<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * roadmap §6 / AP07-08. The first real content model — see
 * BelongsToOrganization for what that trait does and does not protect.
 *
 * AP14-ish (private gallery). Photos live in the 'gallery' media
 * collection via spatie/laravel-medialibrary (already a dependency, the
 * `media` table has existed since the original scaffolding). Each photo's
 * `published` custom property — not a column, see GalleryController — is
 * what separates "uploaded" (photographer: media.upload) from "visible to
 * participants" (customer-admin/event-manager: media.publish), matching
 * the roadmap §2 split those permissions were seeded for. No conversions/
 * thumbnails: the shared host's image extension support wasn't worth
 * gambling a photo upload failing on, so the gallery serves originals.
 */
#[Fillable([
    'name', 'purpose', 'country', 'language', 'starts_at', 'ends_at',
    'retention_days', 'end_of_cycle_action', 'status', 'created_by',
    'organizer', 'co_organizer', 'location', 'agency', 'photographer',
    'data_protection_contact', 'final_package_recipient',
    'organization_id', 'is_restaurant', 'gdpr_contract_text',
    'client_contact_name', 'client_company_name', 'client_contact_email', 'client_address', 'client_tax_number',
    'photographer_user_id', 'photographer_confirmed_at', 'partner_id',
    'retention_processed_at', 'export_path', 'export_password', 'export_confirmed_at',
])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use BelongsToOrganization, HasFactory, InteractsWithMedia;

    /**
     * 'gallery' holds the event's photos/videos — see GalleryController's
     * docblock for the upload/publish split. 'gdpr-contract' is a single
     * optional PDF, supplementing (not replacing) gdpr_contract_text; it's
     * singleFile() so re-uploading replaces rather than accumulates.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm']);

        $this->addMediaCollection('gdpr-contract')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'retention_days' => 'integer',
            'is_restaurant' => 'boolean',
            'retention_processed_at' => 'datetime',
            'export_confirmed_at' => 'datetime',
            'photographer_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Reported: a photographer re-used across several unrelated clients
     * (EventController::findOrCreatePhotographer() stamps organization_id
     * once, on account creation, never again) got a 404 on every event
     * beyond the first client — implicit route binding runs through
     * BelongsToOrganization's TenantScope before any controller
     * authorization is reached at all, and that scope only ever matches
     * the acting user's OWN organization_id. Falls back to an unscoped
     * lookup, but only for the one event this exact account is actually
     * the designated photographer_user_id on — never a blanket bypass, and
     * safe to do at all only because RLS is already disabled on this table
     * (see disable_row_level_security_on_events_table) for the same class
     * of "authorized, but not by tenant" access.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $event = static::where($field, $value)->first();

        if ($event !== null) {
            return $event;
        }

        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        return static::withoutGlobalScopes()
            ->where($field, $value)
            ->where('photographer_user_id', $user->id)
            ->first();
    }

    /**
     * The single person allowed to upload to this event's gallery when set
     * — see GalleryController's authorizeUpload(). Distinct from the plain
     * `photographer` text field, which is just a display name and existed
     * before this account-backed enforcement did.
     *
     * @return BelongsTo<User, $this>
     */
    public function photographerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photographer_user_id');
    }

    /**
     * The regional reseller responsible for this event's sale — see
     * Partner's own docblock for why this is a different concept from
     * franchise_partner_id.
     *
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * The one formula "has this event's gallery retention expired" is
     * computed from, everywhere it's needed: the public gallery link's own
     * expiry (GalleryController), and ProcessEventRetentionCommand's
     * decision to delete/export. Keeping it in one place means the two can
     * never silently drift apart.
     */
    public function retentionDeadline(): CarbonInterface
    {
        return $this->ends_at->copy()->addDays($this->retention_days);
    }
}
