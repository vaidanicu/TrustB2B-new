<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Real PHP/application errors (Laravel's own log file), gated by
 * system.view-logs — platform-admin/franchisor-admin only. A stack trace
 * can leak paths, queries or customer input from any tenant, so this is
 * deliberately stricter than audit-log.view.
 */
class ErrorLogTest extends TestCase
{
    use RefreshDatabase;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->logPath = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(storage_path('logs'));
    }

    protected function tearDown(): void
    {
        File::delete($this->logPath);

        parent::tearDown();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('error-log.index'))->assertRedirect(route('login'));
    }

    public function test_an_organization_scoped_user_cannot_view_the_error_log(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)->get(route('error-log.index'))->assertForbidden();
    }

    public function test_a_franchise_partner_admin_cannot_view_the_error_log(): void
    {
        $user = User::factory()->forFranchisePartner()->create();
        $user->assignRole('franchise-partner-admin');

        $this->actingAs($user)->get(route('error-log.index'))->assertForbidden();
    }

    public function test_a_platform_admin_can_view_the_error_log(): void
    {
        $user = User::factory()->create();
        $user->assignRole('platform-admin');

        $this->actingAs($user)
            ->get(route('error-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('ErrorLog/Index'));
    }

    public function test_it_parses_log_entries_newest_first_with_stack_trace_as_details(): void
    {
        File::put($this->logPath, implode("\n", [
            '[2026-10-30 09:00:00] production.INFO: first entry',
            '[2026-10-31 10:15:23] production.ERROR: Call to undefined method Foo::bar()',
            '#0 /app/Http/Controllers/FooController.php(42): Foo->bar()',
            '#1 {main}',
            '',
        ]));

        $user = User::factory()->create();
        $user->assignRole('platform-admin');

        $this->actingAs($user)
            ->get(route('error-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ErrorLog/Index')
                ->has('entries', 2)
                ->where('entries.0.level', 'ERROR')
                ->where('entries.0.message', 'Call to undefined method Foo::bar()')
                ->where('entries.0.details', fn ($details) => str_contains($details, 'FooController.php'))
                ->where('entries.1.level', 'INFO'));
    }
}
