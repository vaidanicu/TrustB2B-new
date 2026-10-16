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
