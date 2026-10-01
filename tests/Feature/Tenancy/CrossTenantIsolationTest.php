<?php

namespace Tests\Feature\Tenancy;

use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The acceptance criterion from the roadmap (§13) and AP05, made concrete:
 * "Ein Nutzer von Kunde A kann auch durch manipulierte URLs oder
 * API-Anfragen keine Daten von Kunde B abrufen." These tests exercise
 * TenantScope directly — the same guard every controller query goes
 * through regardless of what the URL/request looked like, so a
 * "manipulated" request is indistinguishable here from a query that
 * merely asks for another tenant's id.
 *
 * In production, SetTenantContext middleware calls TenantContext::setFromUser()
 * once per request from the authenticated user; these tests call it directly
 * for the same effect, since no HTTP request/middleware runs here.
 */
class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        TenantContext::flush();
    }

    protected function tearDown(): void
    {
        TenantContext::flush();

        parent::tearDown();
    }

    /**
     * User itself carries no automatic tenant scope — see the long comment
     * on User::booted() for why (it would break Laravel's own auth
     * lookups). Listing "my organization's users" must go through an
     * explicit, scoped query such as this relation, never User::all().
     */
    public function test_listing_an_organizations_users_through_its_relation_excludes_other_tenants(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->forOrganization($orgA)->create();
        $userB = User::factory()->forOrganization($orgB)->create();

        $members = $orgA->users;

        $this->assertTrue($members->contains($userA));
        $this->assertFalse($members->contains($userB));
    }

    public function test_a_customer_user_cannot_fetch_another_organization_by_guessing_its_id(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->forOrganization($orgA)->create();

        TenantContext::setFromUser($userA);

        $this->assertNotNull(Organization::find($orgA->id));
        $this->assertNull(Organization::find($orgB->id));
    }

    public function test_a_franchise_partner_admin_sees_their_own_organizations_only(): void
    {
        $franchiseA = FranchisePartner::factory()->create();
        $franchiseB = FranchisePartner::factory()->create();

        $orgA1 = Organization::factory()->create(['franchise_partner_id' => $franchiseA->id]);
        $orgA2 = Organization::factory()->create(['franchise_partner_id' => $franchiseA->id]);
        $orgB1 = Organization::factory()->create(['franchise_partner_id' => $franchiseB->id]);

        $franchiseAdminA = User::factory()->forFranchisePartner($franchiseA)->create();

        TenantContext::setFromUser($franchiseAdminA);

        $visible = Organization::all();

        $this->assertTrue($visible->contains($orgA1));
        $this->assertTrue($visible->contains($orgA2));
        $this->assertFalse($visible->contains($orgB1));
        $this->assertNull(Organization::find($orgB1->id));
    }

    public function test_a_platform_admin_is_not_restricted_by_tenant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $platformAdmin = User::factory()->create();
        $platformAdmin->assignRole('platform-admin');

        TenantContext::setFromUser($platformAdmin);

        $visible = Organization::all();

        $this->assertTrue($visible->contains($orgA));
        $this->assertTrue($visible->contains($orgB));
    }

    public function test_with_no_tenant_context_nothing_is_visible(): void
    {
        Organization::factory()->create();
        Organization::factory()->create();

        // No actingAs, no setFromUser: TenantContext resolves auth()->user()
        // to null the first time it's asked, same as an anonymous request.
        $this->assertCount(0, Organization::all());
    }

    public function test_creating_a_user_auto_stamps_organization_and_franchise_partner_from_context(): void
    {
        $organization = Organization::factory()->create();

        TenantContext::setFromUser(User::factory()->forOrganization($organization)->create());

        $newUser = User::factory()->make(); // deliberately no organization_id/franchise_partner_id set
        $newUser->save();

        $this->assertSame($organization->id, $newUser->organization_id);
        $this->assertSame($organization->franchise_partner_id, $newUser->franchise_partner_id);
    }
}
