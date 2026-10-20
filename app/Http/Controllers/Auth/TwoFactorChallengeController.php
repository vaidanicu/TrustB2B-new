<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The view half of the challenge; the POST that actually verifies the code
 * is Fortify's own TwoFactorAuthenticatedSessionController@store, already
 * registered at the same URI under the 'two-factor.login.store' name (see
 * routes/web.php for why this GET is registered here rather than left to
 * Fortify). LoginRequest@authenticate is what actually sends a user here,
 * by stashing 'login.id' in session instead of logging them in directly
 * when two-factor is confirmed on the account.
 */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }
}
