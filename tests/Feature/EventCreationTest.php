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
 * AP07/08: events are set up by the agency (franchise-partner-admin or
 * platform-/franchisor-admin) on behalf of a client — never by the
 * client's own staff, since the GDPR contract participants must accept
 * is drafted here. See EventController's docblock for why.
 */
class EventCreationTest extends TestCase
{
    use RefreshDatabase;

    private function franchisePartnerAdmin(FranchisePartner $franchisePartner): User
    {
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        return $user;
    }

    public function test_a_franchise_partner_admin_can_create_an_event_for_one_of_their_clients(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal — clienți 2026',
            'purpose' => 'Eveniment de fidelizare',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Prin participare, sunt de acord cu prelucrarea datelor mele.',
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame($franchisePartner->id, $event->franchise_partner_id);
        $this->assertSame($user->id, $event->created_by);
        $this->assertSame('draft', $event->status);
        $this->assertFalse($event->is_restaurant);
        $this->assertNotNull($event->gdpr_contract_text);
    }

    public function test_a_restaurant_event_does_not_require_gdpr_contract_text(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Seară la restaurant',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'is_restaurant' => true,
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertTrue($event->is_restaurant);
        $this->assertNull($event->gdpr_contract_text);
    }

    public function test_a_non_restaurant_event_requires_gdpr_contract_text(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment fără contract',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
        ]);

        $response->assertSessionHasErrors('gdpr_contract_text');
        $this->assertSame(0, Event::count());
    }

    public function test_an_organization_scoped_user_cannot_create_an_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($user, 'customer-admin', 'test');

        $this->actingAs($user)->post(route('events.store'), ['organization_id' => $organization->id, 'name' => 'x'])->assertForbidden();
        $this->assertSame(0, Event::count());
    }

    public function test_a_franchise_partner_admin_cannot_create_an_event_for_another_franchises_client(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $otherOrganization = Organization::factory()->for($otherFranchise)->create();
        $user = $this->franchisePartnerAdmin($ownFranchise);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $otherOrganization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, Event::count());
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
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 45, // only 60 or 90 are allowed (roadmap §1)
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertSessionHasErrors('retention_days');
        $this->assertSame(0, Event::count());
    }
}
