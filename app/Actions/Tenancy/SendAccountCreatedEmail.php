<?php

namespace App\Actions\Tenancy;

use App\Mail\StaffAccountCreated;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The single place an account-creation email actually gets sent, used by
 * both OrganizationController@store (a client's first admin) and
 * UserController@store (a colleague added to an existing organization).
 * Never throws — a send failure must not block the account from being
 * created, same reasoning as ParticipantController@store with
 * ParticipantInvited; the caller gets a bool back to flash a fallback
 * banner with the password either way.
 */
class SendAccountCreatedEmail
{
    public function send(User $account, string $password, string $context): bool
    {
        try {
            Mail::to($account->email)->send(new StaffAccountCreated($account, $password, $context));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Account creation email failed to send', [
                'user_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
