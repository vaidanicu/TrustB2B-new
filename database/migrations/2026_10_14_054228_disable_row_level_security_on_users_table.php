<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrects a gap left by the enable_tenant_row_level_security migration:
     * it put RLS on BOTH organizations and users, but App\Models\User
     * deliberately carries no Eloquent TenantScope (see the long comment on
     * User::booted()) — a query the auth guard runs to find a user by
     * email/session/remember-token happens before any tenant context can
     * exist, so a scope applied there would deny it outright.
     *
     * That PHP-level fix was incomplete: FORCE ROW LEVEL SECURITY on the
     * users TABLE is enforced by Postgres itself, independent of Eloquent.
     * Every query against users — including the auth guard's own, and this
     * project's seeders — was still silently denied on real Postgres the
     * whole time; sqlite has no RLS at all, so this never surfaced until
     * deploying against a real Postgres database for the first time.
     *
     * Users table protection (who may list/view which users) belongs at
     * the application layer — a Policy once a user-management controller
     * exists — not at the database layer where the auth system itself has
     * to be exempt from it regardless.
     */
    public function up(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        DB::statement('drop policy if exists tenant_isolation on users');
        DB::statement('alter table users disable row level security');
        DB::statement('alter table users no force row level security');
    }

    public function down(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        $policy = <<<'SQL'
            current_setting('app.is_platform_admin', true) = 'true'
            or (
                coalesce(current_setting('app.current_organization_id', true), '') <> ''
                and organization_id = current_setting('app.current_organization_id', true)::bigint
            )
            or (
                coalesce(current_setting('app.current_organization_id', true), '') = ''
                and coalesce(current_setting('app.current_franchise_partner_id', true), '') <> ''
                and franchise_partner_id = current_setting('app.current_franchise_partner_id', true)::bigint
            )
        SQL;

        DB::statement('alter table users enable row level security');
        DB::statement('alter table users force row level security');
        DB::statement("create policy tenant_isolation on users using ({$policy})");
    }

    private function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
