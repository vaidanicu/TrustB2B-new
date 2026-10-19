<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The private event gallery. Upload (media.upload: photographer,
 * customer-admin, event-manager) and publish (media.publish: customer-admin,
 * event-manager only) are deliberately separate permissions, seeded from
 * day one in RolesAndPermissionsSeeder specifically so "Fotograf: nur
 * Upload... kein Teilnehmerdatenexport" (roadmap §2) means something real
 * here — a photographer can add photos but never decide what participants
 * end up seeing.
 */
class GalleryController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        abort_unless($request->user()->can('media.upload') || $request->user()->can('media.publish'), 403);

        return Inertia::render('Events/Gallery', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'canPublish' => $request->user()->can('media.publish'),
            'photos' => $event->getMedia('gallery')->map(fn (Media $media) => $this->photoPayload($media))->values(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->can('media.upload'), 403);

        $request->validate([
            'photos' => ['required', 'array', 'max:20'],
            'photos.*' => ['image', 'max:10240'],
        ]);

        foreach ($request->file('photos') as $file) {
            $event->addMedia($file)
                ->withCustomProperties(['published' => false, 'uploaded_by' => $request->user()->id])
                ->toMediaCollection('gallery');
        }

        activity()->causedBy($request->user())->performedOn($event)->log('photos uploaded');

        return back()->with('status', 'Fotografii încărcate.');
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

    private function photoPayload(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'fileName' => $media->file_name,
            'published' => (bool) $media->getCustomProperty('published', false),
        ];
    }
}
