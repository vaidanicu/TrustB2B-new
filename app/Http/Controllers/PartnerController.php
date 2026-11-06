<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssertEmailIsNotARealStaffAccount;
use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
use App\Models\FranchisePartner;
use App\Models\Partner;
use App\Models\User;
use App\Support\FlashMessage;
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

        $partners = Partner::query()->orderBy('name')->get(['id', 'name', 'country', 'user_id']);

        return Inertia::render('Partners/Index', [
            'partners' => $partners->map(fn (Partner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'country' => $partner->country,
                'hasAccount' => $partner->user_id !== null,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $isSystemOperator = $request->user()->franchise_partner_id === null;

        return Inertia::render('Partners/Create', [
            // isSystemOperator distinguishes "no choice to make, exactly
            // one already" from "a system operator who simply has zero
            // franchise partners yet" — both would otherwise show up as the
            // same empty array, and the latter still needs the "Franciză
            // nouă" option to bootstrap the very first one.
            'franchisePartners' => $isSystemOperator ? FranchisePartner::query()->orderBy('name')->get(['id', 'name']) : [],
            'isSystemOperator' => $isSystemOperator,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:255'],
            // 'new' is a sentinel, not a real id — see the "new franchise
            // partner" block below for why this can't just be
            // 'exists:franchise_partners,id' like every other foreign-key
            // pick in this app.
            'franchise_partner_id' => [
                Rule::requiredIf($request->user()->franchise_partner_id === null),
                'nullable',
            ],
            'new_franchise_name' => ['required_if:franchise_partner_id,new', 'nullable', 'string', 'max:255'],
            'new_franchise_region_code' => ['required_if:franchise_partner_id,new', 'nullable', 'string', 'max:255', 'unique:franchise_partners,region_code'],
            'new_franchise_country' => ['required_if:franchise_partner_id,new', 'nullable', 'string', 'size:2'],
            'new_franchise_contact_email' => ['required_if:franchise_partner_id,new', 'nullable', 'email', 'max:255'],
        ]);

        // Reported live: a real customer-admin's email was reused here too
        // (same pattern as EventController's photographer_email/
        // client_contact_email), additively granting it 'partner' — and,
        // unlike the photographer/client-contact case, this one doesn't
        // just narrow the account's dashboard: Partner's own
        // TenantScope('organization_id') can never resolve $user->partner
        // for an account that already has an organization_id (a Partner
        // row's own organization_id is always null by design), so the
        // account ends up with a dead-end "Clienți" nav link that 403s.
        (new AssertEmailIsNotARealStaffAccount)($data['email'] ?? null, 'email', 'partener');

        // Reported live: a system operator creating the agency's first
        // Partner for a brand-new region had no way to add that region's
        // own FranchisePartner at all — no UI anywhere ever created one,
        // so the dropdown here (and on Organizations/Create) stayed stuck
        // on whichever franchise(s) already existed. In this business a
        // Partner and its franchise are the same real-world company, so
        // "Partener nou" is where a new one gets created too, instead of a
        // separate, standalone "Franchise Partners" admin screen.
        if ($request->user()->franchise_partner_id !== null) {
            $franchisePartnerId = $request->user()->franchise_partner_id;
        } elseif ($data['franchise_partner_id'] === 'new') {
            $franchisePartner = FranchisePartner::create([
                'name' => $data['new_franchise_name'],
                'region_code' => strtoupper($data['new_franchise_region_code']),
                'country' => strtoupper($data['new_franchise_country']),
                'contact_email' => $data['new_franchise_contact_email'],
            ]);

            activity()->causedBy($request->user())->performedOn($franchisePartner)->log('franchise partner created');

            $franchisePartnerId = $franchisePartner->id;
        } else {
            // Same reasoning as Organization::findOrFail($data['organization_id'])
            // in EventController::store(): 404s the same way it would for a
            // bare id that doesn't exist at all, rather than a bespoke
            // 'exists' validation message.
            $franchisePartnerId = FranchisePartner::findOrFail($data['franchise_partner_id'])->id;
        }

        $accountEmailSent = null;
        $partnerUserId = null;
        $newAccountEmail = null;
        $newAccountPassword = null;
        $reusedExistingAccount = false;

        if (filled($data['email'] ?? null)) {
            [$partnerUser, $accountEmailSent, $newAccountPassword] = $this->findOrCreateAccount(
                $data['email'],
                $data['name'],
                $franchisePartnerId,
                $request->user()->locale ?? 'ro',
            );
            $partnerUserId = $partnerUser->id;
            $newAccountEmail = $newAccountPassword !== null ? $partnerUser->email : null;
            // An email that already belonged to an account gets no new
            // password and no email at all (an existing account already
            // knows how to log in) — surfaced explicitly rather than
            // silently, reported: an admin assuming a no-banner result
            // meant the email send had silently failed, when really
            // nothing was ever attempted.
            $reusedExistingAccount = $newAccountPassword === null;
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
            ->with('newAccountPassword', $newAccountPassword)
            ->with('reusedExistingAccount', $reusedExistingAccount ? $data['email'] : null);
    }

    public function edit(Request $request, Partner $partner): Response
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        return Inertia::render('Partners/Edit', [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'country' => $partner->country,
                'email' => $partner->email,
            ],
        ]);
    }

    /**
     * Name/country are plain field updates. Email is the delicate part —
     * changing it re-runs the exact same findOrCreateAccount() flow as
     * store() (same password/reused-account fallback banners on redirect),
     * and the PREVIOUS linked account (if different from the new one, or
     * if the email was removed entirely) has its now-dangling 'partner'
     * role revoked, same reasoning as destroy(). franchise_partner_id is
     * deliberately not editable here — immutable after creation, same as
     * every other tenancy-boundary field in this app.
     */
    public function update(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $newEmail = $data['email'] ?? null;
        $previousUser = $partner->user;

        $accountEmailSent = null;
        $newAccountEmail = null;
        $newAccountPassword = null;
        $reusedExistingAccount = false;
        $partnerUserId = $partner->user_id;

        if ($newEmail !== $partner->email) {
            if (filled($newEmail)) {
                // Same guard as store() — see its own comment.
                (new AssertEmailIsNotARealStaffAccount)($newEmail, 'email', 'partener');

                [$partnerUser, $accountEmailSent, $newAccountPassword] = $this->findOrCreateAccount(
                    $newEmail,
                    $data['name'],
                    $partner->franchise_partner_id,
                    $request->user()->locale ?? 'ro',
                );
                $partnerUserId = $partnerUser->id;
                $newAccountEmail = $newAccountPassword !== null ? $partnerUser->email : null;
                $reusedExistingAccount = $newAccountPassword === null;

                if ($previousUser !== null && $previousUser->id !== $partnerUser->id) {
                    (new AssignRole)->revoke($previousUser, 'partner', 'partner email changed');
                }
            } else {
                if ($previousUser !== null) {
                    (new AssignRole)->revoke($previousUser, 'partner', 'partner email removed');
                }
                $partnerUserId = null;
            }
        }

        $partner->forceFill([
            'name' => $data['name'],
            'country' => strtoupper($data['country']),
            'email' => $newEmail,
            'user_id' => $partnerUserId,
        ])->save();

        activity()->causedBy($request->user())->performedOn($partner)->log('partner updated');

        return redirect()->route('partners.index')
            ->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'partner.updated', ['name' => $partner->name]))
            ->with('accountEmailSent', $accountEmailSent)
            ->with('newAccountEmail', $newAccountEmail)
            ->with('newAccountPassword', $newAccountPassword)
            ->with('reusedExistingAccount', $reusedExistingAccount ? $newEmail : null);
    }

    /**
     * Only the Partner row itself is removed — events.partner_id is
     * nullOnDelete() (create_partners_table/the later migration), so any
     * event that had this partner responsible simply loses the
     * attribution, nothing cascades. The linked login account (if any)
     * is NEVER deleted here: it may be the very same account as an admin
     * also acting as this Partner (e.g. "MM Kft." for HU,
     * User::isActingAsPartnerOnly()) — only the now-dangling 'partner'
     * role is revoked from it, via AssignRole, the one sanctioned place
     * that happens.
     */
    public function destroy(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        $name = $partner->name;
        $linkedUser = $partner->user;

        activity()->causedBy($request->user())->performedOn($partner)->log('partner deleted');

        $partner->delete();

        if ($linkedUser !== null) {
            (new AssignRole)->revoke($linkedUser, 'partner', 'partner deleted');
        }

        return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'partner.deleted', ['name' => $name]));
    }

    /**
     * A self-service alternative to a tinker script every time a
     * partner's password needs recovering — reported, in the middle of
     * exactly that happening by hand over SSH. Generates a fresh one and
     * shows it once on screen (same banner as create()/update()'s own
     * fallback), without depending on email delivery at all — the admin
     * hands it to the partner directly (phone, WhatsApp, in person).
     */
    public function resetPassword(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($request->user()->can('partners.manage'), 403);

        if ($partner->user === null) {
            return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'partner.no_account', ['name' => $partner->name]));
        }

        $password = Str::password(14);
        $partner->user->forceFill(['password' => Hash::make($password)])->save();

        activity()->causedBy($request->user())->performedOn($partner->user)->log('partner password reset');

        return redirect()->route('partners.index')
            ->with('newAccountEmail', $partner->user->email)
            ->with('newAccountPassword', $password)
            ->with('passwordReset', true);
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
