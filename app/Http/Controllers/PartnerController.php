<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
use App\Models\FranchisePartner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Regional resellers (see Partner's own docblock) that the agency picks
 * from when assigning an event's sales-responsible party
 * (EventController's partner_id field) — managed here exactly like
 * Organizations: the acting actor's own franchise_partner_id scopes
 * everything (TenantScope on Partner), a system operator picks one
 * explicitly on create, a franchise-partner-admin has exactly one already.
 */
class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $partners = Partner::query()->orderBy('name')->get(['id', 'name', 'country']);

        return Inertia::render('Partners/Index', [
            'partners' => $partners,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        return Inertia::render('Partners/Create', [
            'franchisePartners' => $request->user()->franchise_partner_id !== null
                ? []
                : FranchisePartner::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:255'],
            'franchise_partner_id' => [
                Rule::requiredIf($request->user()->franchise_partner_id === null),
                'nullable', 'exists:franchise_partners,id',
            ],
        ]);

        $franchisePartnerId = $request->user()->franchise_partner_id ?? $data['franchise_partner_id'];

        $accountEmailSent = null;
        $partnerUserId = null;
        $newAccountEmail = null;
        $newAccountPassword = null;

        if (filled($data['email'] ?? null)) {
            [$partnerUser, $accountEmailSent, $newAccountPassword] = $this->findOrCreateAccount(
                $data['email'],
                $data['name'],
                $franchisePartnerId,
                $request->user()->locale ?? 'ro',
            );
            $partnerUserId = $partnerUser->id;
            $newAccountEmail = $newAccountPassword !== null ? $partnerUser->email : null;
        }

        $partner = Partner::create([
            'name' => $data['name'],
            'country' => strtoupper($data['country']),
            'email' => $data['email'] ?? null,
            'user_id' => $partnerUserId,
            'franchise_partner_id' => $franchisePartnerId,
        ]);

        activity()->causedBy($request->user())->performedOn($partner)->log('partner created');

        // Same non-blocking fallback as OrganizationController::store()'s
        // own newAdminEmail/newAdminPassword: the generated password is
        // otherwise only ever sent once, by email — if that email never
        // arrives (spam filtering, a typo'd domain, SMTP hiccup), it would
        // be unrecoverable without this. Only set for a FRESH account
        // (newAccountPassword is null when reusing an existing one), same
        // as there.
        return redirect()->route('partners.index')
            ->with('accountEmailSent', $accountEmailSent)
            ->with('newAccountEmail', $newAccountEmail)
            ->with('newAccountPassword', $newAccountPassword);
    }

    /**
     * Mirrors EventController's own findOrCreatePhotographer() exactly:
     * reuse an existing account by email (no reason to issue a fresh
     * password to someone who already has an account), or create one and
     * email the credentials — only a freshly created account gets that
     * email, an existing one already knows how to log in.
     *
     * @return array{0: User, 1: bool|null, 2: string|null}
     */
    private function findOrCreateAccount(string $email, string $partnerName, int $franchisePartnerId, string $lang): array
    {
        $user = User::withoutGlobalScopes()->where('email', $email)->first();
        $emailSent = null;
        $password = null;

        if ($user === null) {
            $password = Str::password(14);

            $user = User::create([
                'name' => $partnerName,
                'email' => $email,
                'password' => Hash::make($password),
                'organization_id' => null,
                'franchise_partner_id' => $franchisePartnerId,
                'email_verified_at' => now(),
            ]);

            $emailSent = (new SendAccountCreatedEmail)->send($user, $password, 'regional-partner', $partnerName, $lang);
        }

        (new AssignRole)->grant($user, 'partner', 'regional partner account');

        return [$user, $emailSent, $password];
    }
}
