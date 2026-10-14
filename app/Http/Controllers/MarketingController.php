<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * trustbridgeb2b.com and app.trustbridgeb2b.com point at the same Laravel
 * codebase and the same document root — there is no separate "marketing
 * site" deployment. This controller is what actually splits them: the
 * bare domain always gets the pitch (ro/en/de); the app domain's "/" never
 * shows it, it goes straight to login or the dashboard. Both domains are
 * configured with the identical document root (trustbridge/public) in
 * cPanel — this check is the only thing telling them apart.
 */
class MarketingController extends Controller
{
    private const LOCALES = ['ro', 'en', 'de'];

    public function show(Request $request, string $locale = 'ro'): Response|RedirectResponse
    {
        if ($request->getHost() === $this->appHost()) {
            return $request->user() ? redirect()->route('dashboard') : redirect()->route('login');
        }

        abort_unless(in_array($locale, self::LOCALES, true), 404);

        return Inertia::render('Welcome', ['locale' => $locale]);
    }

    private function appHost(): ?string
    {
        return parse_url(config('app.url'), PHP_URL_HOST);
    }
}
