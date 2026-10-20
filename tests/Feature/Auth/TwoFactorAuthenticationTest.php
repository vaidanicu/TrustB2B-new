<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Two halves: the real login flow (LoginRequest@authenticate /
 * AuthenticatedSessionController — see their docblocks for why they now
 * check two_factor_confirmed_at before logging in directly) and the
 * self-service management endpoints the Profile page's
 * TwoFactorAuthenticationForm talks to, all of which sit behind Fortify's
 * 'password.confirm' middleware.
 */
class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: string} the user and their plaintext secret
     */
    private function userWithConfirmedTwoFactor(): array
    {
        $user = User::factory()->create();

        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return [$user->fresh(), $secret];
    }

    private function currentOtp(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    public function test_a_user_without_two_factor_logs_in_directly(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_a_user_with_confirmed_two_factor_is_sent_to_the_challenge_instead_of_logged_in(): void
    {
        [$user] = $this->userWithConfirmedTwoFactor();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login'));
        $this->assertSame($user->id, session('login.id'));
    }

    public function test_a_valid_code_completes_the_challenge_and_logs_in(): void
    {
        [$user, $secret] = $this->userWithConfirmedTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->post('/two-factor-challenge', ['code' => $this->currentOtp($secret)]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_an_invalid_code_does_not_log_in(): void
    {
        [$user] = $this->userWithConfirmedTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->post('/two-factor-challenge', ['code' => '000000']);

        $this->assertGuest();
    }

    public function test_a_recovery_code_completes_the_challenge_and_consumes_it(): void
    {
        [$user] = $this->userWithConfirmedTwoFactor();
        $recoveryCode = $user->recoveryCodes()[0];

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response = $this->post('/two-factor-challenge', ['recovery_code' => $recoveryCode]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertNotContains($recoveryCode, $user->fresh()->recoveryCodes());
    }

    public function test_the_challenge_screen_redirects_to_login_without_a_pending_challenge(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect(route('login'));
    }

    public function test_enabling_two_factor_requires_a_recently_confirmed_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('two-factor.enable'))
            ->assertStatus(423);

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_a_user_can_enable_and_confirm_two_factor_after_confirming_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson(route('two-factor.enable'))
            ->assertOk();

        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);
        $this->assertNotNull($secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson(route('two-factor.confirm'), ['code' => $this->currentOtp($secret)])
            ->assertOk();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_a_user_can_disable_two_factor_after_confirming_their_password(): void
    {
        [$user] = $this->userWithConfirmedTwoFactor();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson(route('two-factor.disable'))
            ->assertOk();

        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }
}
