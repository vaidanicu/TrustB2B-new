<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user into TenantContext for this request, then
 * mirrors it into the database session (see TenantContext::syncToDatabaseSession
 * — a no-op on sqlite) so the database's own Row-Level Security policies (see
 * the enable_tenant_row_level_security migration) see exactly the same tenant
 * boundary as the Eloquent-level TenantScope.
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
        TenantContext::syncToDatabaseSession();

        return $next($request);
    }
}
