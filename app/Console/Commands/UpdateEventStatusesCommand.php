<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Daily (see bootstrap/app.php's schedule). Event.status was otherwise
 * never touched after EventController::store() sets 'draft' on creation —
 * this is what actually moves it along: 'draft' -> 'active' once the
 * event's own start date arrives, 'active' -> 'finished' once its own end
 * date passes, 'finished'/'active' -> 'archived' once its gallery
 * retention deadline (Event::retentionDeadline()) passes. 'locked' stays a
 * manual-only state, deliberately untouched here. A rescheduled event
 * (EventController::update()) that moves back into the future is reverted
 * out of 'finished' there, not here — this command only ever moves status
 * forward.
 *
 * Runs as the system operator, same reasoning as
 * ProcessEventRetentionCommand: a trusted daily process enforcing a
 * lifecycle rule across every tenant, not a request-time actor being
 * granted anything new.
 */
#[Signature('events:update-statuses')]
#[Description('Move each event from draft to active at its start date, to finished at its end date, and on to archived once its retention deadline passes')]
class UpdateEventStatusesCommand extends Command
{
    public function handle(): int
    {
        TenantContext::runAsSystemOperator(function (): void {
            $this->activateStartedEvents();
            $this->finishEndedEvents();
            $this->archiveExpiredEvents();
        });

        return self::SUCCESS;
    }

    private function activateStartedEvents(): void
    {
        $events = Event::query()
            ->where('status', 'draft')
            ->where('starts_at', '<=', now())
            ->get();

        foreach ($events as $event) {
            $event->forceFill(['status' => 'active'])->save();

            activity()->performedOn($event)->log('event activated (start date reached)');
            $this->info("Event #{$event->id} ({$event->name}): draft -> active.");
        }
    }

    private function finishEndedEvents(): void
    {
        $events = Event::query()
            ->where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($events as $event) {
            $event->forceFill(['status' => 'finished'])->save();

            activity()->performedOn($event)->log('event finished (end date reached)');
            $this->info("Event #{$event->id} ({$event->name}): active -> finished.");
        }
    }

    private function archiveExpiredEvents(): void
    {
        $events = Event::query()
            ->whereIn('status', ['active', 'finished'])
            ->get()
            ->filter(fn (Event $event) => $event->retentionDeadline()->isPast());

        foreach ($events as $event) {
            $event->forceFill(['status' => 'archived'])->save();

            activity()->performedOn($event)->log('event archived (retention deadline passed)');
            $this->info("Event #{$event->id} ({$event->name}): active -> archived.");
        }
    }
}
