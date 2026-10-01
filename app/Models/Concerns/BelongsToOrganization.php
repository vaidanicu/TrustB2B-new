<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For models that live under a single customer organization (AP05): User
 * today, and Event/Participant/Document/Media/GDPR-case once those APs are
 * built. Applies TenantScope and auto-stamps organization_id and the
 * denormalized franchise_partner_id on create when the caller didn't set
 * them explicitly.
 *
 * NOT used by App\Models\User, and not safe to use on any model Laravel's
 * auth guard queries directly: the global scope this trait adds applies to
 * EVERY query on the model, including the guard's own user-by-email/
 * session/remember-token lookups — which run before any tenant context can
 * exist, so TenantScope's deny-by-default rule would lock every user out.
 * (This is exactly what happened when User first used this trait: login,
 * password reset and password confirmation all broke.) User instead
 * auto-stamps organization_id/franchise_partner_id directly in its own
 * booted() without adding the scope — copy that pattern, not this trait,
 * for any other model the auth system itself queries.
 *
 * NOTE for future content models (events, participants, documents, media,
 * GDPR cases) that do NOT have that problem: TenantScope has no
 * system-operator bypass carve-out of its own beyond what TenantScope
 * itself grants. Roadmap §2 draws a line between account/tenancy records
 * (fine to let platform-/franchisor-admin see outright) and *customer
 * content*, where that access must be justified and logged instead of
 * silently unrestricted — re-check that distinction before reusing this
 * trait as-is on a content model.
 */
trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new TenantScope('organization_id'));

        static::creating(function (Model $model) {
            if ($model->organization_id === null) {
                $model->organization_id = TenantContext::organizationId();
            }

            if ($model->franchise_partner_id === null) {
                $model->franchise_partner_id = $model->organization_id
                    ? Organization::withoutGlobalScopes()->whereKey($model->organization_id)->value('franchise_partner_id')
                    : TenantContext::franchisePartnerId();
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
