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
 * There was previously no way to see an event's own data again after
 * creating it — reported: "wo kann ich die eingestellten Daten des Events
 * mir ansehen?? Die sehe ich nicht mehr". See EventController::show().
 */
class EventShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $event = Event::factory()->create();

        $this->get(route('events.show', $event))->assertRedirect(route('login'));
    }

    public function test_a_franchise_partner_admin_can_view_the_events_own_data(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'purpose' => 'Eveniment de test',
            'client_company_name' => 'Optimedia SRL',
        ]);
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/Show')
                ->where('event.purpose', 'Eveniment de test')
                ->where('event.clientCompanyName', 'Optimedia SRL'));
    }

    public function test_the_clients_own_staff_can_view_their_events_data(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $user = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($user, 'customer-admin', 'test');

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/Show'));
    }

    public function test_a_user_from_a_different_organization_cannot_view_the_event(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->forOrganization($otherOrganization)->create();
        (new AssignRole)->grant($user, 'customer-admin', 'test');

        // Event's own TenantScope (BelongsToOrganization) already excludes
        // another organization's row from route-model-binding's query —
        // 404, not 403, the same way every other tenant-scoped model in
        // this app behaves for a mismatched organization_id.
        $this->actingAs($user)->get(route('events.show', $event))->assertNotFound();
    }

    public function test_the_client_contact_can_view_the_event_it_is_listed_on(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'client_contact_email' => 'ion@paleus.ro',
        ]);
        $contact = User::factory()->forOrganization($organization)->create(['email' => 'ion@paleus.ro']);
        (new AssignRole)->grant($contact, 'client-contact', 'test');

        $this->actingAs($contact)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/Show')->where('canManage', false));
    }

    public function test_a_client_contact_cannot_view_an_event_it_is_not_listed_on(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'client_contact_email' => 'ion@paleus.ro',
        ]);
        $contact = User::factory()->forOrganization($organization)->create(['email' => 'maria@paleus.ro']);
        (new AssignRole)->grant($contact, 'client-contact', 'test');

        $this->actingAs($contact)->get(route('events.show', $event))->assertForbidden();
    }
}
