<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the pages built in the frontend pass: each route renders
 * the right Inertia component for the right role, and the two role-gated
 * pages (Organizations, Events/Create) actually turn away the wrong role
 * rather than silently rendering something not meant for them.
 */
class AppPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_page_renders_for_a_guest(): void
    {
        $this->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/TwoFactorChallenge'));
    }

    public function test_organization_scoped_user_is_sent_to_the_events_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')->where('events', []));
    }

    public function test_franchise_scoped_user_is_redirected_from_dashboard_to_organizations(): void
    {
        $user = User::factory()->forFranchisePartner()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('organizations.index'));
    }

    public function test_organizations_page_lists_only_the_franchise_partners_own_organizations(): void
    {
        $franchiseA = FranchisePartner::factory()->create();
        $franchiseB = FranchisePartner::factory()->create();
        $orgA = Organization::factory()->create(['franchise_partner_id' => $franchiseA->id, 'name' => 'Hotel Aurora']);
        Organization::factory()->create(['franchise_partner_id' => $franchiseB->id, 'name' => 'Other Franchise Org']);

        $user = User::factory()->forFranchisePartner($franchiseA)->create();

        $this->actingAs($user)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizations/Index')
                ->has('organizations', 1)
                ->where('organizations.0.name', $orgA->name)
            );
    }

    public function test_organization_scoped_user_cannot_reach_the_organizations_page(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)->get(route('organizations.index'))->assertForbidden();
    }

    public function test_franchise_scoped_user_cannot_reach_the_event_create_page(): void
    {
        $user = User::factory()->forFranchisePartner()->create();

        $this->actingAs($user)->get(route('events.create'))->assertForbidden();
    }

    public function test_organization_scoped_user_can_reach_the_event_create_page(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->get(route('events.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/Create'));
    }

    public function test_audit_log_only_shows_entries_caused_by_the_same_organization(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $assignRole = new AssignRole;

        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->forOrganization($orgA)->create();
        $userB = User::factory()->forOrganization($orgB)->create();

        // AssignRole logs the current auth()->user() as causer — act as
        // each user while granting their own role so the activity entry is
        // attributable, exactly as it would be from a real admin screen.
        $this->actingAs($userA);
        $assignRole->grant($userA, 'customer-admin', 'test');

        $this->actingAs($userB);
        $assignRole->grant($userB, 'customer-admin', 'test');

        $this->actingAs($userA)
            ->get(route('audit-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('AuditLog/Index')
                ->has('entries', 1)
                ->where('entries.0.causer', $userA->name)
            );
    }
}
