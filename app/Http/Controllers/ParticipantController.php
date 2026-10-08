<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faza 2 / Pachet A (roadmap §12, §3): invitație, RSVP, preferințe separate.
 * No Invitation/Participant model yet (AP10-13, scheduled after the
 * frontend pass) — these are reachable preview routes with one sample
 * event, same honest pattern as EventController@create. Wire these to
 * real signed, per-recipient tokens (A-03) when the backend lands;
 * nothing here should be mistaken for that yet.
 */
class ParticipantController extends Controller
{
    private const SAMPLE_EVENT = [
        'organizer' => 'Hotel Aurora',
        'name' => 'Turneu de fotbal — clienți 2026',
        'date' => '14 septembrie 2026',
        'location' => 'Teren Sportiv Aurora, Cluj-Napoca',
        'documentVersion' => '1.2',
        'linkExpires' => '10 septembrie 2026',
    ];

    public function invitation(): Response
    {
        return Inertia::render('Participants/Invitation', [
            'event' => self::SAMPLE_EVENT,
        ]);
    }

    public function confirmation(Request $request): Response
    {
        return Inertia::render('Participants/Confirmation', [
            'event' => self::SAMPLE_EVENT,
            'participantName' => $request->string('nume')->toString() ?: 'Andrei',
            'attending' => $request->boolean('particip', true),
            'photoConsent' => $request->boolean('poze', true),
        ]);
    }

    public function statusBoard(Request $request): Response
    {
        abort_unless($request->user()->organization_id !== null, 403);

        return Inertia::render('Participants/StatusBoard', [
            'event' => self::SAMPLE_EVENT,
        ]);
    }
}
