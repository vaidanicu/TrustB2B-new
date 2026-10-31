<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Builds the password-protected (AES-256) archive behind an "export"
 * end-of-cycle event — see ProcessEventRetentionCommand. Lives on the
 * private 'local' disk (storage/app/private), never the 'public' one the
 * gallery itself uses: nothing here is reachable except through
 * EventExportController's signed routes.
 */
class EventPackageExport
{
    /**
     * @return array{path: string, password: string}|null null when there's
     *                                                    nothing to export, or the archive couldn't be built at all — the caller
     *                                                    must not mark the event processed in either case, so it's retried
     *                                                    tomorrow rather than silently losing the photos.
     */
    public static function build(Event $event): ?array
    {
        $files = $event->getMedia('gallery');

        if ($files->isEmpty()) {
            return null;
        }

        $relativePath = 'exports/event-'.$event->id.'-'.Str::random(10).'.zip';
        $disk = Storage::disk('local');
        $disk->makeDirectory('exports');

        $zip = new ZipArchive;

        if ($zip->open($disk->path($relativePath), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error('Event package export: could not create zip archive', ['event_id' => $event->id]);

            return null;
        }

        $password = Str::password(16);

        foreach ($files as $file) {
            $entryName = $file->id.'-'.$file->file_name;

            if (! $zip->addFile($file->getPath(), $entryName)) {
                continue;
            }

            if (! $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256, $password)) {
                Log::warning('Event package export: AES encryption unavailable, archive will be unencrypted', ['event_id' => $event->id]);
            }
        }

        $zip->close();

        return ['path' => $relativePath, 'password' => $password];
    }
}
