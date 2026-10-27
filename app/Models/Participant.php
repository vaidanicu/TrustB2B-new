<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AP10-13. Reached two ways: by an organizer through the normal
 * tenant-scoped queries (BelongsToOrganization applies as usual), and by
 * the participant themselves through a signed link with no tenant context
 * at all. The second path is why every public controller action loads this
 * model via Participant::withoutGlobalScopes()->findOrFail() rather than
 * route-model-binding or a plain query — see the
 * enable_tenant_row_level_security_on_events_table migration's docblock for
 * why that's an Eloquent-only guard on this table, not also an RLS one, for
 * now.
 */
#[Fillable(['name', 'email', 'status', 'photo_consent', 'responded_at', 'link_expires_at', 'deletion_requested_at', 'gdpr_accepted_at', 'gdpr_accepted_ip', 'organization_id', 'franchise_partner_id'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use BelongsToOrganization, HasFactory;

    protected function casts(): array
    {
        return [
            'photo_consent' => 'boolean',
            'responded_at' => 'datetime',
            'link_expires_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'gdpr_accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
