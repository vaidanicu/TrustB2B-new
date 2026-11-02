<?php

namespace Tests\Feature;

use App\Console\Commands\UpdateEventStatusesCommand;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Event.status was otherwise never touched after creation — this command
 * is what actually moves it along: draft -> active at the start date,
 * active -> archived once the gallery retention deadline passes. 'locked'
 * stays a manual-only state.
 */
class EventStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_draft_event_becomes_active_once_its_start_date_arrives(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'status' => 'draft',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->artisan(UpdateEventStatusesCommand::class)->assertSuccessful();

        $this->assertSame('active', $event->fresh()->status);
    }

    public function test_a_draft_event_before_its_start_date_stays_draft(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'status' => 'draft',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->artisan(UpdateEventStatusesCommand::class)->assertSuccessful();

        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_an_active_event_becomes_archived_once_its_retention_deadline_passes(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'status' => 'active',
            'starts_at' => now()->subDays(100),
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
        ]);

        $this->artisan(UpdateEventStatusesCommand::class)->assertSuccessful();

        $this->assertSame('archived', $event->fresh()->status);
    }

    public function test_an_active_event_before_its_retention_deadline_stays_active(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'status' => 'active',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(10),
            'retention_days' => 90,
        ]);

        $this->artisan(UpdateEventStatusesCommand::class)->assertSuccessful();

        $this->assertSame('active', $event->fresh()->status);
    }

    public function test_a_locked_event_is_never_touched(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'status' => 'locked',
            'starts_at' => now()->subDays(100),
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
        ]);

        $this->artisan(UpdateEventStatusesCommand::class)->assertSuccessful();

        $this->assertSame('locked', $event->fresh()->status);
    }
}
