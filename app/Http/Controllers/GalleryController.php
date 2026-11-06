<?php

namespace App\Http\Controllers;

use App\Mail\PhotographerConfirmedAccess;
use App\Mail\PhotoRemovalRequested;
use App\Models\Event;
use App\Models\User;
use App\Support\FlashMessage;
use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The event gallery, two sides:
 *
 * - Organizer side (index/store/publish/unpublish/destroy/approveRemoval/
 *   denyRemoval): upload (media.upload: the event's one designated
 *   photographer — see authorizeUpload() — or customer-admin/event-manager)
 *   and moderate visibility (media.publish) are still separate permissions,
 *   seeded from day one in RolesAndPermissionsSeeder for "Fotograf: nur
 *   Upload... kein Teilnehmerdatenexport" (roadmap §2) — a photographer can
 *   add photos but never moderate the gallery. Unlike the original design,
 *   every upload is published the moment it lands (owner request: trust the
 *   designated uploader, don't gate on a manual review step) — publish()/
 *   unpublish() are now an after-the-fact hide/show toggle for
 *   media.publish holders, not a pre-publication gate.
 * - Public side (publicGallery/requestRemoval): reached via one signed,
 *   per-EVENT (not per-participant) link/QR code — anyone who has it, not
 *   just someone who went through the invitation flow, can view published
 *   photos and ask for one to be taken down, with a reason. Nothing is
 *   deleted automatically: media.moderate-deletion-requests (already seeded
 *   for customer-admin/data-protection-officer, previously unused) reviews
 *   and decides.
 */
class GalleryController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $user = $request->user();
        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        // events.manage holders (franchise/platform — the only ones who can
        // create an event at all, see EventController) couldn't reach this
        // page before: a restaurant-type event has nothing else to click
        // into (no participants/check-in), leaving no way to even see the
        // public gallery link/QR they need to hand the client. They still
        // can't upload/publish themselves (canUpload/canPublish below stay
        // false for them) — only that they can see the page. A Partner
        // reaches it too, for its own event, with canUpload/
        // canModerateRemovals below reflecting what it can actually do.
        abort_unless(
            $user->can('media.upload') || $user->can('media.publish') || $user->can('events.manage') || $isOwnPartnerEvent,
            403
        );

        // Reported: a partner/admin needs real proof the designated
        // photographer actually saw their access instructions before the
        // event — "email opened" can't be trusted (many clients block
        // remote images; Gmail proxies them regardless of whether a human
        // read anything). This fires once, the first time that exact
        // account opens this exact event's gallery.
        if ($event->photographer_user_id === $user->id && $event->photographer_confirmed_at === null) {
            $event->forceFill(['photographer_confirmed_at' => now()])->save();
            $this->notifyPhotographerConfirmed($event, $user);
        }

        $publicGalleryUrl = $this->publicGalleryUrl($event);

        // Restaurant events have no participants.status page (404s, by
        // design — see ParticipantController::statusBoard()) to come back
        // to, and a franchise/platform viewer with no organization_id of
        // their own never reaches that page at all regardless of event
        // type — route both cases back to the client's event list instead.
        // organization_id (the raw column), not the organization() relation:
        // a cross-client photographer (resolveRouteBinding's own carve-out)
        // has no visibility into Organization at all — its own TenantScope
        // only ever matches their single home organization — so loading the
        // relation here would silently come back null and route() would
        // throw trying to build a URL from it.
        $backUrl = $event->is_restaurant || $user->organization_id === null
            ? route('organizations.events', $event->organization_id)
            : route('participants.status', $event);

        return Inertia::render('Events/Gallery', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'backUrl' => $backUrl,
            'canUpload' => $this->canUpload($user, $event),
            'canPublish' => $user->can('media.publish'),
            'canModerateRemovals' => $user->can('media.moderate-deletion-requests') || $isOwnPartnerEvent,
            'photos' => $event->getMedia('gallery')->map(fn (Media $media) => $this->photoPayload($media))->values(),
            'publicGalleryUrl' => $publicGalleryUrl,
            'publicGalleryQrSvg' => QrCode::svg($publicGalleryUrl),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeUpload($request, $event);

        $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['mimes:jpeg,png,webp,mp4,mov,webm', 'max:102400'],
        ]);

        foreach ($request->file('photos') as $file) {
            $event->addMedia($file)
                ->withCustomProperties(['published' => true, 'uploaded_by' => $request->user()->id])
                ->toMediaCollection('gallery');
        }

        activity()->causedBy($request->user())->performedOn($event)->log('photos uploaded');

        return back()->with('status', FlashMessage::t($request->user()->locale ?? 'ro', 'gallery.uploaded'));
    }

    public function publish(Request $request, Event $event, Media $media): RedirectResponse
    {
        abort_unless($request->user()->can('media.publish'), 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        $media->setCustomProperty('published', true);
        $media->save();

        activity()->causedBy($request->user())->performedOn($event)->log('photo published');

        return back();
    }

    /**
     * The other half of publish() now that every upload goes live
     * immediately — lets media.publish holders hide a photo (e.g. one that
     * shouldn't have gone up) without deleting it outright.
     */
    public function unpublish(Request $request, Event $event, Media $media): RedirectResponse
    {
        abort_unless($request->user()->can('media.publish'), 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        $media->setCustomProperty('published', false);
        $media->save();

        activity()->causedBy($request->user())->performedOn($event)->log('photo hidden');

        return back();
    }

    public function destroy(Request $request, Event $event, Media $media): RedirectResponse
    {
        abort_unless($this->canUpload($request->user(), $event), 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        $media->delete();

        return back();
    }

    /**
     * A Partner may also approve/deny a removal request, but only for its
     * OWN event — "kann Fotos löschen auf Antrag des Kunden" (can delete
     * photos on the client's request), the same per-row scoping reasoning
     * as EventController::update()/destroy() above it, since
     * media.moderate-deletion-requests carries no such restriction on its
     * own.
     */
    public function approveRemoval(Request $request, Event $event, Media $media): RedirectResponse
    {
        $user = $request->user();
        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        abort_unless($user->can('media.moderate-deletion-requests') || $isOwnPartnerEvent, 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        activity()
            ->causedBy($user)
            ->performedOn($event)
            ->withProperties(['reason' => $media->getCustomProperty('deletion_reason')])
            ->log('photo removal request approved, photo deleted');

        $media->delete();

        return back()->with('status', FlashMessage::t($user->locale ?? 'ro', 'gallery.photo_deleted'));
    }

    public function denyRemoval(Request $request, Event $event, Media $media): RedirectResponse
    {
        $user = $request->user();
        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        abort_unless($user->can('media.moderate-deletion-requests') || $isOwnPartnerEvent, 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        $media->forceFill([
            'custom_properties' => collect($media->custom_properties)
                ->except(['deletion_requested', 'deletion_reason', 'deletion_requested_at', 'deletion_requested_by', 'deletion_requested_by_email'])
                ->all(),
        ])->save();

        activity()->causedBy($user)->performedOn($event)->log('photo removal request declined');

        return back()->with('status', FlashMessage::t($user->locale ?? 'ro', 'gallery.removal_denied'));
    }

    /**
     * No auth, no tenant context — reached via the signed link/QR code
     * shown to the organizer on index() above. 'signed' route middleware
     * is what authorizes reaching a specific event's gallery; this and
     * requestRemoval() load it via withoutGlobalScopes() for the same
     * reason ParticipantController's public actions do.
     */
    public function publicGallery(Request $request, int $event): Response
    {
        $event = Event::withoutGlobalScopes()->findOrFail($event);

        return Inertia::render('Events/PublicGallery', [
            'eventName' => $event->name,
            'signedUrl' => $request->fullUrl(),
            'photos' => $event->getMedia('gallery')
                ->filter(fn (Media $media) => (bool) $media->getCustomProperty('published', false))
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'removalRequested' => (bool) $media->getCustomProperty('deletion_requested', false),
                ])
                ->values(),
        ]);
    }

    public function requestRemoval(Request $request, int $event): RedirectResponse
    {
        $event = Event::withoutGlobalScopes()->findOrFail($event);

        $data = $request->validate([
            'media_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            // Required (unlike the name): a real contact is the only
            // accountability a fully anonymous, no-login public gallery can
            // offer the organizer when judging whether a removal request
            // is genuine.
            'requester_email' => ['required', 'email', 'max:255'],
        ]);

        $media = $event->getMedia('gallery')->firstWhere('id', $data['media_id']);
        abort_unless($media !== null, 404);

        if (! $media->getCustomProperty('deletion_requested', false)) {
            $media->setCustomProperty('deletion_requested', true);
            $media->setCustomProperty('deletion_reason', $data['reason']);
            $media->setCustomProperty('deletion_requested_by', $data['requester_name'] ?? null);
            $media->setCustomProperty('deletion_requested_by_email', $data['requester_email']);
            $media->setCustomProperty('deletion_requested_at', now()->toIso8601String());
            $media->save();

            activity()->performedOn($event)->withProperties(['reason' => $data['reason']])->log('photo removal requested');

            $this->notifyModerators($event, $data['requester_name'] ?? null, $data['requester_email'], $data['reason']);
        }

        return redirect($request->fullUrl())->with('status', 'Cererea ta a fost trimisă — organizatorul o va analiza.');
    }

    /**
     * One email per media.moderate-deletion-requests holder in the event's
     * organization, PLUS whoever on the agency side should know about it —
     * the event's assigned Partner (Event.partner_id) if one has their own
     * account (they also see it consolidated across every event on their
     * own dashboard, see DashboardController::partnerDashboard() — no
     * point also emailing them, that was the complaint:
     * "das Mail nicht auf meine Adresse kommt, dort verschwindet es
     * zwischen meinen 100 mails pro Tag"), otherwise the event's own
     * creator (Event.created_by) as a fallback so nothing goes
     * unnoticed when no Partner is assigned. Deduplicated by email in
     * case that person happens to also hold the client-side permission.
     * Each recipient gets it in their own locale — there is no single
     * "acting user" locale to borrow here, the submitter is an anonymous
     * public visitor. Never blocks the request itself: same non-blocking
     * try/catch pattern as every other mail send in this app.
     */
    private function notifyModerators(Event $event, ?string $requesterName, string $requesterEmail, string $reason): void
    {
        $galleryUrl = route('gallery.index', $event);

        $event->loadMissing('partner.user', 'creator');
        $agencyContact = $event->partner?->user ? null : $event->creator;

        $moderators = User::where('organization_id', $event->organization_id)
            ->permission('media.moderate-deletion-requests')
            ->get()
            ->push($agencyContact)
            ->filter()
            ->unique('email');

        foreach ($moderators as $moderator) {
            try {
                Mail::to($moderator->email)->send(new PhotoRemovalRequested(
                    $event,
                    $galleryUrl,
                    $requesterName,
                    $requesterEmail,
                    $reason,
                    $moderator->locale ?? 'ro',
                ));
            } catch (\Throwable $e) {
                Log::warning('Photo removal request notification failed to send', [
                    'event_id' => $event->id,
                    'moderator_id' => $moderator->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Same recipient-resolution reasoning as notifyModerators(): the
     * event's assigned Partner's own account if it has one, otherwise the
     * event's creator as a fallback. $photographer is the account that
     * just confirmed, not the recipient.
     */
    private function notifyPhotographerConfirmed(Event $event, User $photographer): void
    {
        $event->loadMissing('partner.user', 'creator');
        $recipient = $event->partner?->user ?? $event->creator;

        if ($recipient === null) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new PhotographerConfirmedAccess(
                $event,
                $photographer->name,
                $photographer->email,
                route('events.show', $event),
                $recipient->locale ?? 'ro',
            ));
        } catch (\Throwable $e) {
            Log::warning('Photographer-confirmed notification failed to send', [
                'event_id' => $event->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function photoPayload(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'fileName' => $media->file_name,
            'published' => (bool) $media->getCustomProperty('published', false),
            'removalRequested' => (bool) $media->getCustomProperty('deletion_requested', false),
            'removalReason' => $media->getCustomProperty('deletion_reason'),
            'removalRequestedBy' => $media->getCustomProperty('deletion_requested_by'),
            'removalRequestedByEmail' => $media->getCustomProperty('deletion_requested_by_email'),
        ];
    }

    /**
     * Valid for as long as photos are actually kept around (the event's own
     * retention window) — no point handing out a link that outlives the
     * thing it points to.
     */
    private function publicGalleryUrl(Event $event): string
    {
        return URL::temporarySignedRoute(
            'gallery.public',
            $event->retentionDeadline(),
            ['event' => $event->id],
        );
    }

    /**
     * Upload is normally any media.upload holder in the org, but once an
     * event has ONE specific photographer designated (EventController's
     * photographer_user_id), only that exact account — or a customer-admin/
     * event-manager, who outrank any single event's assignment — may use
     * it. Events with no designation keep today's org-wide behaviour.
     */
    /**
     * Upload is normally any media.upload holder in the org, but once an
     * event has ONE specific photographer designated (EventController's
     * photographer_user_id), only that exact account — or a customer-admin/
     * event-manager, who outrank any single event's assignment — may use
     * it. Events with no designation keep today's org-wide behaviour.
     *
     * A Partner may also upload to its OWN event, but only as a fallback —
     * "kann Fotos hochladen, wenn der Kunde keinen eigenen Fotografen
     * benennt" (can upload if the client hasn't named its own
     * photographer): the moment a photographer IS designated, that
     * exclusive lock applies to the Partner exactly like anyone else
     * without the role — no fallback branch reaches that case at all.
     */
    private function canUpload(User $user, Event $event): bool
    {
        if ($event->photographer_user_id !== null) {
            $isAssignedPhotographer = $user->id === $event->photographer_user_id;
            $isOrgManager = $user->hasAnyRole(['customer-admin', 'event-manager']);

            return $user->can('media.upload') && ($isAssignedPhotographer || $isOrgManager);
        }

        $isOwnPartnerEvent = $user->isActingAsPartnerOnly() && $event->partner_id === $user->partner->id;

        return $user->can('media.upload') || $isOwnPartnerEvent;
    }

    private function authorizeUpload(Request $request, Event $event): void
    {
        abort_unless($this->canUpload($request->user(), $event), 403);
    }
}
