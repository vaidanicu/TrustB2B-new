<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An already-authenticated session hitting /login (e.g. clicking the
 * "sign in" button in a newly-created account's email while still signed
 * in as the admin who created it) used to silently bounce to the
 * clicker's own dashboard with no explanation. See
 * App\Http\Middleware\RedirectIfAuthenticated's own docblock.
 */
class RedirectIfAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_visitor_is_redirected_away_from_login_with_an_explanation(): void
    {
        $user = User::factory()->create(['name' => 'Alf Martonossy', 'email' => 'alf@example.test', 'locale' => 'de']);

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', fn ($status) => str_contains($status, 'Alf Martonossy') && str_contains($status, 'alf@example.test'));
    }

    public function test_a_guest_sees_the_login_form_normally(): void
    {
        $this->get(route('login'))->assertOk();
    }
}
