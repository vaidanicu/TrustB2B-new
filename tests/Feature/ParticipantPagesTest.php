<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faza 2 / Pachet A preview pages (roadmap §12). The static demo
 * (invitation/confirmation, no row, no signature) stays reachable by a
 * guest. The status board is AP10-13 now — real, per-event, behind auth —
 * so it needs a real Event to bind, unlike when this test was first
 * written.
 */
class ParticipantPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_page_is_reachable_by_a_guest(): void
    {
        $this->get(route('participants.invitation'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/Invitation')
                ->has('event.name')
            );
    }

    public function test_confirmation_page_is_reachable_by_a_guest(): void
    {
        $this->get(route('participants.confirmation', ['nume' => 'Maria', 'particip' => 1, 'poze' => 0]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/Confirmation')
                ->where('participantName', 'Maria')
                ->where('attending', true)
                ->where('photoConsent', false)
            );
    }

    public function test_status_board_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $this->get(route('participants.status', $event))->assertRedirect(route('login'));
    }

    public function test_status_board_renders_for_an_organization_scoped_user(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $organization->franchise_partner_id]);

        $this->actingAs($user)
            ->get(route('participants.status', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/StatusBoard'));
    }

    public function test_status_board_is_forbidden_for_a_franchise_scoped_user(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->create(['franchise_partner_id' => $franchisePartner->id]);
        $event = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();

        $this->actingAs($user)->get(route('participants.status', $event))->assertForbidden();
    }
}
