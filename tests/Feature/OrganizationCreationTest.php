<?php

namespace Tests\Feature;

use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP06: onboarding a new client organization has to also create its first
 * admin — an organization nobody can log into is a dead end. No outbound
 * email yet, so the generated password travels through the response flash,
 * same honest limitation as the participant invitation link.
 */
class OrganizationCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_franchise_partner_admin_can_create_a_client_organization_with_its_first_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Hotel Aurora',
            'country' => 'RO',
            'data_protection_contact_email' => 'dpo@hotelaurora.ro',
            'admin_name' => 'Bianca Ionescu',
            'admin_email' => 'bianca@hotelaurora.ro',
        ]);

        $response->assertRedirect(route('organizations.index'));
        $response->assertSessionHas('newAdminEmail', 'bianca@hotelaurora.ro');
        $response->assertSessionHas('newAdminPassword');

        $organization = Organization::sole();
        $this->assertSame('Hotel Aurora', $organization->name);
        $this->assertSame($franchisePartner->id, $organization->franchise_partner_id);

        $admin = User::where('email', 'bianca@hotelaurora.ro')->sole();
        $this->assertSame($organization->id, $admin->organization_id);
        $this->assertSame($franchisePartner->id, $admin->franchise_partner_id);
        $this->assertTrue($admin->hasRole('customer-admin'));
    }

    public function test_a_franchise_partner_admin_cannot_assign_a_different_franchise_to_the_organization(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $user = User::factory()->forFranchisePartner($ownFranchise)->create();

        $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Hotel Aurora',
            'country' => 'RO',
            'franchise_partner_id' => $otherFranchise->id,
            'admin_name' => 'Bianca Ionescu',
            'admin_email' => 'bianca@hotelaurora.ro',
        ]);

        $this->assertSame($ownFranchise->id, Organization::sole()->franchise_partner_id);
    }

    public function test_a_system_operator_must_pick_a_franchise_partner_explicitly(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('platform-admin');

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Hotel Aurora',
            'country' => 'RO',
            'admin_name' => 'Bianca Ionescu',
            'admin_email' => 'bianca@hotelaurora.ro',
        ]);

        $response->assertSessionHasErrors('franchise_partner_id');
        $this->assertSame(0, Organization::count());
    }

    public function test_an_organization_scoped_user_cannot_create_another_organization(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'x',
            'country' => 'RO',
            'admin_name' => 'x',
            'admin_email' => 'x@example.test',
        ])->assertForbidden();

        $this->assertSame(1, Organization::count());
    }

    public function test_the_admin_email_must_be_unique(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        User::factory()->create(['email' => 'bianca@hotelaurora.ro']);

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Hotel Aurora',
            'country' => 'RO',
            'admin_name' => 'Bianca Ionescu',
            'admin_email' => 'bianca@hotelaurora.ro',
        ]);

        $response->assertSessionHasErrors('admin_email');
        $this->assertSame(0, Organization::count());
    }
}
