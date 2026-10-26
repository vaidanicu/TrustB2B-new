<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
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
])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use BelongsToOrganization, HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'retention_days' => 'integer',
            'is_restaurant' => 'boolean',
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
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}
