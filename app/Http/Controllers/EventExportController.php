<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The recipient side of an "export" end-of-cycle event (see
 * ProcessEventRetentionCommand and EventPackageReady) — reached via one
 * signed link emailed once, no account, same pattern as every other public
 * flow in this app (ParticipantController, GalleryController's public
 * gallery). show()/confirm() share that one link's signature (GET shows the
 * page, POST confirms); download() gets its own, short-lived signature,
 * minted fresh every time show() renders rather than stored anywhere.
 */
class EventExportController extends Controller
{
    public function show(Request $request, int $event): Response
    {
        $event = Event::withoutGlobalScopes()->findOrFail($event);

        return Inertia::render('Events/PackageDelivery', [
            'eventName' => $event->name,
            'signedUrl' => $request->fullUrl(),
            'confirmed' => $event->export_confirmed_at !== null,
            'password' => $event->export_confirmed_at === null ? $event->export_password : null,
            'downloadUrl' => $event->export_confirmed_at === null && $event->export_path !== null
                ? URL::temporarySignedRoute('events.export.download', now()->addHour(), ['event' => $event->id])
                : null,
        ]);
    }

    public function download(Request $request, int $event): HttpResponse
    {
        $event = Event::withoutGlobalScopes()->findOrFail($event);

        abort_if($event->export_path === null || $event->export_confirmed_at !== null, 404);
        abort_unless(Storage::disk('local')->exists($event->export_path), 404);

        $downloadName = Str::slug($event->name).'-poze.zip';

        return response()->download(Storage::disk('local')->path($event->export_path), $downloadName);
    }

    public function confirm(Request $request, int $event): RedirectResponse
    {
        $event = Event::withoutGlobalScopes()->findOrFail($event);

        if ($event->export_confirmed_at === null) {
            if ($event->export_path !== null) {
                Storage::disk('local')->delete($event->export_path);
            }

            $event->clearMediaCollection('gallery');

            $event->forceFill([
                'export_confirmed_at' => now(),
                'export_path' => null,
                'export_password' => null,
            ])->save();

            activity()
                ->performedOn($event)
                ->log('event package confirmed by recipient, originals deleted');
        }

        return redirect($request->fullUrl());
    }
}
