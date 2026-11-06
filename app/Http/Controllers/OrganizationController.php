<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use App\Support\FlashMessage;
use App\Support\GeneratedPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Franchise-partner view of their own client organizations ("Clienți" in
 * the Faza 1 design canvas). TenantScope on Organization already limits
 * the query to the acting user's own franchise (or, for a system
 * operator, everything) — this controller does no filtering of its own.
 *
 * AP06. store() creates the organization AND its first customer-admin
 * together, atomically — an organization with no admin is a dead end
 * nobody can log into. The generated password is emailed (StaffAccountCreated)
 * AND kept in the redirect flash as a fallback — same non-blocking
 * try/catch pattern as ParticipantController@store, since account creation
 * must never fail just because SMTP is briefly unreachable.
 *
 * A Partner (User::isActingAsPartnerOnly()) may also create and
 * administer clients now — reported: "kann Kunden und Events erzeugen
 * und administrieren" — but only its OWN ones (isOwnPartnerClient()):
 * created by it directly (Organization.partner_id) or merely one it has
 * an event with (Event.partner_id). organizations.manage carries no such
 * per-row scoping on its own, so every method below checks this
 * explicitly rather than widening that permission to the 'partner' role.
 */
class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $partner = $user->isActingAsPartnerOnly() ? $user->partner : null;

        // A Partner account sees only its OWN clients — either ones it
        // created itself (Organization.partner_id) or ones it merely has
        // an event with (Event.partner_id, set by a franchise/platform
        // actor assigning it as sales-responsible) — never the full
        // franchise client list, which stays behind organizations.manage
        // below. isActingAsPartnerOnly() (not partner() directly) is what
        // keeps an admin account also linked as a Partner (e.g. "MM Kft."
        // for HU) on the full, unrestricted list.
        if ($partner !== null) {
            $organizations = Organization::query()
                ->where(function ($query) use ($partner) {
                    $query->where('partner_id', $partner->id)
                        ->orWhereHas('events', fn ($eventsQuery) => $eventsQuery->where('partner_id', $partner->id));
                })
                ->with('partner')
                ->orderBy('name')
                ->get(['id', 'name', 'country', 'contract_status', 'partner_id']);
        } else {
            abort_unless($user->can('organizations.manage'), 403);

            $organizations = Organization::query()
                ->with('partner')
                ->orderBy('name')
                ->get(['id', 'name', 'country', 'contract_status', 'partner_id']);
        }

        return Inertia::render('Organizations/Index', [
            // A Partner can also create a new client and change its own
            // clients' contract status now — same "administers its own
            // clients" ability as a franchise/platform actor, just
            // narrowed to the rows above.
            'canManage' => $partner !== null || $user->can('organizations.manage'),
            'organizations' => $organizations->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'country' => $organization->country,
                'status' => $organization->contract_status,
                // Reported: on the full client list, an admin should see
                // at a glance which Partner brought each client on —
                // "Organization.partner_id" (see the model's own
                // docblock), not Event.partner_id, since a client can
                // have events from several partners.
                'partnerName' => $organization->partner?->name,
            ]),
        ]);
    }

    /**
     * Every organization is created with contract_status = 'onboarding'
     * (the column's own DB default) and nothing ever moved it — there was
     * simply no control to do it with. A franchise/platform actor can now
     * set it directly from the client list, same authorization as
     * index()/create() since it's the same "franchise/system-level staff
     * manage clients" screen. A Partner may do the same, but only for its
     * own client (same ownership test as index()'s query).
     */
    public function updateStatus(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();
        $isOwnPartnerClient = $this->isOwnPartnerClient($user, $organization);

        abort_unless($user->can('organizations.manage') || $isOwnPartnerClient, 403);

        $data = $request->validate([
            'contract_status' => ['required', Rule::in(['onboarding', 'active', 'suspended'])],
        ]);

        $organization->forceFill($data)->save();

        activity()
            ->causedBy($user)
            ->performedOn($organization)
            ->withProperties(['status' => $data['contract_status']])
            ->log('organization status changed');

        return back();
    }

    /**
     * "Its own client", for a Partner: one it created itself
     * (Organization.partner_id) or one it merely has an event with
     * (Event.partner_id) — the same two-part test index()'s own query
     * applies, reused everywhere a Partner acts on a specific
     * Organization instead of just listing them.
     */
    private function isOwnPartnerClient(User $user, Organization $organization): bool
    {
        if (! $user->isActingAsPartnerOnly()) {
            return false;
        }

        $partnerId = $user->partner->id;

        return $organization->partner_id === $partnerId
            || $organization->events()->where('partner_id', $partnerId)->exists();
    }

    /**
     * The events belonging to one client — where a franchise/platform actor
     * (the only ones who can create events now, see EventController) sees
     * what they've set up and starts a new one. Event's own TenantScope
     * already guarantees $organization is one this actor can see (route
     * model binding resolves it the same tenant-scoped way); events.manage
     * guards actually touching it, same as EventController itself.
     *
     * A Partner account reaches this same screen too. If it owns this
     * client (Organization.partner_id — e.g. one it created itself), it
     * sees and administers every event for it, same as a franchise actor.
     * Otherwise, narrowed to just its own events for this client
     * (Event.partner_id) — reported: a partner needs a table of which
     * events were done for a client, who photographed each one and who
     * the client-side contact was, not scattered across individual
     * removal-request emails. abort_unless here (rather than letting an
     * empty table render) keeps a partner from browsing a client it
     * neither owns nor has any event with.
     */
    public function events(Request $request, Organization $organization): Response
    {
        $user = $request->user();
        $partner = $user->isActingAsPartnerOnly() ? $user->partner : null;

        if ($partner !== null) {
            $ownsClient = $organization->partner_id === $partner->id;

            abort_unless($ownsClient || $organization->events()->where('partner_id', $partner->id)->exists(), 403);

            $eventsQuery = $ownsClient ? $organization->events() : $organization->events()->where('partner_id', $partner->id);
        } else {
            abort_unless($user->can('events.manage'), 403);
            $eventsQuery = $organization->events();
        }

        $events = $eventsQuery->orderByDesc('starts_at')
            ->with('photographerUser')
            ->get(['id', 'name', 'starts_at', 'status', 'is_restaurant', 'photographer', 'photographer_user_id', 'client_contact_name', 'client_contact_email']);

        return Inertia::render('Organizations/Events', [
            'organization' => ['id' => $organization->id, 'name' => $organization->name],
            // True for either branch — the Partner one is already
            // narrowed to just its own rows above, so "administer what
            // you can see" holds either way.
            'canManage' => true,
            'canCreateEvent' => true,
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->starts_at->format('d.m.Y H:i'),
                'status' => $event->status,
                'isRestaurant' => $event->is_restaurant,
                'photographerName' => $event->photographer,
                'photographerEmail' => $event->photographerUser?->email,
                'clientContactName' => $event->client_contact_name,
                'clientContactEmail' => $event->client_contact_email,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('organizations.manage') || $user->isActingAsPartnerOnly(), 403);

        return Inertia::render('Organizations/Create', [
            // A franchise-partner-admin (or a Partner — both already have
            // exactly one franchise_partner_id of their own) has no choice
            // to make. Only a system operator (no franchise of their own)
            // picks one explicitly.
            'franchisePartners' => $user->franchise_partner_id !== null
                ? []
                : FranchisePartner::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $actingPartner = $user->isActingAsPartnerOnly() ? $user->partner : null;

        abort_unless($user->can('organizations.manage') || $actingPartner !== null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'data_protection_contact_email' => ['nullable', 'email', 'max:255'],
            'franchise_partner_id' => [
                Rule::requiredIf($user->franchise_partner_id === null),
                'nullable', 'exists:franchise_partners,id',
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $franchisePartnerId = $user->franchise_partner_id ?? $data['franchise_partner_id'];

        // Credit whoever actually created this client if they're a Partner
        // at all — not just a narrow ("isActingAsPartnerOnly") one. Unlike
        // EventController::store(), this form has no "pick a partner"
        // dropdown a merged partner (one also carrying the full
        // organizations.manage permission, see User::isActingAsPartnerOnly()'s
        // own docblock) could use instead, so losing this would leave the
        // client completely unattributed with no way to fix it afterwards.
        $creatingPartner = $user->partner;

        $organization = Organization::create([
            'franchise_partner_id' => $franchisePartnerId,
            'partner_id' => $creatingPartner?->id,
            'name' => $data['name'],
            'country' => $data['country'],
            'data_protection_contact_email' => $data['data_protection_contact_email'] ?? null,
        ]);

        $password = GeneratedPassword::make(16);

        $admin = User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($password),
            'organization_id' => $organization->id,
            'franchise_partner_id' => $franchisePartnerId,
            'email_verified_at' => now(),
        ]);

        (new AssignRole)->grant($admin, 'customer-admin', 'organization created via admin UI');

        activity()->causedBy($user)->performedOn($organization)->log('organization created');

        $emailSent = (new SendAccountCreatedEmail)->send(
            $admin,
            $password,
            'organization-admin',
            $organization->name,
            $user->locale ?? 'ro',
        );

        return redirect()->route('organizations.index')
            ->with('status', FlashMessage::t($user->locale ?? 'ro', 'organization.created', ['name' => $organization->name]))
            ->with('newAdminEmail', $admin->email)
            ->with('newAdminPassword', $password)
            ->with('newAdminEmailSent', $emailSent);
    }
}
