<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * enable_tenant_row_level_security only ever covered organizations and
     * users — events (AP07/08) has been a real content table since,
     * protected only by the Eloquent-level BelongsToOrganization scope.
     * Backfilling that gap here, one migration after the fact instead of
     * editing an already-deployed one (see the migrations skill on treating
     * deployed migrations as immutable).
     *
     * participants (AP10-13) is deliberately NOT included here. A
     * participant reaches their own row through a signed link with no
     * session and no tenant context at all — the exact shape of problem
     * that already broke `users` under RLS once (see
     * disable_row_level_security_on_users_table) and the auth guard before
     * that. FORCE ROW LEVEL SECURITY cannot be bypassed from the Eloquent
     * side the way withoutGlobalScopes() bypasses TenantScope, so applying
     * this table's tenant policy as-is would lock every participant out of
     * their own invitation. The right fix is a narrow, explicit carve-out
     * (e.g. a session var scoped to the one signed-for participant id, not
     * a blanket admin bypass — runAsSystemOperator's own docblock rules
     * that out for customer content) — deliberately left for a follow-up
     * migration rather than guessed at here. Eloquent-level scoping via
     * BelongsToOrganization is participants' only guard for now; every
     * public controller action must keep using withoutGlobalScopes() to
     * reach a specific signed-for row, not to list or browse them.
     */
    public function up(): void
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
            ." and organization_id = current_setting('app.current_organization_id', true)::bigint"
            .') or ('
            ."coalesce(current_setting('app.current_organization_id', true), '') = ''"
            ." and coalesce(current_setting('app.current_franchise_partner_id', true), '') <> ''"
            ." and franchise_partner_id = current_setting('app.current_franchise_partner_id', true)::bigint"
            .')'
            .')');
    }

    public function down(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        DB::statement('drop policy if exists tenant_isolation on events');
        DB::statement('alter table events disable row level security');
    }

    private function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
