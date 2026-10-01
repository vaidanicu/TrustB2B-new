<?php

namespace App\Actions\Tenancy;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * The single place a role is ever granted or revoked on a user, so that
 * "jede Rechteänderung protokollieren" (log every rights change — AP06,
 * roadmap §2/§8) is a guarantee of going through here, not a convention
 * that is easy to forget on one controller and not another.
 *
 * Wire this into the role-management UI once it exists (AP06/AP07) instead
 * of calling $user->assignRole()/removeRole() directly from a controller.
 */
class AssignRole
{
    public function grant(User $target, string $role, ?string $reason = null): void
    {
        $target->assignRole($role);

        $this->log('role granted', $target, $role, $reason);
    }

    public function revoke(User $target, string $role, ?string $reason = null): void
    {
        $target->removeRole($role);

        $this->log('role revoked', $target, $role, $reason);
    }

    private function log(string $event, User $target, string $role, ?string $reason): void
    {
        activity()
            ->causedBy(Auth::user())
            ->performedOn($target)
            ->withProperties(['role' => $role, 'reason' => $reason])
            ->log($event);
    }
}
