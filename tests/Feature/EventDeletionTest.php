<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_organizer_can_delete_their_own_event(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $this->actingAs($user)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Event::withoutGlobalScopes()->count());
    }

    public function test_deleting_an_event_also_deletes_its_participants(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $this->actingAs($user)->delete(route('events.destroy', $event));

        $this->assertSame(0, Participant::withoutGlobalScopes()->count());
    }

    public function test_an_organizer_cannot_delete_another_organizations_event(): void
    {
        $otherOrganization = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrganization)->create(['franchise_partner_id' => $otherOrganization->franchise_partner_id]);

        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->delete(route('events.destroy', $otherEvent))
            ->assertNotFound();

        $this->assertSame(1, Event::withoutGlobalScopes()->count());
    }
}
