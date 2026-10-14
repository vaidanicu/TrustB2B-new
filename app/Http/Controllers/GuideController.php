<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-app walkthrough for both acting scopes (franchise/system staff vs. an
 * organization's own staff) — requested directly: a step-by-step helper
 * inside the platform instead of explaining the flow over chat every time.
 * Purely static content, scoped client-side from the same auth.user shared
 * prop AppLayout already reads; nothing here needs its own data.
 */
class GuideController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Guide/Index');
    }
}
