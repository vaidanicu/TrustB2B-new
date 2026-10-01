<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Deny-by-default tenant isolation (roadmap §2/§5, AP05):
 *
 *   - system operator (platform-admin / franchisor-admin): unrestricted.
 *   - organization-scoped actor: only rows for their organization.
 *   - franchise-scoped actor (no organization_id): only rows for their
 *     franchise partner — "own customers, no other partner's data".
 *   - no tenant context at all: nothing. Not "everything".
 *
 * On PostgreSQL this is backed by real Row-Level Security policies (see the
 * enable_tenant_row_level_security migration) using the same three facts,
 * mirrored into session variables by SetTenantContext — this scope is not
 * the only guard, it is the ergonomic one.
 */
class TenantScope implements Scope
{
    public function __construct(private readonly string $organizationColumn = 'organization_id') {}

    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::isSystemOperator()) {
            return;
        }

        if (($organizationId = TenantContext::organizationId()) !== null) {
            $builder->where($model->qualifyColumn($this->organizationColumn), $organizationId);

            return;
        }

        if (($franchisePartnerId = TenantContext::franchisePartnerId()) !== null) {
            $builder->where($model->qualifyColumn('franchise_partner_id'), $franchisePartnerId);

            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
