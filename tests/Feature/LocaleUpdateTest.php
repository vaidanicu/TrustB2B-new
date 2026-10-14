<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The authenticated app's own language preference — distinct from the
 * marketing page's ro/en/de, which needs no account (MarketingController).
 */
class LocaleUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_switch_their_locale(): void
    {
        $user = User::factory()->forOrganization()->create(['locale' => 'ro']);

        $this->actingAs($user)
            ->patch(route('locale.update'), ['locale' => 'de'])
            ->assertRedirect();

        $this->assertSame('de', $user->fresh()->locale);
    }

    public function test_an_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->forOrganization()->create(['locale' => 'ro']);

        $this->actingAs($user)
            ->patch(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('ro', $user->fresh()->locale);
    }

    public function test_a_new_user_defaults_to_romanian(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->assertSame('ro', $user->fresh()->locale);
    }

    public function test_a_guest_cannot_change_locale(): void
    {
        $this->patch(route('locale.update'), ['locale' => 'en'])->assertRedirect(route('login'));
    }
}
