<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Tenancy\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'franchise_partner_id', 'organization_id'])]
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
            if ($user->organization_id === null) {
                $user->organization_id = TenantContext::organizationId();
            }

            if ($user->franchise_partner_id === null) {
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
}
