<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AP07/08. Event uses BelongsToOrganization, so organization_id and
 * franchise_partner_id are stamped automatically from the acting user —
 * this controller never sets them itself.
 */
class EventController extends Controller
{
    public function create(Request $request): Response
    {
        abort_unless($request->user()->organization_id !== null, 403);

        return Inertia::render('Events/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->organization_id !== null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'country' => ['required', 'string', 'size:2'],
            'language' => ['required', 'string', 'max:5'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'retention_days' => ['required', 'integer', 'in:60,90'],
            'end_of_cycle_action' => ['required', 'in:delete,export'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'co_organizer' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:255'],
            'photographer' => ['nullable', 'string', 'max:255'],
            'data_protection_contact' => ['nullable', 'email', 'max:255'],
            'final_package_recipient' => ['nullable', 'string', 'max:255'],
        ]);

        $event = Event::create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($event)
            ->log('event created');

        return redirect()->route('dashboard')->with('status', "Eveniment creat: {$event->name}");
    }
}
