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
}
