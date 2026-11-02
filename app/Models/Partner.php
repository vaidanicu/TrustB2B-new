<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A regional reseller/representative (e.g. "Auras srl" for HU/AT/DE) that
 * an agency assigns as the sales-responsible party for a specific event
 * (Event.partner_id) — distinct from FranchisePartner, which is a tenancy
 * boundary of the platform itself, not a per-sale attribution. Managed by
 * the agency (partners.manage: platform-/franchisor-/franchise-partner-admin),
 * never by a client's own staff.
 */
#[Fillable(['franchise_partner_id', 'name', 'country'])]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    /**
     * organization_id never actually gets set on a Partner (see the
     * create_partners_table migration) — this scope exists purely so an
     * organization-scoped actor is denied by default, the same guarantee
     * every other tenant-scoped model gets.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope('organization_id'));
    }

    /**
     * @return BelongsTo<FranchisePartner, $this>
     */
    public function franchisePartner(): BelongsTo
    {
        return $this->belongsTo(FranchisePartner::class);
    }
}
