<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
 */
class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        // Organization-scoped users have their own account page for this
        // later; today's screen is built for franchise/system-level staff.
        abort_if($request->user()->organization_id !== null, 403);

        $organizations = Organization::query()
            ->orderBy('name')
            ->get(['id', 'name', 'country', 'contract_status']);

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
                'country' => $organization->country,
                'status' => $organization->contract_status,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_if($request->user()->organization_id !== null, 403);

        return Inertia::render('Organizations/Create', [
            // A franchise-partner-admin has exactly one franchise already —
            // no choice to make. Only a system operator (no franchise of
            // their own) picks one explicitly.
            'franchisePartners' => $request->user()->franchise_partner_id !== null
                ? []
                : FranchisePartner::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()->organization_id !== null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'data_protection_contact_email' => ['nullable', 'email', 'max:255'],
            'franchise_partner_id' => [
                Rule::requiredIf($request->user()->franchise_partner_id === null),
                'nullable', 'exists:franchise_partners,id',
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $franchisePartnerId = $request->user()->franchise_partner_id ?? $data['franchise_partner_id'];

        $organization = Organization::create([
            'franchise_partner_id' => $franchisePartnerId,
            'name' => $data['name'],
            'country' => $data['country'],
            'data_protection_contact_email' => $data['data_protection_contact_email'] ?? null,
        ]);

        $password = Str::password(16);

        $admin = User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($password),
            'organization_id' => $organization->id,
            'franchise_partner_id' => $franchisePartnerId,
            'email_verified_at' => now(),
        ]);

        (new AssignRole)->grant($admin, 'customer-admin', 'organization created via admin UI');

        activity()->causedBy($request->user())->performedOn($organization)->log('organization created');

        $emailSent = (new SendAccountCreatedEmail)->send(
            $admin,
            $password,
            "Ai fost desemnat admin pentru organizația {$organization->name}.",
        );

        return redirect()->route('organizations.index')
            ->with('status', "Organizație creată: {$organization->name}")
            ->with('newAdminEmail', $admin->email)
            ->with('newAdminPassword', $password)
            ->with('newAdminEmailSent', $emailSent);
    }
}
