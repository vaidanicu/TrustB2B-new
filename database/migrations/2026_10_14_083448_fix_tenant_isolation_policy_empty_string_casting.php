<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Both enable_tenant_row_level_security's original tenant_isolation
     * policy (still live on organizations) and this project's own
     * enable_tenant_row_level_security_on_events_table copy of it cast
     * current_setting('app.current_organization_id', true) straight to
     * ::bigint, guarded only by a `coalesce(...) <> ''` check in the same
     * AND clause. Postgres does not guarantee left-to-right short-circuit
     * evaluation of AND/OR in a USING expression, so it can evaluate the
     * cast before the guard — and casting an empty string ('', what
     * set_config stores for "no organization", e.g. a franchise-level or
     * platform-admin actor) to bigint is a hard SQL error
     * (22P02: invalid input syntax for type bigint), not merely "no
     * match". That broke `select count(*) from organizations` (and now
     * events too) for exactly those actors — caught against the real
     * production database, never on sqlite, where RLS does not exist to
     * hit this at all.
     *
     * Fix: wrap every such cast in nullif(value, ''), which turns the
     * empty string into a real NULL *before* casting — and NULL::bigint
     * never errors, regardless of evaluation order. Same tenant-boundary
     * logic as before, just evaluation-order-safe. Not touching `users`:
     * its RLS was already disabled entirely by
     * disable_row_level_security_on_users_table for an unrelated reason
     * and never carried this bug forward.
     */
    public function up(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        DB::statement('drop policy if exists tenant_isolation on organizations');
        DB::statement('create policy tenant_isolation on organizations using ('
            .$this->policy('id', 'franchise_partner_id')
            .')');

        DB::statement('drop policy if exists tenant_isolation on events');
        DB::statement('create policy tenant_isolation on events using ('
            .$this->policy('organization_id', 'franchise_partner_id')
            .')');
    }

    public function down(): void
    {
        if (! $this->isPostgres()) {
            return;
        }

        $unsafePolicy = <<<'SQL'
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

        DB::statement('drop policy if exists tenant_isolation on organizations');
        DB::statement('create policy tenant_isolation on organizations using ('
            .str_replace(['organization_id_column', 'franchise_partner_id_column'], ['id', 'franchise_partner_id'], $unsafePolicy)
            .')');

        DB::statement('drop policy if exists tenant_isolation on events');
        DB::statement('create policy tenant_isolation on events using ('
            .str_replace(['organization_id_column', 'franchise_partner_id_column'], ['organization_id', 'franchise_partner_id'], $unsafePolicy)
            .')');
    }

    private function policy(string $organizationColumn, string $franchiseColumn): string
    {
        return <<<SQL
            current_setting('app.is_platform_admin', true) = 'true'
            or (
                coalesce(current_setting('app.current_organization_id', true), '') <> ''
                and {$organizationColumn} = nullif(current_setting('app.current_organization_id', true), '')::bigint
            )
            or (
                coalesce(current_setting('app.current_organization_id', true), '') = ''
                and coalesce(current_setting('app.current_franchise_partner_id', true), '') <> ''
                and {$franchiseColumn} = nullif(current_setting('app.current_franchise_partner_id', true), '')::bigint
            )
            SQL;
    }

    private function isPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
