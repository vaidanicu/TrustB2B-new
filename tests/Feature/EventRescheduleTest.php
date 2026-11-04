<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EventController::update() — reported: "wir müssen das Datum ändern
 * können, falls das Event verschoben wird". UpdateEventStatusesCommand
 * only ever moves status forward, so rescheduling a 'finished' event back
 * into the future has to undo that stamp here, not there.
 */
class EventRescheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function franchisePartnerAdmin(FranchisePartner $franchisePartner): User
    {
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        return $user;
    }

    public function test_a_franchise_partner_admin_can_reschedule_an_event(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'starts_at' => '2026-12-01 10:00',
            'ends_at' => '2026-12-01 18:00',
        ]);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)
            ->patch(route('events.update', $event), [
                'starts_at' => '2026-12-15 10:00',
                'ends_at' => '2026-12-15 18:00',
            ])
            ->assertRedirect();

        $fresh = $event->fresh();
        $this->assertSame('2026-12-15 10:00:00', $fresh->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-15 18:00:00', $fresh->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_rescheduling_a_finished_event_into_the_future_reactivates_it(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'status' => 'finished',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
        ]);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->patch(route('events.update', $event), [
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeek()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame('draft', $event->fresh()->status);
    }

    public function test_rescheduling_an_archived_event_does_not_reactivate_it(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'status' => 'archived',
            'starts_at' => now()->subMonths(6),
            'ends_at' => now()->subMonths(6),
        ]);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->patch(route('events.update', $event), [
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeek()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame('archived', $event->fresh()->status);
    }

    public function test_the_end_date_cannot_be_before_the_start_date(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'starts_at' => '2026-12-10 10:00',
            'ends_at' => '2026-12-10 18:00',
        ]);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)
            ->patch(route('events.update', $event), [
                'starts_at' => '2026-12-10 10:00',
                'ends_at' => '2026-12-09 10:00',
            ])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_the_clients_own_staff_cannot_reschedule_an_event(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $user = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($user, 'customer-admin', 'test');

        $this->actingAs($user)
            ->patch(route('events.update', $event), [
                'starts_at' => '2026-12-15 10:00',
                'ends_at' => '2026-12-15 18:00',
            ])
            ->assertForbidden();
    }
}
