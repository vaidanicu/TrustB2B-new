<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Tenancy\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'locale', 'franchise_partner_id', 'organization_id', 'email_verified_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Deliberately NOT using App\Models\Concerns\BelongsToOrganization here:
     * that trait's TenantScope would apply to every query against this
     * table, including the ones Laravel's auth guard itself runs to find a
     * user by email/session/remember-token — before any tenant context can
     * possibly exist. The first version of this model used it and broke
     * login, password reset and password confirmation outright (the
     * lookup query that authentication depends on was scoped to "no
     * tenant", i.e. denied by TenantScope's own deny-by-default rule).
     *
     * So: auto-stamp organization_id/franchise_partner_id on create (same
     * behaviour as BelongsToOrganization), but no global scope. Tenant
     * isolation for "list/find users" must go through an explicit, scoped
     * query instead — e.g. $organization->users, or
     * User::where('organization_id', TenantContext::organizationId()) —
     * never User::all() / User::find() unguarded. Enforce this for real
     * once a user-management controller exists (AP06/AP07) via a Policy
     * that checks the target user's organization_id, not by trying to
     * retrofit a scope onto this model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $user) {
            // array_key_exists, not "=== null": a franchise-level staff
            // member legitimately HAS organization_id = null, explicitly,
            // and that must stick — only an attribute that was never set
            // at all should fall back to the ambient tenant context.
            $attributes = $user->getAttributes();

            if (! array_key_exists('organization_id', $attributes)) {
                $user->organization_id = TenantContext::organizationId();
            }

            if (! array_key_exists('franchise_partner_id', $attributes)) {
                $user->franchise_partner_id = $user->organization_id
                    ? Organization::withoutGlobalScopes()->whereKey($user->organization_id)->value('franchise_partner_id')
                    : TenantContext::franchisePartnerId();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Franchise-partner-level staff (no organization_id) belong directly to
     * a partner. Organization-scoped users also carry this (denormalized
     * from their organization) so every query can filter without a join.
     *
     * @return BelongsTo<FranchisePartner, $this>
     */
    public function franchisePartner(): BelongsTo
    {
        return $this->belongsTo(FranchisePartner::class);
    }

    /**
     * Set only for an account created via PartnerController's own
     * findOrCreateAccount() — DashboardController reads this to decide
     * whether to show the regular org/agency dashboard or the Partner's
     * own cross-event removal-requests view.
     *
     * @return HasOne<Partner, $this>
     */
    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    /**
     * True only for a Partner account with none of the franchise-wide
     * permissions RolesAndPermissionsSeeder now also grants the 'partner'
     * role itself (organizations.manage and friends) — in this business a
     * Partner IS its own franchise, so a brand-new one (PartnerController's
     * own findOrCreateAccount()) is a full franchise-partner-admin in
     * everything but name, same as this method's own long-standing "MM
     * Kft." case (the platform operator's own admin account doubling as
     * the Partner record for Hungary): every controller that used to check
     * this to show the NARROW partner-only view (DashboardController's own
     * removal-requests dashboard, "only its own client/event" scoping in
     * OrganizationController/EventController/GalleryController/
     * ParticipantController) now simply falls through to the SAME
     * full-access branch a franchise-partner-admin already used — their
     * own TenantScope('franchise_partner_id') is what actually keeps that
     * to their own franchise, not any of this narrowing code. The
     * 'franchise-partner-admin' role NAME itself is checked here only for
     * whatever legacy account might still hold it bare (no merged
     * permission on 'partner' yet) — new Partner accounts are never
     * granted it anymore, see findOrCreateAccount()'s own comment.
     *
     * hasNoRealOrganizationStaffRole() stays checked explicitly, same
     * reasoning as before: a real customer-admin/event-manager/DPO whose
     * email got reused for a Partner account must never be narrowed OR
     * widened by it — their own real role always wins either way.
     */
    public function isActingAsPartnerOnly(): bool
    {
        return $this->partner !== null
            && $this->hasNoRealOrganizationStaffRole()
            && ! $this->can('organizations.manage')
            && ! $this->hasAnyRole(['platform-admin', 'franchisor-admin', 'franchise-partner-admin']);
    }

    /**
     * True only for an account with NO real organization-staff role —
     * reported live: a customer-admin got listed as an event's photographer
     * (same email, EventController::findOrCreatePhotographer() grants
     * 'photographer' additively and never revokes anything), and the
     * account's full management view disappeared behind the narrow
     * photographer-only one everywhere that checked hasRole('photographer')
     * directly. The 'photographer'/'client-contact' roles describe how an
     * account was first reached (an event's own designated fields), never
     * what it's allowed to do — genuine org staff always wins, same
     * reasoning as isActingAsPartnerOnly() above.
     */
    public function hasNoRealOrganizationStaffRole(): bool
    {
        return ! $this->hasAnyRole(['customer-admin', 'event-manager', 'data-protection-officer']);
    }

    public function isActingAsPhotographerOnly(): bool
    {
        return $this->hasRole('photographer') && $this->hasNoRealOrganizationStaffRole();
    }

    public function isActingAsClientContactOnly(): bool
    {
        return $this->hasRole('client-contact') && $this->hasNoRealOrganizationStaffRole();
    }
}
