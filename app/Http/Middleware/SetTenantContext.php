<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user into TenantContext for this request, and
 * — on PostgreSQL only — mirrors the same three facts into session
 * variables so the database's own Row-Level Security policies (see the
 * enable_tenant_row_level_security migration) see exactly the same tenant
 * boundary as the Eloquent-level TenantScope. sqlite (local dev) has no RLS,
 * so only the Eloquent side applies there.
 *
 * Runs on every web request, authenticated or not — an anonymous visitor
 * gets "no context", which TenantScope treats as deny-by-default, not as
 * "everything".
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::setFromUser($request->user());

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("select set_config('app.current_organization_id', ?, false)", [
                (string) (TenantContext::organizationId() ?? ''),
            ]);
            DB::statement("select set_config('app.current_franchise_partner_id', ?, false)", [
                (string) (TenantContext::franchisePartnerId() ?? ''),
            ]);
            DB::statement("select set_config('app.is_platform_admin', ?, false)", [
                TenantContext::isSystemOperator() ? 'true' : 'false',
            ]);
        }

        return $next($request);
    }
}
