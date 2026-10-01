<?php

namespace App\Models;

use Database\Factories\FranchisePartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A legally independent regional partner (e.g. RO-Auras, HU-MM) that
 * onboards and supports its own customer organizations. Top level of the
 * TrustBridge tenancy hierarchy — see roadmap §2 and AP05.
 */
#[Fillable(['name', 'region_code', 'country', 'contact_email', 'contract_status'])]
class FranchisePartner extends Model
{
    /** @use HasFactory<FranchisePartnerFactory> */
    use HasFactory;

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * Franchise-partner-level staff (Franchisepartner-Administrator and
     * similar roles) — these users have no organization_id of their own.
     *
     * @return HasMany<User, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereNull('organization_id');
    }
}
