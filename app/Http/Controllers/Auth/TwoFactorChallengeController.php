<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UI only, not yet wired into the real login pipeline. Breeze's own
 * AuthenticatedSessionController authenticates directly with Auth::attempt()
 * and never consults Fortify's two-factor state, so a user with 2FA enabled
 * is not actually sent here today — config/fortify.php turns the feature on,
 * but completing the wire-up (routing login through Fortify's pipeline
 * instead of Breeze's) is backend work, deliberately left for after the
 * frontend pass per the current work order.
 */
class TwoFactorChallengeController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/TwoFactorChallenge');
    }
}
