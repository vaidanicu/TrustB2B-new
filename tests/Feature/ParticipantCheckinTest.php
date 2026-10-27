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

/**
 * The door, on the day of the event — see ParticipantController::checkin()/
 * checkinStore() and the German spec this came from: participants get a
 * generated code with their invitation, and staff enter it here to mark
 * attendance in real time.
 */
class ParticipantCheckinTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_code_checks_the_participant_in(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'checkin_code' => 'ABC123',
        ]);

        $this->actingAs($user)
            ->post(route('participants.checkin.store', $event), ['code' => 'abc123'])
            ->assertRedirect();

        $this->assertNotNull($participant->fresh()->checked_in_at);
    }

    public function test_an_invalid_code_is_rejected(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $this->actingAs($user)
            ->post(route('participants.checkin.store', $event), ['code' => 'ZZZZZZ'])
            ->assertSessionHasErrors('code');
    }

    public function test_checking_in_twice_reports_already_checked_in_without_erroring(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'checkin_code' => 'ABC123',
            'checked_in_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->post(route('participants.checkin.store', $event), ['code' => 'ABC123']);

        $response->assertSessionHas('checkinStatus', 'already');
        $this->assertTrue($participant->fresh()->checked_in_at->equalTo($participant->checked_in_at));
    }

    public function test_a_franchise_partner_admin_can_also_check_participants_in(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'checkin_code' => 'ABC123',
        ]);

        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        $this->actingAs($user)
            ->post(route('participants.checkin.store', $event), ['code' => 'ABC123'])
            ->assertRedirect();

        $this->assertNotNull($participant->fresh()->checked_in_at);
    }

    public function test_a_restaurant_event_has_no_checkin_screen(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'is_restaurant' => true,
        ]);

        $this->actingAs($user)->get(route('participants.checkin', $event))->assertNotFound();
    }
}
