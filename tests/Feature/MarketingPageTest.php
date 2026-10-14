<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * trustbridgeb2b.com and app.trustbridgeb2b.com share one codebase and one
 * document root — MarketingController's host check is the only thing that
 * tells them apart (see its docblock). config('app.url')'s host is what
 * "the app domain" means here; APP_URL in .env is http://localhost for
 * this test environment, so that's the host these tests use for "the app
 * domain" and anything else for "the marketing domain".
 */
class MarketingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_marketing_domain_shows_the_pitch_in_romanian_by_default(): void
    {
        $this->get('http://trustbridgeb2b.example/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome')->where('locale', 'ro'));
    }

    public function test_the_marketing_domain_serves_english_and_german(): void
    {
        $this->get('http://trustbridgeb2b.example/en')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));

        $this->get('http://trustbridgeb2b.example/de')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'de'));
    }

    public function test_the_app_domain_never_shows_the_pitch(): void
    {
        $this->get('http://'.parse_url(config('app.url'), PHP_URL_HOST).'/')
            ->assertRedirect(route('login'));
    }

    public function test_the_app_domain_sends_a_logged_in_visitor_to_the_dashboard(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->get('http://'.parse_url(config('app.url'), PHP_URL_HOST).'/')
            ->assertRedirect(route('dashboard'));
    }
}
