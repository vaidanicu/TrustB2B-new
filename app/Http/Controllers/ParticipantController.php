<?php

namespace App\Http\Controllers;

use App\Mail\ParticipantInvited;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * AP10-13. invitation()/confirmation() below are the untouched static demo
 * (one sample event, no database row, no signature) that the marketing
 * page links to — see routes/web.php for why that stays separate from
 * show()/respond(), the real per-recipient flow.
 *
 * show()/respond() load Participant via withoutGlobalScopes()->findOrFail():
 * deliberate, not an oversight. The visitor has no session and no tenant
 * context, so the normal BelongsToOrganization scope would see "no
 * context" and deny everything (TenantScope::apply) — correct for every
 * other access path, wrong for a participant following their own signed
 * link. The 'signed' route middleware is what actually authorizes reaching
 * a specific row; withoutGlobalScopes() only lets that already-authorized
 * lookup through.
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

    public function statusBoard(Request $request, Event $event): Response
    {
        abort_unless($request->user()->organization_id !== null, 403);

        $participants = $event->participants()->orderByDesc('created_at')->get();

        return Inertia::render('Participants/StatusBoard', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->starts_at->format('d.m.Y'),
                'location' => $event->location,
            ],
            'participants' => $participants->map(fn (Participant $participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'status' => $participant->status,
                'photoConsent' => $participant->photo_consent,
                'respondedAt' => $participant->responded_at?->format('d M.'),
            ]),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->organization_id !== null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('participants')->where('event_id', $event->id)],
        ]);

        $participant = $event->participants()->create([
            ...$data,
            'status' => 'invited',
            // Business expiry shown to the participant ("linkExpires"); also
            // what signs the URL below, so the two can never disagree.
            'link_expires_at' => $event->starts_at->copy()->subDay()->endOfDay(),
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($participant)
            ->log('participant invited');

        $invitationUrl = URL::temporarySignedRoute(
            'participants.show',
            $participant->link_expires_at,
            ['participant' => $participant->id],
        );

        // The link banner stays regardless of whether this succeeds — a
        // participant creation must never fail just because SMTP is
        // temporarily unreachable, and the organizer can always copy/send
        // the link by hand either way.
        $emailSent = true;

        try {
            Mail::to($participant->email)->send(new ParticipantInvited($participant, $invitationUrl));
        } catch (\Throwable $e) {
            $emailSent = false;

            Log::warning('Participant invitation email failed to send', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('status', "Invitație creată pentru {$participant->name}")
            ->with('invitationUrl', $invitationUrl)
            ->with('invitationEmailSent', $emailSent);
    }

    public function show(Request $request, int $participant): Response
    {
        // Event carries its own BelongsToOrganization scope too — eager
        // loading it without bypassing that would silently return null
        // under "no tenant context", same reasoning as Participant's own
        // withoutGlobalScopes() above.
        $participant = Participant::withoutGlobalScopes()
            ->with(['event' => fn ($query) => $query->withoutGlobalScopes()])
            ->findOrFail($participant);

        if ($participant->responded_at !== null) {
            return $this->renderConfirmation($participant);
        }

        if ($participant->status === 'invited') {
            $participant->forceFill(['status' => 'opened'])->save();
        }

        return Inertia::render('Participants/Invitation', [
            'event' => $this->eventPayload($participant),
            'participantName' => $participant->name,
            'signedUrl' => $request->fullUrl(),
        ]);
    }

    public function respond(Request $request, int $participant): RedirectResponse
    {
        $participant = Participant::withoutGlobalScopes()->findOrFail($participant);

        // Resubmitting an already-recorded response (double-click, back
        // button) is harmless and idempotent — redirect straight to the
        // confirmation show() renders for a responded participant, without
        // overwriting what was already saved.
        if ($participant->responded_at === null) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'attending' => ['required', 'boolean'],
                'photo_consent' => ['nullable', 'boolean'],
            ]);

            $participant->forceFill([
                'name' => $data['name'],
                'status' => $data['attending'] ? 'attending' : 'declined',
                'photo_consent' => $data['attending'] ? $request->boolean('photo_consent') : null,
                'responded_at' => now(),
            ])->save();

            activity()->performedOn($participant)->log('participant responded');
        }

        return redirect($request->fullUrl());
    }

    private function renderConfirmation(Participant $participant): Response
    {
        $attending = $participant->status === 'attending';

        return Inertia::render('Participants/Confirmation', [
            'event' => $this->eventPayload($participant),
            'participantName' => $participant->name,
            'attending' => $attending,
            'photoConsent' => (bool) $participant->photo_consent,
            // Whoever attended can see the shared album, regardless of their
            // own photo_consent — that only governs whether THEY appear in
            // it, not whether they can look. Declined participants never
            // see this: they weren't there.
            'photos' => $attending
                ? $participant->event->getMedia('gallery')
                    ->filter(fn (Media $media) => (bool) $media->getCustomProperty('published', false))
                    ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->getUrl()])
                    ->values()
                : [],
        ]);
    }

    private function eventPayload(Participant $participant): array
    {
        $event = $participant->event;

        return [
            'organizer' => $event->organizer ?? $event->organization?->name ?? '',
            'name' => $event->name,
            'date' => $event->starts_at->format('d F Y'),
            'location' => $event->location ?? '',
            'documentVersion' => '1.0',
            'linkExpires' => $participant->link_expires_at->format('d F Y'),
        ];
    }
}
