<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * enable_tenant_row_level_security_on_events_table missed the exact
     * problem its own sibling (participants) was explicitly written to
     * avoid: a participant reaches an event too, indirectly, through the
     * event() relation on their own signed link in ParticipantController
     * (the show, respond and eventPayload methods) — with no session and
     * no tenant context at all. withoutGlobalScopes() on that eager-load
     * bypasses Eloquent's TenantScope, same as it already does for
     * Participant, but FORCE ROW LEVEL SECURITY on "events" doesn't care
     * about that bypass — it blocked the query at the database layer
     * regardless, so the loaded participant's event came back null and
     * crashed eventPayload() (confirmed live:
     * Event::withoutGlobalScopes()->count() === 0 under no tenant context,
     * via tinker).
     *
     * Same fix as users/participants: Eloquent-level BelongsToOrganization
     * scoping is this table's only guard for now, until a narrow per-row
     * carve-out (not a blanket bypass) exists for the public participant
     * path specifically.
     */
    public function up(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        DB::statement('drop policy if exists tenant_isolation on events');
        DB::statement('alter table events disable row level security');
        DB::statement('alter table events no force row level security');
    }

    public function down(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        DB::statement('alter table events enable row level security');
        DB::statement('alter table events force row level security');
        DB::statement('create policy tenant_isolation on events using ('
            ."current_setting('app.is_platform_admin', true) = 'true'"
            .' or ('
            ."coalesce(current_setting('app.current_organization_id', true), '') <> ''"
            .' and organization_id = '
            ."nullif(current_setting('app.current_organization_id', true), '')::bigint"
            .') or ('
            ."coalesce(current_setting('app.current_organization_id', true), '') = ''"
            ." and coalesce(current_setting('app.current_franchise_partner_id', true), '') <> ''"
            .' and franchise_partner_id = '
            ."nullif(current_setting('app.current_franchise_partner_id', true), '')::bigint"
            .')'
            .')');
    }

    private function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
