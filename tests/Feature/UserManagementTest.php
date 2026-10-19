<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AP06, organization-scoped half: a customer-admin adding/removing staff
 * within their own organization — distinct from OrganizationController's
 * franchise-level "Client nou" (which creates the organization itself).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function customerAdmin(Organization $organization): User
    {
        $admin = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($admin, 'customer-admin', 'test');

        return $admin;
    }

    public function test_a_customer_admin_can_add_a_colleague(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $admin = $this->customerAdmin($organization);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Grup Media Est SRL',
            'email' => 'fotograf@exemplu.ro',
            'role' => 'photographer',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('newStaffPassword');

        $staff = User::where('email', 'fotograf@exemplu.ro')->sole();
        $this->assertSame($organization->id, $staff->organization_id);
        $this->assertTrue($staff->hasRole('photographer'));
    }

    public function test_a_photographer_cannot_add_colleagues(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $photographer = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($photographer, 'photographer', 'test');

        $this->actingAs($photographer)->post(route('users.store'), [
            'name' => 'x', 'email' => 'x@example.test', 'role' => 'photographer',
        ])->assertForbidden();

        $this->assertSame(1, User::where('organization_id', $organization->id)->count());
    }

    public function test_a_franchise_scoped_user_cannot_manage_an_organizations_team(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchiseAdmin = User::factory()->forFranchisePartner()->create();
        (new AssignRole)->grant($franchiseAdmin, 'franchise-partner-admin', 'test');

        $this->actingAs($franchiseAdmin)->get(route('users.index'))->assertForbidden();
    }

    public function test_a_customer_admin_can_remove_a_colleague(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $admin = $this->customerAdmin($organization);
        $staff = User::factory()->forOrganization($organization)->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $staff))
            ->assertRedirect();

        $this->assertNull($staff->fresh());
    }

    public function test_a_customer_admin_cannot_delete_their_own_account_here(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $admin = $this->customerAdmin($organization);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertNotNull($admin->fresh());
    }

    public function test_a_customer_admin_cannot_delete_a_user_from_another_organization(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $admin = $this->customerAdmin($organization);
        $outsider = User::factory()->forOrganization()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $outsider))
            ->assertNotFound();

        $this->assertNotNull($outsider->fresh());
    }
}
