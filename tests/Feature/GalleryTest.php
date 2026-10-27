<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The private event gallery: upload (media.upload) and publish
 * (media.publish) are separate permissions on purpose — see
 * GalleryController's docblock for why a photographer must never be able
 * to decide what participants see.
 */
class GalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(Organization $organization, string $role): User
    {
        $user = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($user, $role, 'test');

        return $user;
    }

    public function test_a_customer_admin_can_upload_a_photo_as_a_draft(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $admin = $this->userWithRole($organization, 'customer-admin');

        $this->actingAs($admin)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertRedirect();

        $photo = $event->fresh()->getMedia('gallery')->sole();
        $this->assertFalse((bool) $photo->getCustomProperty('published'));
    }

    public function test_a_photographer_can_upload_but_not_publish(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $photographer = $this->userWithRole($organization, 'photographer');

        $this->actingAs($photographer)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertRedirect();

        $photo = $event->fresh()->getMedia('gallery')->sole();

        $this->actingAs($photographer)
            ->patch(route('gallery.publish', [$event, $photo]))
            ->assertForbidden();
    }

    public function test_a_customer_admin_can_publish_an_uploaded_photo(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $admin = $this->userWithRole($organization, 'customer-admin');

        $event->addMedia(UploadedFile::fake()->image('photo.jpg'))
            ->withCustomProperties(['published' => false])
            ->toMediaCollection('gallery');
        $photo = $event->getMedia('gallery')->sole();

        $this->actingAs($admin)
            ->patch(route('gallery.publish', [$event, $photo]))
            ->assertRedirect();

        $this->assertTrue((bool) $photo->fresh()->getCustomProperty('published'));
    }

    public function test_a_platform_admin_cannot_upload_or_view_the_gallery(): void
    {
        $event = Event::factory()->create();
        $platformAdmin = User::factory()->create();
        $platformAdmin->assignRole('platform-admin');

        $this->actingAs($platformAdmin)->get(route('gallery.index', $event))->assertForbidden();
        $this->actingAs($platformAdmin)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertForbidden();
    }

    public function test_an_attending_participant_sees_only_published_photos(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true])
            ->toMediaCollection('gallery');
        $event->addMedia(UploadedFile::fake()->image('draft.jpg'))
            ->withCustomProperties(['published' => false])
            ->toMediaCollection('gallery');

        $participant = Participant::factory()->for($event)->attending()->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'participants.show',
            now()->addDay(),
            ['participant' => $participant->id],
        );

        $this->get($signedUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/Confirmation')
                ->has('photos', 1)
                ->where('photos.0.fileName', 'published.jpg'));
    }

    public function test_a_declined_participant_sees_no_photos(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true])
            ->toMediaCollection('gallery');

        $participant = Participant::factory()->for($event)->declined()->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'participants.show',
            now()->addDay(),
            ['participant' => $participant->id],
        );

        $this->get($signedUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/Confirmation')->has('photos', 0));
    }

    public function test_the_designated_photographer_can_upload_to_their_assigned_event(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $photographer = $this->userWithRole($organization, 'photographer');
        $event->forceFill(['photographer_user_id' => $photographer->id])->save();

        $this->actingAs($photographer)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertRedirect();

        $this->assertSame(1, $event->fresh()->getMedia('gallery')->count());
    }

    public function test_a_different_photographer_cannot_upload_to_an_event_they_are_not_assigned_to(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $assignedPhotographer = $this->userWithRole($organization, 'photographer');
        $otherPhotographer = $this->userWithRole($organization, 'photographer');
        $event->forceFill(['photographer_user_id' => $assignedPhotographer->id])->save();

        $this->actingAs($otherPhotographer)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertForbidden();

        $this->assertSame(0, $event->fresh()->getMedia('gallery')->count());
    }

    public function test_a_customer_admin_can_still_upload_when_a_photographer_is_assigned(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $photographer = $this->userWithRole($organization, 'photographer');
        $event->forceFill(['photographer_user_id' => $photographer->id])->save();
        $admin = $this->userWithRole($organization, 'customer-admin');

        $this->actingAs($admin)
            ->post(route('gallery.store', $event), ['photos' => [UploadedFile::fake()->image('photo.jpg')]])
            ->assertRedirect();

        $this->assertSame(1, $event->fresh()->getMedia('gallery')->count());
    }

    public function test_the_public_gallery_link_shows_only_published_photos_and_a_qr_code(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $admin = $this->userWithRole($organization, 'customer-admin');

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true])
            ->toMediaCollection('gallery');
        $event->addMedia(UploadedFile::fake()->image('draft.jpg'))
            ->withCustomProperties(['published' => false])
            ->toMediaCollection('gallery');

        $this->actingAs($admin)
            ->get(route('gallery.index', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/Gallery')
                ->where('publicGalleryUrl', fn ($url) => str_contains($url, '/evenimente/'))
                ->where('publicGalleryQrSvg', fn ($svg) => str_contains($svg, '<svg')));

        $signedUrl = URL::temporarySignedRoute('gallery.public', now()->addDay(), ['event' => $event->id]);

        $this->get($signedUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/PublicGallery')->has('photos', 1));
    }

    public function test_anyone_with_the_public_link_can_request_a_photos_removal(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true])
            ->toMediaCollection('gallery');
        $photo = $event->getMedia('gallery')->sole();

        $signedUrl = URL::temporarySignedRoute('gallery.public', now()->addDay(), ['event' => $event->id]);

        $this->post($signedUrl, ['media_id' => $photo->id, 'reason' => 'Nu vreau să apar în poze.'])
            ->assertRedirect();

        $photo->refresh();
        $this->assertTrue((bool) $photo->getCustomProperty('deletion_requested'));
        $this->assertSame('Nu vreau să apar în poze.', $photo->getCustomProperty('deletion_reason'));
    }

    public function test_a_customer_admin_can_approve_a_removal_request_and_delete_the_photo(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $admin = $this->userWithRole($organization, 'customer-admin');

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true, 'deletion_requested' => true, 'deletion_reason' => 'motiv'])
            ->toMediaCollection('gallery');
        $photo = $event->getMedia('gallery')->sole();

        $this->actingAs($admin)
            ->patch(route('gallery.approve-removal', [$event, $photo]))
            ->assertRedirect();

        $this->assertSame(0, $event->fresh()->getMedia('gallery')->count());
    }

    public function test_a_customer_admin_can_deny_a_removal_request_and_keep_the_photo(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $admin = $this->userWithRole($organization, 'customer-admin');

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true, 'deletion_requested' => true, 'deletion_reason' => 'motiv'])
            ->toMediaCollection('gallery');
        $photo = $event->getMedia('gallery')->sole();

        $this->actingAs($admin)
            ->patch(route('gallery.deny-removal', [$event, $photo]))
            ->assertRedirect();

        $photo->refresh();
        $this->assertSame(1, $event->fresh()->getMedia('gallery')->count());
        $this->assertFalse((bool) $photo->getCustomProperty('deletion_requested'));
    }

    public function test_a_photographer_cannot_moderate_removal_requests(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $photographer = $this->userWithRole($organization, 'photographer');

        $event->addMedia(UploadedFile::fake()->image('published.jpg'))
            ->withCustomProperties(['published' => true, 'deletion_requested' => true])
            ->toMediaCollection('gallery');
        $photo = $event->getMedia('gallery')->sole();

        $this->actingAs($photographer)
            ->patch(route('gallery.approve-removal', [$event, $photo]))
            ->assertForbidden();
    }
}
