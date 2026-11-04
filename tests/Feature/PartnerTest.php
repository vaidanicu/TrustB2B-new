<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Partner: a regional reseller assigned per-event as the sales-responsible
 * party (Event.partner_id) — a different concept from FranchisePartner,
 * which is a tenancy boundary. Managed by the agency (partners.manage),
 * never by a client's own staff. See Partner's own docblock.
 */
class PartnerTest extends TestCase
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

    private function partnerAccount(Partner $partner): User
    {
        $user = User::factory()->forFranchisePartner($partner->franchisePartner)->create();
        (new AssignRole)->grant($user, 'partner', 'test');
        $partner->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    public function test_a_franchise_partner_admin_can_create_a_partner(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('partners.store'), [
            'name' => 'Auras srl',
            'country' => 'RO',
        ]);

        $response->assertRedirect(route('partners.index'));

        $partner = Partner::sole();
        $this->assertSame('Auras srl', $partner->name);
        $this->assertSame($franchisePartner->id, $partner->franchise_partner_id);
    }

    public function test_a_system_operator_must_pick_a_franchise_partner_explicitly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('platform-admin');

        $response = $this->actingAs($user)->post(route('partners.store'), [
            'name' => 'Auras srl',
            'country' => 'RO',
        ]);

        $response->assertSessionHasErrors('franchise_partner_id');
        $this->assertSame(0, Partner::count());
    }

    public function test_an_organization_scoped_user_cannot_view_or_create_partners(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)->get(route('partners.index'))->assertForbidden();
        $this->actingAs($user)->post(route('partners.store'), ['name' => 'x', 'country' => 'RO'])->assertForbidden();
    }

    public function test_a_franchise_partner_admin_only_sees_their_own_partners(): void
    {
        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        Partner::factory()->for($ownFranchise)->create(['name' => 'Mine']);
        Partner::factory()->for($otherFranchise)->create(['name' => 'Not mine']);

        $user = $this->franchisePartnerAdmin($ownFranchise);

        $this->actingAs($user)
            ->get(route('partners.index'))
            ->assertInertia(fn ($page) => $page->has('partners', 1)->where('partners.0.name', 'Mine'));
    }

    public function test_an_event_can_be_assigned_the_partner_responsible_for_the_sale(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO', 'language' => 'ro',
            'starts_at' => '2026-12-01', 'ends_at' => '2026-12-01',
            'retention_days' => 90, 'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'client_company_name' => 'Optimedia SRL',
            'partner_id' => $partner->id,
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertSame($partner->id, $event->partner_id);
        $this->assertSame('Optimedia SRL', $event->client_company_name);
    }

    public function test_an_event_cannot_be_assigned_a_partner_from_another_franchise(): void
    {
        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($ownFranchise)->create();
        $foreignPartner = Partner::factory()->for($otherFranchise)->create();
        $user = $this->franchisePartnerAdmin($ownFranchise);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO', 'language' => 'ro',
            'starts_at' => '2026-12-01', 'ends_at' => '2026-12-01',
            'retention_days' => 90, 'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'partner_id' => $foreignPartner->id,
        ]);

        $response->assertNotFound();
        $this->assertSame(0, Event::count());
    }

    public function test_a_partner_account_only_sees_clients_it_has_an_event_with(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);

        $theirClient = Organization::factory()->for($franchisePartner)->create(['name' => 'Theirs']);
        Event::factory()->for($theirClient)->create(['franchise_partner_id' => $franchisePartner->id, 'partner_id' => $partner->id]);

        $otherClient = Organization::factory()->for($franchisePartner)->create(['name' => 'Not theirs']);
        Event::factory()->for($otherClient)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizations/Index')
                ->where('canManage', false)
                ->has('organizations', 1)
                ->where('organizations.0.name', 'Theirs'));
    }

    public function test_a_partner_account_sees_only_its_own_events_for_a_client_with_photographer_and_contact_columns(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();

        $theirEvent = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'partner_id' => $partner->id,
            'photographer' => 'Ion Popescu',
            'client_contact_name' => 'Bianca Ionescu',
            'client_contact_email' => 'bianca@hotelaurora.ro',
        ]);
        Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)
            ->get(route('organizations.events', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizations/Events')
                ->where('canManage', false)
                ->has('events', 1)
                ->where('events.0.id', $theirEvent->id)
                ->where('events.0.photographerName', 'Ion Popescu')
                ->where('events.0.clientContactEmail', 'bianca@hotelaurora.ro'));
    }

    public function test_a_partner_account_cannot_view_a_clients_events_it_has_no_event_with(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);

        $organization = Organization::factory()->for($franchisePartner)->create();
        Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)->get(route('organizations.events', $organization))->assertForbidden();
    }
}
