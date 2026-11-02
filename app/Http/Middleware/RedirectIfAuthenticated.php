<?php

namespace App\Http\Middleware;

use App\Support\FlashMessage;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as BaseRedirectIfAuthenticated;
use Illuminate\Http\Request;

/**
 * An already-logged-in session hitting /login (e.g. the "ANMELDEN" button
 * in a newly-created account's email, clicked by the admin who just
 * created it, still signed in as themselves) silently bounced to the
 * clicker's OWN dashboard with zero explanation — reported: "ich komme
 * nicht auf eine Anmeldeseite, sondern hierhin, und dann weiß ein
 * Fotograf sicher nicht weiter." Flashes who they're actually signed in
 * as before the framework's own default redirect runs, so the landing
 * page's existing flash banner explains it instead of leaving a silent,
 * confusing jump.
 */
class RedirectIfAuthenticated extends BaseRedirectIfAuthenticated
{
    protected function redirectTo(Request $request): ?string
    {
        $user = $request->user();

        if ($user !== null) {
            $request->session()->flash('status', FlashMessage::t($user->locale ?? 'ro', 'auth.already_authenticated', [
                'name' => $user->name,
                'email' => $user->email,
            ]));
        }

        return parent::redirectTo($request);
    }
}
