<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Only the create *form* exists so far — no Event model, no store route.
 * Building the UI before the data layer was a deliberate ordering choice;
 * the page says so instead of silently accepting a submit that goes
 * nowhere. Wire this up for real alongside AP07/08.
 */
class EventController extends Controller
{
    public function create(Request $request): Response
    {
        abort_unless($request->user()->organization_id !== null, 403);

        return Inertia::render('Events/Create');
    }
}
