<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * trustbridgeb2b.com and app.trustbridgeb2b.com share one codebase/docroot
 * (see MarketingController). Every authenticated route used to have no
 * domain check of its own, so staying logged in on the bare marketing
 * domain silently worked — and any signed link generated from there (a
 * participant invitation) was stamped with the wrong host and 403'd for
 * whoever clicked it. See EnsureAppDomain's own docblock.
 */
class EnsureAppDomainTest extends TestCase
{
    use RefreshDatabase;

    private function appHost(): string
    {
        return parse_url(config('app.url'), PHP_URL_HOST);
    }

    public function test_an_authenticated_route_on_the_marketing_domain_redirects_to_the_app_domain(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->get('http://trustbridgeb2b.example/dashboard')
            ->assertRedirect(config('app.url').'/dashboard');
    }

    /**
     * actingAs() pre-authenticates outside the middleware pipeline, so the
     * test above never actually exercises ordering against 'auth' — this
     * is the scenario that broke in production: a logged-OUT visit hits
     * Authenticate before EnsureAppDomain gets a chance to run (unless
     * EnsureAppDomain is in the priority list, not just first in the route
     * group array) and redirects to login on the WRONG domain instead of
     * to the app domain first.
     */
    public function test_a_guest_on_the_marketing_domain_is_sent_to_the_app_domain_not_the_wrong_login(): void
    {
        $this->get('http://trustbridgeb2b.example/dashboard')
            ->assertRedirect(config('app.url').'/dashboard');
    }

    public function test_the_app_domain_itself_is_not_redirected(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->get('http://'.$this->appHost().'/dashboard')
            ->assertOk();
    }

    public function test_login_on_the_marketing_domain_also_redirects(): void
    {
        $this->get('http://trustbridgeb2b.example/login')
            ->assertRedirect(config('app.url').'/login');
    }
}
