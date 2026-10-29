<?php

namespace App\Http\Controllers;

use App\Actions\Tenancy\AssignRole;
use App\Actions\Tenancy\SendAccountCreatedEmail;
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
 * AP06, organization-scoped half: a customer-admin adding staff to their
 * own organization. Deliberately a different screen from
 * OrganizationController's franchise-level "Client nou" — that one creates
 * an organization AND its first admin together; this one only ever adds a
 * user to an ALREADY-EXISTING organization, the acting admin's own. The
 * role list is the four customer-level roles from
 * RolesAndPermissionsSeeder — franchise/platform roles aren't offered here,
 * and "participant" isn't a role anyone is assigned by hand (participants
 * reach the platform through their own signed link, never an account).
 */
class UserController extends Controller
{
    private const ASSIGNABLE_ROLES = ['customer-admin', 'event-manager', 'data-protection-officer', 'photographer'];

    public function index(Request $request): Response
    {
        $this->authorizeTeamManagement($request);

        $users = User::where('organization_id', $request->user()->organization_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'isSelf' => $user->id === $request->user()->id,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeTeamManagement($request);

        return Inertia::render('Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTeamManagement($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $password = Str::password(14);
        $actor = $request->user();

        $staff = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'organization_id' => $actor->organization_id,
            'franchise_partner_id' => $actor->franchise_partner_id,
            'email_verified_at' => now(),
        ]);

        (new AssignRole)->grant($staff, $data['role'], 'added via team screen');

        $emailSent = (new SendAccountCreatedEmail)->send(
            $staff,
            $password,
            'organization-staff',
            $actor->organization->name,
            $actor->locale ?? 'ro',
        );

        return redirect()->route('users.index')
            ->with('status', FlashMessage::t($actor->locale ?? 'ro', 'user.added', ['name' => $staff->name]))
            ->with('newStaffEmail', $staff->email)
            ->with('newStaffPassword', $password)
            ->with('newStaffEmailSent', $emailSent);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTeamManagement($request);

        abort_unless($user->organization_id === $request->user()->organization_id, 404);
        abort_if($user->id === $request->user()->id, 403);

        $name = $user->name;
        $user->delete();

        return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'user.deleted', ['name' => $name]));
    }

    private function authorizeTeamManagement(Request $request): void
    {
        abort_unless($request->user()->organization_id !== null, 403);
        abort_unless($request->user()->can('users.manage'), 403);
    }
}
