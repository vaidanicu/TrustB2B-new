<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Console\Commands\ProcessEventRetentionCommand;
use App\Mail\EventPackageReady;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * ProcessEventRetentionCommand applies each event's end_of_cycle_action once
 * Event::retentionDeadline() passes — 'delete' wipes the gallery outright,
 * 'export' builds a password-protected archive and only deletes the
 * originals once the recipient confirms receipt through
 * EventExportController, never on the command's own schedule.
 */
class EventRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function eventWithGalleryPhoto(array $attributes = [], array $organizationAttributes = []): Event
    {
        $organization = Organization::factory()->create($organizationAttributes);
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            ...$attributes,
        ]);
        $admin = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($admin, 'customer-admin', 'test');

        $this->actingAs($admin)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertRedirect();

        return $event->fresh();
    }

    public function test_delete_action_removes_gallery_media_once_the_deadline_has_passed(): void
    {
        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'delete',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();

        $event->refresh();
        $this->assertTrue($event->getMedia('gallery')->isEmpty());
        $this->assertNotNull($event->retention_processed_at);
    }

    public function test_delete_action_does_not_touch_events_before_their_deadline(): void
    {
        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'delete',
            'ends_at' => now()->subDays(10),
            'retention_days' => 90,
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();

        $event->refresh();
        $this->assertCount(1, $event->getMedia('gallery'));
        $this->assertNull($event->retention_processed_at);
    }

    public function test_export_action_builds_a_package_and_emails_the_client_contact(): void
    {
        Mail::fake();

        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'export',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
            'client_contact_email' => 'client@example.test',
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();

        $event->refresh();
        $this->assertNotNull($event->export_path);
        $this->assertNotNull($event->export_password);
        $this->assertNotNull($event->retention_processed_at);
        // Nothing is deleted yet — only a confirmation from the recipient does that.
        $this->assertCount(1, $event->getMedia('gallery'));

        Mail::assertSent(EventPackageReady::class, fn (EventPackageReady $mail) => $mail->hasTo('client@example.test'));
    }

    public function test_export_action_without_any_recipient_email_is_retried_later(): void
    {
        Mail::fake();

        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'export',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
            'client_contact_email' => null,
            'data_protection_contact' => null,
        ], ['data_protection_contact_email' => null]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();

        $event->refresh();
        $this->assertNotNull($event->export_path);
        $this->assertNull($event->retention_processed_at);
        Mail::assertNothingSent();
    }

    public function test_export_action_with_no_photos_is_marked_processed_without_building_anything(): void
    {
        Mail::fake();

        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'end_of_cycle_action' => 'export',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
            'client_contact_email' => 'client@example.test',
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();

        $event->refresh();
        $this->assertNull($event->export_path);
        $this->assertNotNull($event->retention_processed_at);
        Mail::assertNothingSent();
    }

    public function test_confirming_receipt_deletes_the_originals_and_the_archive(): void
    {
        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'export',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
            'client_contact_email' => 'client@example.test',
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();
        $event->refresh();
        $exportPath = $event->export_path;
        Storage::disk('local')->assertExists($exportPath);

        $signedUrl = URL::temporarySignedRoute('events.export.show', now()->addDay(), ['event' => $event->id]);
        $this->post($signedUrl)->assertRedirect();

        $event->refresh();
        $this->assertNotNull($event->export_confirmed_at);
        $this->assertNull($event->export_path);
        $this->assertNull($event->export_password);
        $this->assertTrue($event->getMedia('gallery')->isEmpty());
        Storage::disk('local')->assertMissing($exportPath);
    }

    public function test_the_download_link_404s_once_the_package_is_confirmed(): void
    {
        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'export',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
            'client_contact_email' => 'client@example.test',
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();
        $event->refresh();

        $showUrl = URL::temporarySignedRoute('events.export.show', now()->addDay(), ['event' => $event->id]);
        $this->post($showUrl)->assertRedirect();

        $downloadUrl = URL::temporarySignedRoute('events.export.download', now()->addDay(), ['event' => $event->id]);
        $this->get($downloadUrl)->assertNotFound();
    }

    public function test_the_package_page_shows_the_password_only_before_confirmation(): void
    {
        $event = $this->eventWithGalleryPhoto([
            'end_of_cycle_action' => 'export',
            'ends_at' => now()->subDays(100),
            'retention_days' => 60,
            'client_contact_email' => 'client@example.test',
        ]);

        $this->artisan(ProcessEventRetentionCommand::class)->assertSuccessful();
        $event->refresh();

        $showUrl = URL::temporarySignedRoute('events.export.show', now()->addDay(), ['event' => $event->id]);

        $this->get($showUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Events/PackageDelivery')
                ->where('confirmed', false)
                ->where('password', $event->export_password)
            );

        $this->post($showUrl)->assertRedirect();

        $this->get($showUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('confirmed', true)
                ->where('password', null)
                ->where('downloadUrl', null)
            );
    }
}
