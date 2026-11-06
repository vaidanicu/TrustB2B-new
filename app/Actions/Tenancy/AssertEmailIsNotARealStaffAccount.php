<?php

namespace App\Actions\Tenancy;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Shared guard for every "reuse an account by email, or create one" flow
 * that additively grants a narrow role (EventController's own
 * findOrCreatePhotographer()/notifyClientContact(), PartnerController's
 * findOrCreateAccount()): refuses up front when that email already belongs
 * to a real organization-staff account (User::hasNoRealOrganizationStaffRole())
 * instead of letting the reuse silently widen that account's roles.
 *
 * Reported live twice now — a customer-admin got additively turned into a
 * photographer (same email, an event's own photographer_email field), and
 * separately into a Partner reseller (same email, Partner's own email
 * field) — both times leaving the account's real dashboard/nav access
 * either narrowed or, for the Partner case, outright broken (Partner's own
 * TenantScope('organization_id') can never resolve the relation for an
 * account that already has an organization_id, since a Partner row's own
 * organization_id is always null by design).
 */
class AssertEmailIsNotARealStaffAccount
{
    public function __invoke(?string $email, string $field, string $roleLabel): void
    {
        if (blank($email)) {
            return;
        }

        $account = User::withoutGlobalScopes()->where('email', $email)->first();

        if ($account === null || $account->hasNoRealOrganizationStaffRole()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => "Acest email apartine deja contului de organizatie al lui {$account->name} — nu poate fi folosit si ca {$roleLabel}. Foloseste alt email.",
        ]);
    }
}
