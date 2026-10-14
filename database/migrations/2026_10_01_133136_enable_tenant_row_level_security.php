<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roadmap §5/§8 and AP05 both require tenant isolation enforced below
     * the application layer, not only by Eloquent scopes. On PostgreSQL we
     * back the app-level BelongsToOrganization scope with real Row-Level
     * Security; on sqlite (local dev) this migration is a no-op — RLS does
     * not exist there, so the Eloquent scope is the only guard locally.
     *
     * SetTenantContext middleware sets three session variables per request:
     *   app.current_organization_id, app.current_franchise_partner_id,
     *   app.is_platform_admin
     *
     * Known limitation: Postgres never applies RLS to the table owner
     * unless FORCE ROW LEVEL SECURITY is also set (which we do below), and
     * never applies it to a superuser regardless. For this to mean anything
     * in production, the application's DB role must NOT be a superuser.
     * Document/verify that when provisioning the production database.
     *
     * CORRECTED by disable_row_level_security_on_users_table: the policy
     * this migration puts on `users` below does not survive — it blocked
     * the auth guard's own queries (and every seeder) on real Postgres,
     * the same circular problem User::booted()'s docblock describes for
     * the Eloquent scope, just enforced one layer deeper. sqlite has no
     * RLS at all, so that migration's fix went untested against a real
     * Postgres database until this one surfaced it for real.
     */
    public function up(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        $policy = <<<'SQL'
            current_setting('app.is_platform_admin', true) = 'true'
            or (
                coalesce(current_setting('app.current_organization_id', true), '') <> ''
                and organization_id_column = current_setting('app.current_organization_id', true)::bigint
            )
            or (
                coalesce(current_setting('app.current_organization_id', true), '') = ''
                and coalesce(current_setting('app.current_franchise_partner_id', true), '') <> ''
                and franchise_partner_id_column = current_setting('app.current_franchise_partner_id', true)::bigint
            )
        SQL;

        // organizations: the "organization_id" of this table is its own id.
        DB::statement('alter table organizations enable row level security');
        DB::statement('alter table organizations force row level security');
        DB::statement('create policy tenant_isolation on organizations using ('
            .str_replace(['organization_id_column', 'franchise_partner_id_column'], ['id', 'franchise_partner_id'], $policy)
            .')');

        // users: scoped by its own organization_id / franchise_partner_id columns.
        DB::statement('alter table users enable row level security');
        DB::statement('alter table users force row level security');
        DB::statement('create policy tenant_isolation on users using ('
            .str_replace(['organization_id_column', 'franchise_partner_id_column'], ['organization_id', 'franchise_partner_id'], $policy)
            .')');
    }

    public function down(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        DB::statement('drop policy if exists tenant_isolation on organizations');
        DB::statement('alter table organizations disable row level security');

        DB::statement('drop policy if exists tenant_isolation on users');
        DB::statement('alter table users disable row level security');
    }

    private function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
