<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('guide.index'))->assertRedirect(route('login'));
    }

    public function test_an_organization_scoped_user_can_view_the_guide(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->get(route('guide.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Guide/Index'));
    }

    public function test_a_franchise_scoped_user_can_view_the_guide(): void
    {
        $user = User::factory()->forFranchisePartner()->create();

        $this->actingAs($user)
            ->get(route('guide.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Guide/Index'));
    }
}
