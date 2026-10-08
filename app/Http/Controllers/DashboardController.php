<?php

namespace App\Http\Controllers;

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
 * Event does not exist yet (AP07/08, deliberately built after the
 * frontend per the current work order) — this renders the real page shell
 * with an honest empty state rather than placeholder data that would look
 * like it came from a database.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->organization_id === null) {
            return redirect()->route('organizations.index');
        }

        return Inertia::render('Dashboard', [
            'events' => [],
        ]);
    }
}
