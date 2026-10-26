<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AP07/08. Events are set up by the agency (franchise-partner-admin or
 * platform-/franchisor-admin — the 'events.manage' permission, see
 * RolesAndPermissionsSeeder) on behalf of a client, never by the client's
 * own staff: the GDPR contract participants must accept before attending is
 * drafted here, and a customer-admin/event-manager deciding their own
 * consent terms isn't something this platform allows. organization_id is
 * therefore no longer auto-stamped from the acting user's own tenant
 * context (they have none — franchise/platform staff aren't scoped to an
 * organization) but chosen explicitly from the organizations that actor can
 * already see (Organization's own TenantScope: all of them for a system
 * operator, only their own franchise's for a franchise-partner-admin).
 */
class EventController extends Controller
{
    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('events.manage'), 403);

        return Inertia::render('Events/Create', [
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'selectedOrganizationId' => $request->integer('organization_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('events.manage'), 403);

        $data = $request->validate([
            'organization_id' => ['required', 'integer'],
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
            'is_restaurant' => ['nullable', 'boolean'],
            'gdpr_contract_text' => ['nullable', 'string', 'max:20000'],
        ]);

        $isRestaurant = $request->boolean('is_restaurant');

        if (! $isRestaurant && blank($data['gdpr_contract_text'] ?? null)) {
            throw ValidationException::withMessages([
                'gdpr_contract_text' => 'Textul contractului GDPR este obligatoriu pentru evenimente care nu sunt de tip restaurant.',
            ]);
        }

        // Organization's own TenantScope ('id' column) is what actually
        // enforces "only an organization this actor can already see" —
        // findOrFail throws (404) the same way it would for a bare id that
        // doesn't exist at all if a franchise-partner-admin tries to pass
        // another partner's organization_id by hand.
        $organization = Organization::findOrFail($data['organization_id']);

        $event = Event::create([
            ...$data,
            'organization_id' => $organization->id,
            'is_restaurant' => $isRestaurant,
            'gdpr_contract_text' => $isRestaurant ? null : $data['gdpr_contract_text'],
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($event)
            ->log('event created');

        return redirect()->route('organizations.events', $organization)
            ->with('status', "Eveniment creat: {$event->name}");
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->can('events.manage'), 403);

        $name = $event->name;
        $organization = $event->organization;

        activity()
            ->causedBy($request->user())
            ->performedOn($event)
            ->log('event deleted');

        // Participants cascade via the events.id foreign key; gallery
        // photos (spatie/laravel-medialibrary) clean up their own files
        // through the model's own deleting event — neither needs doing
        // by hand here.
        $event->delete();

        return redirect()->route('organizations.events', $organization)
            ->with('status', "Eveniment șters: {$name}");
    }
}
