<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * "/dashboard" means something different per role (AP06): an
 * organization-scoped user's dashboard is their events list; a Partner
 * account's (PartnerController::findOrCreateAccount()) is a cross-event
 * removal-requests view; a franchise/system-level actor's real home is the
 * client list, so we send them straight there instead of showing an empty
 * events screen that isn't theirs.
 *
 * Events are real now (AP07/08) — Event's BelongsToOrganization scope
 * already limits this query to the acting user's own organization.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isActingAsPartnerOnly()) {
            return $this->partnerDashboard($user->partner);
        }

        if ($user->organization_id === null) {
            return redirect()->route('organizations.index');
        }

        $events = Event::query()
            ->orderBy('starts_at')
            ->get(['id', 'name', 'starts_at', 'status', 'is_restaurant']);

        return Inertia::render('Dashboard', [
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->starts_at->format('d.m.Y H:i'),
                'status' => $event->status,
                'isRestaurant' => $event->is_restaurant,
            ]),
        ]);
    }

    /**
     * Every pending removal request across every event assigned to this
     * Partner (Event.partner_id), in one place — replaces having to rely
     * on a per-request email landing in a personal inbox. View-only:
     * moderating (approve/deny) stays with the client's own
     * media.moderate-deletion-requests holders; Event's own TenantScope
     * already keeps this to the partner's own franchise, the explicit
     * partner_id match narrows it to just their events.
     */
    private function partnerDashboard(Partner $partner): Response
    {
        $events = Event::query()->where('partner_id', $partner->id)->get();

        $flaggedPhotos = $events->flatMap(function (Event $event) {
            return $event->getMedia('gallery')
                ->filter(fn (Media $media) => (bool) $media->getCustomProperty('deletion_requested', false))
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'eventName' => $event->name,
                    'requestedBy' => $media->getCustomProperty('deletion_requested_by'),
                    'requestedByEmail' => $media->getCustomProperty('deletion_requested_by_email'),
                    'reason' => $media->getCustomProperty('deletion_reason'),
                    'requestedAt' => $media->getCustomProperty('deletion_requested_at'),
                ]);
        })->sortByDesc('requestedAt')->values();

        return Inertia::render('Partners/Dashboard', [
            'partnerName' => $partner->name,
            'flaggedPhotos' => $flaggedPhotos,
        ]);
    }
}
