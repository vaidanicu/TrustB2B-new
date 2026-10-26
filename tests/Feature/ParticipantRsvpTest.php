<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * AP10-13, participant side: the real signed-link flow. Deliberately no
 * actingAs() anywhere here — a participant following their own link has no
 * session at all, which is the entire point of show()/respond() using
 * Participant::withoutGlobalScopes() instead of route-model-binding (see
 * ParticipantController's docblock).
 */
class ParticipantRsvpTest extends TestCase
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

    public function test_visiting_a_valid_link_shows_the_invitation_and_marks_it_opened(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'name' => 'Andrei Popescu',
            'status' => 'invited',
        ]);

        $this->get($this->signedUrlFor($participant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/Invitation')
                ->where('participantName', 'Andrei Popescu')
            );

        $this->assertSame('opened', $participant->fresh()->status);
    }

    public function test_a_link_without_a_valid_signature_is_rejected(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
        ]);

        $this->get('/invitatie/'.$participant->id)->assertForbidden();
    }

    public function test_responding_attending_persists_the_rsvp_and_then_shows_the_confirmation(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
        ]);

        $url = $this->signedUrlFor($participant);

        $this->post($url, ['name' => 'Maria Ionescu', 'attending' => true, 'photo_consent' => true])
            ->assertRedirect($url);

        $participant->refresh();
        $this->assertSame('attending', $participant->status);
        $this->assertTrue($participant->photo_consent);
        $this->assertNotNull($participant->responded_at);

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/Confirmation')
                ->where('participantName', 'Maria Ionescu')
                ->where('attending', true)
                ->where('photoConsent', true)
            );
    }

    public function test_declining_clears_the_photo_consent(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
        ]);

        $url = $this->signedUrlFor($participant);

        $this->post($url, ['name' => 'Radu Constantin', 'attending' => false])->assertRedirect($url);

        $participant->refresh();
        $this->assertSame('declined', $participant->status);
        $this->assertNull($participant->photo_consent);
    }

    public function test_attending_requires_accepting_the_gdpr_contract_when_the_event_has_one(): void
    {
        $event = Event::factory()->create(['gdpr_contract_text' => 'Sunt de acord cu prelucrarea datelor mele.']);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
        ]);

        $url = $this->signedUrlFor($participant);

        $this->post($url, ['name' => 'Maria Ionescu', 'attending' => true])
            ->assertSessionHasErrors('gdpr_accepted');

        $this->assertNull($participant->fresh()->responded_at);
    }

    public function test_accepting_the_gdpr_contract_records_the_acceptance_timestamp_and_ip(): void
    {
        $event = Event::factory()->create(['gdpr_contract_text' => 'Sunt de acord cu prelucrarea datelor mele.']);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
        ]);

        $url = $this->signedUrlFor($participant);

        $this->post($url, ['name' => 'Maria Ionescu', 'attending' => true, 'gdpr_accepted' => true])
            ->assertRedirect($url);

        $participant->refresh();
        $this->assertSame('attending', $participant->status);
        $this->assertNotNull($participant->gdpr_accepted_at);
        $this->assertNotNull($participant->gdpr_accepted_ip);
    }

    public function test_declining_does_not_require_gdpr_acceptance(): void
    {
        $event = Event::factory()->create(['gdpr_contract_text' => 'Sunt de acord cu prelucrarea datelor mele.']);
        $participant = Participant::factory()->for($event)->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'status' => 'invited',
        ]);

        $url = $this->signedUrlFor($participant);

        $this->post($url, ['name' => 'Radu Constantin', 'attending' => false])
            ->assertRedirect($url);

        $participant->refresh();
        $this->assertSame('declined', $participant->status);
        $this->assertNull($participant->gdpr_accepted_at);
    }

    public function test_resubmitting_an_already_recorded_response_does_not_overwrite_it(): void
    {
        $event = Event::factory()->create();
        $participant = Participant::factory()->for($event)->attending()->create([
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'name' => 'Elena Vasile',
        ]);

        $url = $this->signedUrlFor($participant);

        $this->post($url, ['name' => 'Cu totul alt nume', 'attending' => false])->assertRedirect($url);

        $this->assertSame('Elena Vasile', $participant->fresh()->name);
        $this->assertSame('attending', $participant->fresh()->status);
    }
}
