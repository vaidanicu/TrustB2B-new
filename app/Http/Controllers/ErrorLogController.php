<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Real PHP/application errors from Laravel's own log file — distinct from
 * AuditLogController, which only ever shows business activity (events
 * created, roles granted), never a stack trace. Gated by system.view-logs
 * (platform-admin/franchisor-admin only, see RolesAndPermissionsSeeder): a
 * stack trace or an exception message can leak file paths, queries or
 * customer input from any tenant, a different sensitivity level than the
 * account/tenancy records roadmap §2 already lets a system operator see
 * outright.
 */
class ErrorLogController extends Controller
{
    private const MAX_ENTRIES = 150;

    private const TAIL_BYTES = 2_000_000;

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('system.view-logs'), 403);

        return Inertia::render('ErrorLog/Index', [
            'entries' => $this->recentEntries(),
        ]);
    }

    /**
     * @return list<array{at: string, level: string, message: string, details: string|null}>
     */
    private function recentEntries(): array
    {
        $path = $this->latestLogFile();

        if ($path === null) {
            return [];
        }

        $chunks = preg_split(
            '/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m',
            $this->tail($path, self::TAIL_BYTES),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        $entries = [];

        foreach ($chunks as $chunk) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \S+\.(\w+): (.*)$/m', $chunk, $m)) {
                // Leftover fragment before the first full entry in the
                // tailed window — not a real entry, drop it.
                continue;
            }

            $firstLineEnd = strpos($chunk, "\n");
            $details = $firstLineEnd !== false ? trim(substr($chunk, $firstLineEnd + 1)) : '';

            $entries[] = [
                'at' => $m[1],
                'level' => strtoupper($m[2]),
                'message' => trim($m[3]),
                'details' => $details !== '' ? $details : null,
            ];
        }

        return array_slice(array_reverse($entries), 0, self::MAX_ENTRIES);
    }

    private function latestLogFile(): ?string
    {
        $dir = storage_path('logs');

        if (! File::isDirectory($dir)) {
            return null;
        }

        $latest = collect(File::files($dir))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'laravel'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->first();

        return $latest?->getPathname();
    }

    private function tail(string $path, int $bytes): string
    {
        $size = filesize($path);

        if ($size === false || $size <= $bytes) {
            return File::get($path);
        }

        $handle = fopen($path, 'r');
        fseek($handle, -$bytes, SEEK_END);
        $content = fread($handle, $bytes);
        fclose($handle);

        return $content === false ? '' : $content;
    }
}
