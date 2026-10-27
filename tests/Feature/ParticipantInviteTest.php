<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Mail\ParticipantInvited;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * AP10-13, organizer side: adding a participant to one of their own events.
 * Participant uses BelongsToOrganization same as Event, so this is that
 * trait's first live test outside Event.
 */
class ParticipantInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_invite_a_participant_and_receives_an_invitation_link(): void
    {
        Mail::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $response = $this->actingAs($user)->post(route('participants.store', $event), [
            'name' => 'Andrei Popescu',
            'email' => 'andrei.popescu@exemplu.ro',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('invitationUrl');

        // The session having it isn't enough on its own — HandleInertiaRequests
        // has to actually forward it as a page prop, or the banner the
        // organizer is supposed to see never renders. This regressed once
        // already (a hand-picked 'flash' key list silently dropped it).
        $this->actingAs($user)
            ->get(route('participants.status', $event))
            ->assertInertia(fn ($page) => $page->where('flash.invitationUrl', fn ($url) => str_contains($url, '/invitatie/')));

        $participant = Participant::sole();
        $this->assertSame($event->id, $participant->event_id);
        $this->assertSame($organization->id, $participant->organization_id);
        $this->assertSame($organization->franchise_partner_id, $participant->franchise_partner_id);
        $this->assertSame('invited', $participant->status);
        $this->assertNull($participant->responded_at);

        Mail::assertSent(ParticipantInvited::class, fn (ParticipantInvited $mail) => $mail->hasTo('andrei.popescu@exemplu.ro')
            && $mail->participant->is($participant));
    }

    public function test_an_invitation_stores_address_and_company_and_generates_a_checkin_code(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $this->actingAs($user)->post(route('participants.store', $event), [
            'name' => 'Andrei Popescu',
            'email' => 'andrei.popescu@exemplu.ro',
            'address' => 'Str. Exemplu 1, Cluj-Napoca',
            'company_name' => 'Exemplu SRL',
        ]);

        $participant = Participant::sole();
        $this->assertSame('Str. Exemplu 1, Cluj-Napoca', $participant->address);
        $this->assertSame('Exemplu SRL', $participant->company_name);
        $this->assertNotNull($participant->checkin_code);
        $this->assertSame(6, strlen($participant->checkin_code));
    }

    public function test_the_participant_is_still_created_when_the_invitation_email_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP unreachable'));

        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $response = $this->actingAs($user)->post(route('participants.store', $event), [
            'name' => 'Andrei Popescu',
            'email' => 'andrei.popescu@exemplu.ro',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('invitationUrl');
        $response->assertSessionHas('invitationEmailSent', false);
        $this->assertSame(1, Participant::count());
    }

    public function test_the_same_email_cannot_be_invited_twice_to_the_same_event(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'email' => 'andrei.popescu@exemplu.ro',
        ]);

        $response = $this->actingAs($user)->post(route('participants.store', $event), [
            'name' => 'Alt Nume',
            'email' => 'andrei.popescu@exemplu.ro',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, Participant::count());
    }

    public function test_an_organizer_cannot_invite_a_participant_to_another_organizations_event(): void
    {
        $otherOrganization = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrganization)->create(['franchise_partner_id' => $otherOrganization->franchise_partner_id]);

        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->post(route('participants.store', $otherEvent), ['name' => 'x', 'email' => 'x@example.test'])
            ->assertNotFound();

        $this->assertSame(0, Participant::count());
    }

    public function test_the_status_board_lists_only_the_events_own_participants(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $otherEvent = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'name' => 'Participant A',
        ]);
        Participant::factory()->for($otherEvent)->create([
            'organization_id' => $otherEvent->organization_id,
            'franchise_partner_id' => $otherEvent->franchise_partner_id,
            'name' => 'Participant B',
        ]);

        $this->actingAs($user)
            ->get(route('participants.status', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/StatusBoard')
                ->has('participants', 1)
                ->where('participants.0.name', 'Participant A')
            );
    }

    public function test_an_organizer_can_delete_a_participant(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $this->actingAs($user)
            ->delete(route('participants.destroy', [$event, $participant]))
            ->assertRedirect();

        $this->assertSame(0, Participant::count());
    }

    public function test_an_organizer_cannot_delete_a_participant_from_another_organizations_event(): void
    {
        $otherOrganization = Organization::factory()->create();
        $otherEvent = Event::factory()->for($otherOrganization)->create(['franchise_partner_id' => $otherOrganization->franchise_partner_id]);
        $otherParticipant = Participant::factory()->for($otherEvent)->create([
            'organization_id' => $otherEvent->organization_id,
            'franchise_partner_id' => $otherEvent->franchise_partner_id,
        ]);

        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->delete(route('participants.destroy', [$otherEvent, $otherParticipant]))
            ->assertNotFound();

        // Participant::count() alone would be scoped to the ACTING user's
        // own (empty) organization after this request and pass either way
        // — withoutGlobalScopes() is what actually proves the other org's
        // row survived.
        $this->assertSame(1, Participant::withoutGlobalScopes()->count());
    }

    public function test_a_franchise_partner_admin_can_invite_a_participant_to_a_client_event_they_created(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $response = $this->actingAs($user)->post(route('participants.store', $event), [
            'name' => 'Andrei Popescu',
            'email' => 'andrei.popescu@exemplu.ro',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Participant::count());

        $this->actingAs($user)
            ->get(route('participants.status', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/StatusBoard'));
    }

    public function test_a_franchise_partner_admin_cannot_invite_a_participant_to_another_franchises_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $otherOrganization = Organization::factory()->for($otherFranchise)->create();
        $otherEvent = Event::factory()->for($otherOrganization)->create(['franchise_partner_id' => $otherFranchise->id]);

        $user = User::factory()->forFranchisePartner($ownFranchise)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        $this->actingAs($user)
            ->post(route('participants.store', $otherEvent), ['name' => 'x', 'email' => 'x@example.test'])
            ->assertNotFound();

        $this->assertSame(0, Participant::count());
    }

    public function test_a_restaurant_event_cannot_have_participants_invited(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'is_restaurant' => true,
        ]);

        $this->actingAs($user)
            ->post(route('participants.store', $event), ['name' => 'x', 'email' => 'x@example.test'])
            ->assertNotFound();

        $this->assertSame(0, Participant::count());
    }

    public function test_a_restaurant_events_status_board_is_not_reachable(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $organization->franchise_partner_id,
            'is_restaurant' => true,
        ]);

        $this->actingAs($user)->get(route('participants.status', $event))->assertNotFound();
    }
}
