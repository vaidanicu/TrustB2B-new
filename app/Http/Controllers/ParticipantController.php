<?php

namespace App\Http\Controllers;

use App\Mail\ParticipantInvited;
use App\Models\Event;
use App\Models\Participant;
use App\Support\FlashMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $this->authorizeParticipantManagement($request);
        abort_if($event->is_restaurant, 404);

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
                'deletionRequested' => $participant->deletion_requested_at !== null,
                'checkedIn' => $participant->checked_in_at !== null,
            ]),
            'checkinUrl' => route('participants.checkin', $event),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeParticipantManagement($request);
        abort_if($event->is_restaurant, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('participants')->where('event_id', $event->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $participant = $event->participants()->create([
            ...$data,
            // Explicit, not auto-stamped from the acting user's own tenant
            // context (BelongsToOrganization's default): a franchise/
            // platform actor managing participants here (see
            // authorizeParticipantManagement()) has no organization_id of
            // their own to stamp — the event's is always the right one,
            // regardless of who's inviting.
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
            // Business expiry shown to the participant ("linkExpires"); also
            // what signs the URL below, so the two can never disagree.
            'link_expires_at' => $event->starts_at->copy()->subDay()->endOfDay(),
            // Handed to event staff at the door (see checkin()/checkinStore()
            // below) to mark attendance — generated now, not on response/
            // acceptance, since it's sent out with the original invitation.
            'checkin_code' => $this->generateCheckinCode($event),
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
            Mail::to($participant->email)->send(new ParticipantInvited($participant, $invitationUrl, $request->user()->locale ?? 'ro'));
        } catch (\Throwable $e) {
            $emailSent = false;

            Log::warning('Participant invitation email failed to send', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'participant.invited', ['name' => $participant->name]))
            ->with('invitationUrl', $invitationUrl)
            ->with('invitationEmailSent', $emailSent);
    }

    public function destroy(Request $request, Event $event, Participant $participant): RedirectResponse
    {
        $this->authorizeParticipantManagement($request);
        abort_unless($participant->event_id === $event->id, 404);

        $name = $participant->name;
        $wasRequested = $participant->deletion_requested_at !== null;

        activity()
            ->causedBy($request->user())
            ->performedOn($participant)
            ->log($wasRequested ? 'deletion request approved, participant removed' : 'participant removed');

        $participant->delete();

        return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'participant.deleted', ['name' => $name]));
    }

    public function denyDeletionRequest(Request $request, Event $event, Participant $participant): RedirectResponse
    {
        $this->authorizeParticipantManagement($request);
        abort_unless($participant->event_id === $event->id, 404);

        $participant->forceFill(['deletion_requested_at' => null])->save();

        activity()
            ->causedBy($request->user())
            ->performedOn($participant)
            ->log('deletion request declined');

        return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'participant.deletion_denied', ['name' => $participant->name]));
    }

    /**
     * The door, on the day of the event: staff type/paste the code from the
     * participant's invitation email (checkin_code, generated in store())
     * and it marks them present in real time. Deliberately a single
     * always-present form rather than requiring a camera/scanner —
     * shared-host JS tooling constraints aside, typing a short code works
     * on literally any device, including a borrowed one.
     */
    public function checkin(Request $request, Event $event): Response
    {
        $this->authorizeParticipantManagement($request);
        abort_if($event->is_restaurant, 404);

        return Inertia::render('Participants/Checkin', [
            'event' => ['id' => $event->id, 'name' => $event->name],
        ]);
    }

    public function checkinStore(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeParticipantManagement($request);
        abort_if($event->is_restaurant, 404);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $participant = $event->participants()
            ->whereRaw('upper(checkin_code) = ?', [strtoupper($data['code'])])
            ->first();

        if ($participant === null) {
            throw ValidationException::withMessages(['code' => 'Cod invalid pentru acest eveniment.']);
        }

        if ($participant->checked_in_at !== null) {
            return back()->with('checkinStatus', 'already')->with('checkinParticipantName', $participant->name);
        }

        $participant->forceFill(['checked_in_at' => now()])->save();

        activity()->causedBy($request->user())->performedOn($participant)->log('participant checked in');

        return back()->with('checkinStatus', 'success')->with('checkinParticipantName', $participant->name);
    }

    private function generateCheckinCode(Event $event): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while ($event->participants()->where('checkin_code', $code)->exists());

        return $code;
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

        // Takes priority over both other states — once requested, there is
        // nothing else meaningful to show the participant regardless of
        // whether they'd already responded.
        if ($participant->deletion_requested_at !== null) {
            return Inertia::render('Participants/DeletionRequested', [
                'event' => $this->eventPayload($participant),
                'participantName' => $participant->name,
            ]);
        }

        if ($participant->responded_at !== null) {
            return $this->renderConfirmation($participant, $request->fullUrl());
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
        // Eager-loaded with its own scope bypassed too — same reasoning as
        // show(): an anonymous participant request has no tenant context,
        // and the GDPR-acceptance check below is the first thing in this
        // method that actually touches $participant->event.
        $participant = Participant::withoutGlobalScopes()
            ->with(['event' => fn ($query) => $query->withoutGlobalScopes()])
            ->findOrFail($participant);

        // Resubmitting an already-recorded response (double-click, back
        // button) is harmless and idempotent — redirect straight to the
        // confirmation show() renders for a responded participant, without
        // overwriting what was already saved.
        if ($participant->responded_at === null && $participant->deletion_requested_at === null) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'attending' => ['required', 'boolean'],
                'photo_consent' => ['nullable', 'boolean'],
            ]);

            // Only gate on acceptance when the event actually has contract
            // text to accept — events created before this existed have none,
            // and requiring a checkbox for text that was never shown would
            // just lock out every participant already mid-flow on one of
            // those. New events always have text (EventController::store()
            // requires it unless is_restaurant), so this is effectively
            // "always required" going forward.
            $requiresGdprAcceptance = $data['attending']
                && ! $participant->event->is_restaurant
                && filled($participant->event->gdpr_contract_text);

            if ($requiresGdprAcceptance && ! $request->boolean('gdpr_accepted')) {
                throw ValidationException::withMessages([
                    'gdpr_accepted' => 'Trebuie să confirmi că ești de acord cu termenii de mai sus pentru a participa.',
                ]);
            }

            $participant->forceFill([
                'name' => $data['name'],
                'status' => $data['attending'] ? 'attending' : 'declined',
                'photo_consent' => $data['attending'] ? $request->boolean('photo_consent') : null,
                'responded_at' => now(),
                'gdpr_accepted_at' => $data['attending'] && $request->boolean('gdpr_accepted') ? now() : null,
                'gdpr_accepted_ip' => $data['attending'] && $request->boolean('gdpr_accepted') ? $request->ip() : null,
            ])->save();

            activity()->performedOn($participant)->log('participant responded');
        }

        return redirect($request->fullUrl());
    }

    /**
     * Shares the same signed URL as show()/respond() (PATCH instead of
     * GET/POST) so the participant's page never needs a second signature —
     * the "deletion request" link just reuses the signedUrl prop already on
     * the page.
     */
    public function requestDeletion(Request $request, int $participant): RedirectResponse
    {
        $participant = Participant::withoutGlobalScopes()->findOrFail($participant);

        if ($participant->deletion_requested_at === null) {
            $participant->forceFill(['deletion_requested_at' => now()])->save();

            activity()->performedOn($participant)->log('participant requested deletion');
        }

        return redirect($request->fullUrl());
    }

    private function renderConfirmation(Participant $participant, ?string $signedUrl = null): Response
    {
        $attending = $participant->status === 'attending';

        return Inertia::render('Participants/Confirmation', [
            'event' => $this->eventPayload($participant),
            'participantName' => $participant->name,
            'attending' => $attending,
            'photoConsent' => (bool) $participant->photo_consent,
            'gdprAccepted' => $participant->gdpr_accepted_at !== null,
            'signedUrl' => $signedUrl,
            // Whoever attended can see the shared album, regardless of their
            // own photo_consent — that only governs whether THEY appear in
            // it, not whether they can look. Declined participants never
            // see this: they weren't there.
            'photos' => $attending
                ? $participant->event->getMedia('gallery')
                    ->filter(fn (Media $media) => (bool) $media->getCustomProperty('published', false))
                    ->map(fn (Media $media) => ['id' => $media->id, 'url' => $media->getUrl(), 'fileName' => $media->file_name])
                    ->values()
                : [],
        ]);
    }

    /**
     * Participant management (invite/view/remove) is organization-scoped
     * staff's day-to-day job, but the agency (franchise/platform —
     * 'events.manage', same permission EventController gates on) can do it
     * too: they're the ones who set the event and its GDPR contract up in
     * the first place, and shouldn't have to depend on the client's own
     * account existing or being available to actually send invitations.
     */
    private function authorizeParticipantManagement(Request $request): void
    {
        abort_unless($request->user()->organization_id !== null || $request->user()->can('events.manage'), 403);
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
            'gdprContractText' => $event->gdpr_contract_text ?? '',
        ];
    }
}
