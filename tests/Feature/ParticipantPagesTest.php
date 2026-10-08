<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faza 2 / Pachet A preview pages (roadmap §12): reachable without a real
 * Invitation/Participant model yet. The one thing worth locking down now,
 * before that model exists: the invitation and confirmation pages must
 * stay reachable by a guest — a participant is never logged into the
 * admin app — while the organizer's status board stays behind auth.
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
        $this->get(route('participants.status'))->assertRedirect(route('login'));
    }

    public function test_status_board_renders_for_an_organization_scoped_user(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->get(route('participants.status'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Participants/StatusBoard'));
    }

    public function test_status_board_is_forbidden_for_a_franchise_scoped_user(): void
    {
        $user = User::factory()->forFranchisePartner()->create();

        $this->actingAs($user)->get(route('participants.status'))->assertForbidden();
    }
}
