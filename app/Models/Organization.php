<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A paying B2B customer (Kundenorganisation) — the tenant boundary for
 * everything beneath it (users, and in later APs: events, participants,
 * documents, media, data-subject requests). See roadmap §2/§6 and AP05.
 */
#[Fillable([
    'franchise_partner_id', 'partner_id', 'name', 'country', 'languages',
    'data_protection_contact_email', 'contract_status',
    'storage_quota_mb', 'event_quota',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * Visible to: system operators (unrestricted), the organization's own
     * users (their own row only, via its "id"), and their franchise
     * partner's staff (every organization under that franchise_partner_id).
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope('id'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
        ];
    }

    /**
     * @return BelongsTo<FranchisePartner, $this>
     */
    public function franchisePartner(): BelongsTo
    {
        return $this->belongsTo(FranchisePartner::class);
    }

    /**
     * The regional reseller that onboarded this client — reported: a
     * Partner should be able to create and administer its own clients
     * directly. Distinct from Event.partner_id (sales attribution per
     * event); this one is "who brought this client onto the platform".
     *
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
