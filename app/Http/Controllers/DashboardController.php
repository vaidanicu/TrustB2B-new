<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "/dashboard" means something different per role (AP06): an
 * organization-scoped user's dashboard is their events list; a
 * franchise/system-level actor's real home is the client list, so we send
 * them straight there instead of showing an empty events screen that
 * isn't theirs.
 *
 * Events are real now (AP07/08) — Event's BelongsToOrganization scope
 * already limits this query to the acting user's own organization.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

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
                'date' => $event->starts_at->format('d.m.Y'),
                'status' => $event->status,
                'isRestaurant' => $event->is_restaurant,
            ]),
        ]);
    }
}
