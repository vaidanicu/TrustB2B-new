<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * roadmap §6 / AP07-08. The first real content model — see
 * BelongsToOrganization for what that trait does and does not protect.
 */
#[Fillable([
    'name', 'purpose', 'country', 'language', 'starts_at', 'ends_at',
    'retention_days', 'end_of_cycle_action', 'status', 'created_by',
    'organizer', 'co_organizer', 'location', 'agency', 'photographer',
    'data_protection_contact', 'final_package_recipient',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use BelongsToOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'retention_days' => 'integer',
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
