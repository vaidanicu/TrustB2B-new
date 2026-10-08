<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP07/08: the first end-to-end real flow — create an event, see it on the
 * dashboard, and (the part that actually matters) never see another
 * organization's event there. Event is the first content model using
 * BelongsToOrganization for real; this is that trait's first live test
 * outside User, which deliberately does NOT use it (see User::booted()).
 */
class EventCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_event_stamps_organization_and_franchise_partner_automatically(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();

        $response = $this->actingAs($user)->post(route('events.store'), [
            'name' => 'Turneu de fotbal — clienți 2026',
            'purpose' => 'Eveniment de fidelizare',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
        ]);

        $response->assertRedirect(route('dashboard'));

        $event = Event::sole();
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame($organization->franchise_partner_id, $event->franchise_partner_id);
        $this->assertSame($user->id, $event->created_by);
        $this->assertSame('draft', $event->status);
    }

    public function test_franchise_scoped_user_cannot_create_an_event(): void
    {
        $user = User::factory()->forFranchisePartner()->create();

        $this->actingAs($user)->post(route('events.store'), ['name' => 'x'])->assertForbidden();
    }

    public function test_dashboard_lists_only_the_users_own_organization_events(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $eventA = Event::factory()->for($orgA)->create(['franchise_partner_id' => $orgA->franchise_partner_id, 'name' => 'Eveniment A']);
        Event::factory()->for($orgB)->create(['franchise_partner_id' => $orgB->franchise_partner_id, 'name' => 'Eveniment B']);

        $userA = User::factory()->forOrganization($orgA)->create();

        $this->actingAs($userA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')
                ->has('events', 1)
                ->where('events.0.name', $eventA->name)
            );
    }

    public function test_an_invalid_retention_value_is_rejected(): void
    {
        $user = User::factory()->forOrganization()->create();

        $response = $this->actingAs($user)->post(route('events.store'), [
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 45, // only 60 or 90 are allowed (roadmap §1)
            'end_of_cycle_action' => 'delete',
        ]);

        $response->assertSessionHasErrors('retention_days');
        $this->assertSame(0, Event::count());
    }
}
