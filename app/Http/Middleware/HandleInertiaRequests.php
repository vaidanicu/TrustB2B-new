<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing(['organization', 'franchisePartner']);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    ...$user->only(['id', 'name', 'email', 'locale', 'organization_id', 'franchise_partner_id']),
                    'roles' => $user->getRoleNames(),
                    'organization' => $user->organization?->only(['id', 'name']),
                    'franchise_partner' => $user->franchisePartner?->only(['id', 'name', 'region_code']),
                ] : null,
            ],
            // Shares whatever a controller actually flashed on the PREVIOUS
            // request (redirect()->with('status', ...), ->with('invitationUrl', ...),
            // etc.) instead of a hand-picked key list — the previous
            // 'status'-only list silently dropped invitationUrl/
            // newAdminEmail/newAdminPassword, so those banners never
            // rendered even though the backend set them correctly.
            // '_flash.old' (not '_flash.new') is deliberate: StartSession's
            // ageFlashData() already ran before this middleware, moving the
            // flashed keys from 'new' to 'old' for exactly this request —
            // 'new' has already been reset to empty by the time we get here.
            'flash' => fn () => collect($request->session()->get('_flash.old', []))
                ->mapWithKeys(fn (string $key) => [$key => $request->session()->get($key)])
                ->all(),
        ];
    }
}
