<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Partner;
use App\Models\User;
use App\Support\FlashMessage;
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
 * RolesAndPermissionsSeeder) or by a Partner for its own clients — never
 * by the client's own staff: the GDPR contract participants must accept
 * before attending is drafted here, and a customer-admin/event-manager
 * deciding their own consent terms isn't something this platform allows.
 * organization_id is therefore no longer auto-stamped from the acting
 * user's own tenant context (they have none — franchise/platform staff
 * and Partners aren't scoped to an organization) but chosen explicitly
 * from the organizations that actor can already see (Organization's own
 * TenantScope: all of them for a system operator, only their own
 * franchise's for a franchise-partner-admin or Partner).
 *
 * A Partner (User::isActingAsPartnerOnly()) reaches create()/store() too,
 * but never events.manage's full reach: it can create events for any
 * client in its own franchise, always attributed to itself (partner_id is
 * forced, never a free choice), and still can't reschedule or delete —
 * update()/destroy() stay franchise/platform-only below.
 */
class EventController extends Controller
{
    /**
     * There was previously no way to see an event's own data again after
     * creating it — reported: "wo kann ich die eingestellten Daten des
     * Events mir ansehen?? Die sehe ich nicht mehr". Open to whoever can
     * already reach the event elsewhere: the agency (events.manage) or
     * the client's own staff (their organization_id matches the event's).
     */
    public function show(Request $request, Event $event): Response
    {
        $user = $request->user();
        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        abort_unless(
            $user->can('events.manage') || $user->organization_id === $event->organization_id || $isOwnPartnerEvent,
            403
        );

        $event->loadMissing('organization', 'partner', 'photographerUser', 'creator');
        $gdprContract = $event->getFirstMedia('gdpr-contract');

        // Same reasoning as GalleryController::index()'s own backUrl: a
        // franchise/platform viewer (no organization_id of their own)
        // never reaches participants.status regardless of event type, and
        // a restaurant event has no participants page to begin with.
        $backUrl = $user->organization_id === null
            ? route('organizations.events', $event->organization)
            : route('dashboard');

        return Inertia::render('Events/Show', [
            'backUrl' => $backUrl,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'purpose' => $event->purpose,
                'country' => $event->country,
                'language' => $event->language,
                'isRestaurant' => $event->is_restaurant,
                'status' => $event->status,
                'startsAt' => $event->starts_at->format('d.m.Y H:i'),
                'endsAt' => $event->ends_at->format('d.m.Y H:i'),
                'startsAtRaw' => $event->starts_at->format('Y-m-d\TH:i'),
                'endsAtRaw' => $event->ends_at->format('Y-m-d\TH:i'),
                'retentionDays' => $event->retention_days,
                'endOfCycleAction' => $event->end_of_cycle_action,
                'organizer' => $event->organizer,
                'coOrganizer' => $event->co_organizer,
                'location' => $event->location,
                'agency' => $event->agency,
                'photographer' => $event->photographer,
                'photographerAccountEmail' => $event->photographerUser?->email,
                'dataProtectionContact' => $event->data_protection_contact,
                'finalPackageRecipient' => $event->final_package_recipient,
                'partnerName' => $event->partner ? "{$event->partner->name} ({$event->partner->country})" : null,
                'clientCompanyName' => $event->client_company_name,
                'clientContactName' => $event->client_contact_name,
                'clientContactEmail' => $event->client_contact_email,
                'clientAddress' => $event->client_address,
                'clientTaxNumber' => $event->client_tax_number,
                'gdprContractText' => $event->gdpr_contract_text,
                'gdprContractFileUrl' => $gdprContract?->getUrl(),
                'organizationName' => $event->organization?->name,
                'organizationId' => $event->organization_id,
                'createdByName' => $event->creator?->name,
            ],
            'canManage' => $user->can('events.manage') || $isOwnPartnerEvent,
        ]);
    }

    /**
     * The only field an agency actor can change after creation — reported:
     * events need to move when a client postpones them, and
     * UpdateEventStatusesCommand has no way to know that on its own.
     * Rescheduling a 'finished' event back into the future reverts that
     * automatic stamp (it isn't done after all); 'archived' is left
     * alone, since the gallery may already be gone by then.
     *
     * A Partner may also reschedule, but only its OWN event (partner_id
     * match) — events.manage has no such per-row scoping (any
     * franchise/platform actor who holds it can touch any event in their
     * franchise), so a blanket role grant would have let a Partner touch
     * every event in the franchise, not just its own.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();
        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        abort_unless($user->can('events.manage') || $isOwnPartnerEvent, 403);

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        $event->forceFill($data);

        if ($event->status === 'finished' && $event->ends_at->isFuture()) {
            $event->status = $event->starts_at->isFuture() ? 'draft' : 'active';
        }

        $event->save();

        activity()
            ->causedBy($user)
            ->performedOn($event)
            ->log('event rescheduled');

        return back()->with('status', FlashMessage::t($user->locale ?? 'ro', 'event.rescheduled', ['name' => $event->name]));
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $partner = $user->isActingAsPartnerOnly() ? $user->partner : null;

        abort_unless($partner !== null || $user->can('events.manage'), 403);

        return Inertia::render('Events/Create', [
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            // A Partner never picks "Partener responsabil" — it's always
            // itself, forced in store() below — so it gets no dropdown
            // list at all, just its own name shown read-only.
            'partners' => $partner === null ? Partner::query()->orderBy('name')->get(['id', 'name', 'country']) : [],
            'lockedPartnerName' => $partner?->name,
            'selectedOrganizationId' => $request->integer('organization_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $actingPartner = $user->isActingAsPartnerOnly() ? $user->partner : null;

        abort_unless($actingPartner !== null || $user->can('events.manage'), 403);

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
            'client_company_name' => ['nullable', 'string', 'max:255'],
            'client_contact_email' => ['nullable', 'email', 'max:255'],
            'client_address' => ['nullable', 'string', 'max:255'],
            'client_tax_number' => ['nullable', 'string', 'max:255'],
            'photographer_email' => ['nullable', 'email', 'max:255'],
            'partner_id' => ['nullable', 'integer'],
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

        // A Partner is always the event's own partner — never a free
        // choice (the dropdown doesn't even reach it, see create()) — a
        // franchise/platform actor still picks one explicitly. Same
        // TenantScope reasoning as $organization above for that pick:
        // findOrFail 404s the same way it would for a bare id that
        // doesn't exist at all otherwise.
        $partnerId = $actingPartner !== null
            ? $actingPartner->id
            : (filled($data['partner_id'] ?? null) ? Partner::findOrFail($data['partner_id'])->id : null);

        $photographerEmailSent = null;
        $photographerUser = null;

        if (filled($data['photographer_email'] ?? null)) {
            [$photographerUser, $photographerEmailSent] = $this->findOrCreatePhotographer(
                $data['photographer_email'],
                $data['photographer'] ?? $data['photographer_email'],
                $organization,
                $data['name'],
                $user->locale ?? 'ro',
            );
        }

        $event = Event::create([
            ...$data,
            'organization_id' => $organization->id,
            'partner_id' => $partnerId,
            'is_restaurant' => $isRestaurant,
            'gdpr_contract_text' => $isRestaurant ? null : $data['gdpr_contract_text'],
            'photographer_user_id' => $photographerUser?->id,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        if ($request->hasFile('gdpr_contract_file')) {
            $event->addMediaFromRequest('gdpr_contract_file')->toMediaCollection('gdpr-contract');
        }

        activity()
            ->causedBy($user)
            ->performedOn($event)
            ->log('event created');

        return redirect()->route('organizations.events', $organization)
            ->with('status', FlashMessage::t($user->locale ?? 'ro', 'event.created', ['name' => $event->name]))
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

    /**
     * A Partner may also delete, but only its OWN event — same per-row
     * scoping reasoning as update() above, since events.manage itself
     * carries no such restriction.
     */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();
        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        abort_unless($user->can('events.manage') || $isOwnPartnerEvent, 403);

        $name = $event->name;
        $organization = $event->organization;

        activity()
            ->causedBy($user)
            ->performedOn($event)
            ->log('event deleted');

        // Participants cascade via the events.id foreign key; gallery
        // photos (spatie/laravel-medialibrary) clean up their own files
        // through the model's own deleting event — neither needs doing
        // by hand here.
        $event->delete();

        return redirect()->route('organizations.events', $organization)
            ->with('status', FlashMessage::t($user->locale ?? 'ro', 'event.deleted', ['name' => $name]));
    }
}
