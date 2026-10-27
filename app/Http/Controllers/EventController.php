<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'gdpr_contract_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'client_contact_name' => ['nullable', 'string', 'max:255'],
            'client_contact_email' => ['nullable', 'email', 'max:255'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'client_tax_number' => ['nullable', 'string', 'max:255'],
            'photographer_email' => ['nullable', 'email', 'max:255'],
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

        $photographerEmailSent = null;
        $photographerUser = null;

        if (filled($data['photographer_email'] ?? null)) {
            [$photographerUser, $photographerEmailSent] = $this->findOrCreatePhotographer(
                $data['photographer_email'],
                $data['photographer'] ?? $data['photographer_email'],
                $organization,
                $data['name'],
                $request->user()->locale ?? 'ro',
            );
        }

        $event = Event::create([
            ...$data,
            'organization_id' => $organization->id,
            'is_restaurant' => $isRestaurant,
            'gdpr_contract_text' => $isRestaurant ? null : $data['gdpr_contract_text'],
            'photographer_user_id' => $photographerUser?->id,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        if ($request->hasFile('gdpr_contract_file')) {
            $event->addMediaFromRequest('gdpr_contract_file')->toMediaCollection('gdpr-contract');
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($event)
            ->log('event created');

        return redirect()->route('organizations.events', $organization)
            ->with('status', "Eveniment creat: {$event->name}")
            ->with('photographerEmailSent', $photographerEmailSent);
    }

    /**
     * Reuses an existing account by email (a photographer working multiple
     * events shouldn't get a new password every time) or creates one —
     * either way, grants 'photographer' additively (AssignRole never
     * revokes what's already there) and links it as this event's one
     * allowed uploader (GalleryController::authorizeUpload()). Only a
     * freshly created account gets the "here's your password" email; an
     * existing one already knows how to log in.
     *
     * @return array{0: User, 1: bool|null}
     */
    private function findOrCreatePhotographer(string $email, string $name, Organization $organization, string $eventName, string $lang): array
    {
        $user = User::withoutGlobalScopes()->where('email', $email)->first();
        $emailSent = null;

        if ($user === null) {
            $password = Str::password(14);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'organization_id' => $organization->id,
                'franchise_partner_id' => $organization->franchise_partner_id,
                'email_verified_at' => now(),
            ]);

            $emailSent = (new SendAccountCreatedEmail)->send($user, $password, 'event-photographer', $eventName, $lang);
        }

        (new AssignRole)->grant($user, 'photographer', 'designated event uploader');

        return [$user, $emailSent];
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
