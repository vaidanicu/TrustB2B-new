<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * GDPR right to erasure, approval-queue version: a participant marks their
 * own row via their signed link, the organization's admin reviews and
 * either approves (deletes, via the existing destroy()) or denies
 * (clears the flag) — never an immediate, unreviewed self-service delete.
 */
class ParticipantDeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrlFor(Participant $participant): string
    {
        return URL::temporarySignedRoute(
            'participants.show',
            now()->addDays(14),
            ['participant' => $participant->id],
        );
    }

    public function test_a_participant_can_request_deletion_from_their_own_link(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->attending()->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $url = $this->signedUrlFor($participant);

        $this->patch($url)->assertRedirect($url);

        $this->assertNotNull($participant->fresh()->deletion_requested_at);
    }

    public function test_the_signed_link_shows_the_deletion_requested_page_afterward(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->attending()->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $url = $this->signedUrlFor($participant);
        $this->patch($url);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/DeletionRequested'));
    }

    public function test_a_participant_who_requested_deletion_can_no_longer_respond(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
        ]);

        $url = $this->signedUrlFor($participant);
        $this->patch($url);

        $this->post($url, ['name' => 'Someone else', 'attending' => true]);

        $this->assertSame('invited', $participant->fresh()->status);
        $this->assertNull($participant->fresh()->responded_at);
    }

    public function test_an_organizer_sees_the_pending_request_on_the_status_board(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'deletion_requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('participants.status', $event))
            ->assertInertia(fn ($page) => $page->where('participants.0.deletionRequested', true));
    }

    public function test_an_organizer_can_approve_a_deletion_request(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'deletion_requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('participants.destroy', [$event, $participant]))
            ->assertRedirect();

        $this->assertNull($participant->fresh());
    }

    public function test_an_organizer_can_deny_a_deletion_request(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'deletion_requested_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('participants.deny-deletion', [$event, $participant]))
            ->assertRedirect();

        $fresh = $participant->fresh();
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->deletion_requested_at);
    }
}
