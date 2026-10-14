<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The account-level language preference for the authenticated app, distinct
 * from the marketing page's own ro/en/de (MarketingController) which needs
 * no account at all. One PATCH, no page of its own — the switcher lives in
 * AppLayout and this just persists the choice and redirects back so the
 * current page re-renders in the new language.
 */
class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(['ro', 'en', 'de'])],
        ]);

        $request->user()->update(['locale' => $data['locale']]);

        return back();
    }
}
