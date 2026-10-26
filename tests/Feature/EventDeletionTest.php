<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function franchisePartnerAdmin(FranchisePartner $franchisePartner): User
    {
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        return $user;
    }

    public function test_a_franchise_partner_admin_can_delete_one_of_their_clients_events(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($user)
            ->delete(route('events.destroy', $event))
            ->assertRedirect(route('organizations.events', $organization));

        $this->assertSame(0, Event::withoutGlobalScopes()->count());
    }

    public function test_deleting_an_event_also_deletes_its_participants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);
        Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $this->actingAs($user)->delete(route('events.destroy', $event));

        $this->assertSame(0, Participant::withoutGlobalScopes()->count());
    }

    public function test_an_organization_scoped_user_cannot_delete_an_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($user, 'customer-admin', 'test');
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $this->actingAs($user)
            ->delete(route('events.destroy', $event))
            ->assertForbidden();

        $this->assertSame(1, Event::withoutGlobalScopes()->count());
    }

    public function test_a_franchise_partner_admin_cannot_delete_another_franchises_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $otherOrganization = Organization::factory()->for($otherFranchise)->create();
        $otherEvent = Event::factory()->for($otherOrganization)->create(['franchise_partner_id' => $otherFranchise->id]);

        $user = $this->franchisePartnerAdmin($ownFranchise);

        $this->actingAs($user)
            ->delete(route('events.destroy', $otherEvent))
            ->assertNotFound();

        $this->assertSame(1, Event::withoutGlobalScopes()->count());
    }
}
