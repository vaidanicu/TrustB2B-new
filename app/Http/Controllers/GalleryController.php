<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The event gallery, two sides:
 *
 * - Organizer side (index/store/publish/destroy/approveRemoval/denyRemoval):
 *   upload (media.upload: the event's one designated photographer — see
 *   authorizeUpload() — or customer-admin/event-manager) and publish
 *   (media.publish) are deliberately separate permissions, seeded from day
 *   one in RolesAndPermissionsSeeder specifically so "Fotograf: nur
 *   Upload... kein Teilnehmerdatenexport" (roadmap §2) means something real
 *   here — a photographer can add photos but never decide what participants
 *   end up seeing.
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
        abort_unless($request->user()->can('media.upload') || $request->user()->can('media.publish'), 403);

        $publicGalleryUrl = $this->publicGalleryUrl($event);

        return Inertia::render('Events/Gallery', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'canPublish' => $request->user()->can('media.publish'),
            'canModerateRemovals' => $request->user()->can('media.moderate-deletion-requests'),
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
                ->withCustomProperties(['published' => false, 'uploaded_by' => $request->user()->id])
                ->toMediaCollection('gallery');
        }

        activity()->causedBy($request->user())->performedOn($event)->log('photos uploaded');

        return back()->with('status', 'Fotografii/video-uri încărcate.');
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

    public function destroy(Request $request, Event $event, Media $media): RedirectResponse
    {
        abort_unless($request->user()->can('media.upload'), 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        $media->delete();

        return back();
    }

    public function approveRemoval(Request $request, Event $event, Media $media): RedirectResponse
    {
        abort_unless($request->user()->can('media.moderate-deletion-requests'), 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        activity()
            ->causedBy($request->user())
            ->performedOn($event)
            ->withProperties(['reason' => $media->getCustomProperty('deletion_reason')])
            ->log('photo removal request approved, photo deleted');

        $media->delete();

        return back()->with('status', 'Fotografie ștearsă.');
    }

    public function denyRemoval(Request $request, Event $event, Media $media): RedirectResponse
    {
        abort_unless($request->user()->can('media.moderate-deletion-requests'), 403);
        abort_unless($media->model_type === Event::class && $media->model_id === $event->id, 404);

        $media->forceFill([
            'custom_properties' => collect($media->custom_properties)
                ->except(['deletion_requested', 'deletion_reason', 'deletion_requested_at'])
                ->all(),
        ])->save();

        activity()->causedBy($request->user())->performedOn($event)->log('photo removal request declined');

        return back()->with('status', 'Cerere de eliminare respinsă.');
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
        ]);

        $media = $event->getMedia('gallery')->firstWhere('id', $data['media_id']);
        abort_unless($media !== null, 404);

        if (! $media->getCustomProperty('deletion_requested', false)) {
            $media->setCustomProperty('deletion_requested', true);
            $media->setCustomProperty('deletion_reason', $data['reason']);
            $media->setCustomProperty('deletion_requested_at', now()->toIso8601String());
            $media->save();

            activity()->performedOn($event)->withProperties(['reason' => $data['reason']])->log('photo removal requested');
        }

        return redirect($request->fullUrl())->with('status', 'Cererea ta a fost trimisă — organizatorul o va analiza.');
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
            $event->ends_at->copy()->addDays($event->retention_days),
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
    private function authorizeUpload(Request $request, Event $event): void
    {
        abort_unless($request->user()->can('media.upload'), 403);

        if ($event->photographer_user_id === null) {
            return;
        }

        $isAssignedPhotographer = $request->user()->id === $event->photographer_user_id;
        $isOrgManager = $request->user()->hasAnyRole(['customer-admin', 'event-manager']);

        abort_unless($isAssignedPhotographer || $isOrgManager, 403);
    }
}
