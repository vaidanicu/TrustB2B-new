<?php

namespace App\Console\Commands;

use App\Mail\EventPackageReady;
use App\Models\Event;
use App\Support\EventPackageExport;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Daily (see bootstrap/app.php's schedule). Applies each event's own
 * end_of_cycle_action once its retention deadline (Event::retentionDeadline())
 * passes: 'delete' wipes the gallery outright, 'export' builds a
 * password-protected archive and emails a link to it, deleting the
 * originals only once the recipient confirms receipt
 * (EventExportController::confirm()) — never on a schedule of its own.
 *
 * Runs as the system operator (TenantContext::runAsSystemOperator) — the
 * exact use that method's own docblock was written for: a trusted
 * background process enforcing the retention cycle across every tenant,
 * not a request-time actor being granted anything new.
 */
#[Signature('events:process-retention')]
#[Description("Apply each event's end-of-cycle action once its photo retention deadline has passed")]
class ProcessEventRetentionCommand extends Command
{
    public function handle(): int
    {
        TenantContext::runAsSystemOperator(function (): void {
            Event::query()
                ->whereNull('retention_processed_at')
                ->get()
                ->filter(fn (Event $event) => $event->retentionDeadline()->isPast())
                ->each(fn (Event $event) => $this->process($event));
        });

        return self::SUCCESS;
    }

    private function process(Event $event): void
    {
        if ($event->end_of_cycle_action === 'delete') {
            $event->clearMediaCollection('gallery');
            $event->forceFill(['retention_processed_at' => now()])->save();

            activity()->performedOn($event)->log('event gallery retention: deleted');
            $this->info("Event #{$event->id} ({$event->name}): gallery deleted.");

            return;
        }

        $this->processExport($event);
    }

    private function processExport(Event $event): void
    {
        if ($event->export_path === null) {
            $package = EventPackageExport::build($event);

            if ($package === null) {
                if ($event->getMedia('gallery')->isEmpty()) {
                    $event->forceFill(['retention_processed_at' => now()])->save();
                    $this->info("Event #{$event->id} ({$event->name}): no photos to export.");
                } else {
                    Log::error('Event retention: failed to build export package, will retry tomorrow', ['event_id' => $event->id]);
                }

                return;
            }

            $event->forceFill(['export_path' => $package['path'], 'export_password' => $package['password']])->save();
        }

        $recipient = $this->recipientEmail($event);

        if ($recipient === null) {
            Log::warning('Event retention: export package has no recipient email configured, will retry tomorrow', ['event_id' => $event->id]);
            $this->warn("Event #{$event->id} ({$event->name}): package built, but no recipient email is configured.");

            return;
        }

        $packageUrl = URL::temporarySignedRoute('events.export.show', now()->addDays(60), ['event' => $event->id]);

        try {
            Mail::to($recipient)->send(new EventPackageReady($event, $packageUrl, $event->language));
        } catch (\Throwable $e) {
            Log::warning('Event retention: export package built but the email failed to send, will retry tomorrow', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $event->forceFill(['retention_processed_at' => now()])->save();

        activity()->performedOn($event)->log('event gallery retention: export package emailed');
        $this->info("Event #{$event->id} ({$event->name}): export package emailed to {$recipient}.");
    }

    private function recipientEmail(Event $event): ?string
    {
        return $event->data_protection_contact
            ?? $event->client_contact_email
            ?? $event->organization?->data_protection_contact_email;
    }
}
