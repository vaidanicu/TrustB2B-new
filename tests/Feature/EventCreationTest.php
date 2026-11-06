<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Mail\EventCreatedForClientContact;
use App\Mail\PhotographerAssignedToEvent;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * AP07/08: events are set up by the agency (franchise-partner-admin or
 * platform-/franchisor-admin) on behalf of a client — never by the
 * client's own staff, since the GDPR contract participants must accept
 * is drafted here. See EventController's docblock for why.
 */
class EventCreationTest extends TestCase
{
    use RefreshDatabase;

    private function franchisePartnerAdmin(FranchisePartner $franchisePartner): User
    {
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        return $user;
    }

    public function test_a_franchise_partner_admin_can_create_an_event_for_one_of_their_clients(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal — clienți 2026',
            'purpose' => 'Eveniment de fidelizare',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Prin participare, sunt de acord cu prelucrarea datelor mele.',
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame($franchisePartner->id, $event->franchise_partner_id);
        $this->assertSame($user->id, $event->created_by);
        $this->assertSame('draft', $event->status);
        $this->assertFalse($event->is_restaurant);
        $this->assertNotNull($event->gdpr_contract_text);
    }

    public function test_the_created_flash_message_matches_the_actors_locale(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $user->forceFill(['locale' => 'de'])->save();

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertSessionHas('status', 'Event erstellt: Firmenevent');
    }

    public function test_client_billing_details_are_stored_on_the_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'client_contact_name' => 'Ion Pop',
            'client_contact_email' => 'ion.pop@hotelaurora.ro',
            'client_address' => 'Str. Exemplu 1, Cluj-Napoca',
            'client_tax_number' => 'RO12345678',
        ]);

        $event = Event::sole();
        $this->assertSame('Ion Pop', $event->client_contact_name);
        $this->assertSame('ion.pop@hotelaurora.ro', $event->client_contact_email);
        $this->assertSame('Str. Exemplu 1, Cluj-Napoca', $event->client_address);
        $this->assertSame('RO12345678', $event->client_tax_number);
    }

    public function test_a_gdpr_contract_pdf_can_be_uploaded_alongside_the_text(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'gdpr_contract_file' => UploadedFile::fake()->createWithContent('contract.pdf', '%PDF-1.4 fake pdf content for testing'),
        ]);

        $event = Event::sole();
        $this->assertSame(1, $event->getMedia('gdpr-contract')->count());
    }

    public function test_a_photographer_email_creates_an_account_and_links_it_to_the_event(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'photographer' => 'Grup Media Est',
            'photographer_email' => 'fotograf@exemplu.ro',
        ]);

        $event = Event::sole();
        $photographer = User::where('email', 'fotograf@exemplu.ro')->sole();

        $this->assertSame($photographer->id, $event->photographer_user_id);
        $this->assertTrue($photographer->hasRole('photographer'));
        $this->assertNotNull($photographer->email_verified_at);

        Mail::assertSent(PhotographerAssignedToEvent::class, fn (PhotographerAssignedToEvent $mail) => $mail->hasTo('fotograf@exemplu.ro')
            && $mail->event->is($event)
            && $mail->password !== null);
    }

    public function test_an_existing_photographer_account_is_reused_not_recreated(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $existingPhotographer = User::factory()->create(['email' => 'fotograf@exemplu.ro']);

        $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'photographer_email' => 'fotograf@exemplu.ro',
        ]);

        $this->assertSame(1, User::where('email', 'fotograf@exemplu.ro')->count());
        $this->assertSame($existingPhotographer->id, Event::sole()->photographer_user_id);

        // Unlike before, this now fires every time regardless — same
        // reasoning as the client-contact notification — but without a
        // password, since the existing account already knows how to log in.
        Mail::assertSent(PhotographerAssignedToEvent::class, fn (PhotographerAssignedToEvent $mail) => $mail->hasTo('fotograf@exemplu.ro')
            && $mail->password === null);
    }

    public function test_a_client_contact_email_creates_an_account_and_is_notified(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Turneu de fotbal',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'client_contact_name' => 'Ion Pop',
            'client_contact_email' => 'ion@paleus.ro',
        ]);

        $event = Event::sole();
        $contact = User::where('email', 'ion@paleus.ro')->sole();

        $this->assertTrue($contact->hasRole('client-contact'));
        $this->assertSame($organization->id, $contact->organization_id);
        $this->assertNotNull($contact->email_verified_at);

        Mail::assertSent(EventCreatedForClientContact::class, fn (EventCreatedForClientContact $mail) => $mail->hasTo('ion@paleus.ro')
            && $mail->event->is($event)
            && $mail->password !== null);
    }

    public function test_an_existing_client_contact_is_reused_and_still_notified_without_a_password(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $existingContact = User::factory()->create(['email' => 'ion@paleus.ro']);

        $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'A doua ediție',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'client_contact_email' => 'ion@paleus.ro',
        ]);

        $this->assertSame(1, User::where('email', 'ion@paleus.ro')->count());
        $this->assertTrue($existingContact->fresh()->hasRole('client-contact'));

        // Unlike the photographer/partner reuse pattern, this one DOES
        // still fire every time — the whole point is confirming THIS
        // event's creation, not just handing out credentials once.
        Mail::assertSent(EventCreatedForClientContact::class, fn (EventCreatedForClientContact $mail) => $mail->hasTo('ion@paleus.ro')
            && $mail->password === null);
    }

    public function test_a_real_staff_members_email_cannot_be_used_as_the_events_photographer(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $staffMember = User::factory()->forOrganization($organization)->create(['email' => 'staff@exemplu.ro']);
        (new AssignRole)->grant($staffMember, 'customer-admin', 'test');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'photographer_email' => 'staff@exemplu.ro',
        ]);

        $response->assertSessionHasErrors('photographer_email');
        $this->assertSame(0, Event::count());
        $this->assertFalse($staffMember->fresh()->hasRole('photographer'));
    }

    public function test_a_real_staff_members_email_cannot_be_used_as_the_client_contact(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $staffMember = User::factory()->forOrganization($organization)->create(['email' => 'staff@exemplu.ro']);
        (new AssignRole)->grant($staffMember, 'event-manager', 'test');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'client_contact_email' => 'staff@exemplu.ro',
        ]);

        $response->assertSessionHasErrors('client_contact_email');
        $this->assertSame(0, Event::count());
        $this->assertFalse($staffMember->fresh()->hasRole('client-contact'));
    }

    public function test_an_account_that_is_only_a_photographer_can_still_be_reused_as_photographer(): void
    {
        Mail::fake();
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $photographer = User::factory()->create(['email' => 'fotograf@exemplu.ro']);
        (new AssignRole)->grant($photographer, 'photographer', 'test');

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'photographer_email' => 'fotograf@exemplu.ro',
        ]);

        $response->assertSessionDoesntHaveErrors('photographer_email');
        $this->assertSame($photographer->id, Event::sole()->photographer_user_id);
    }

    public function test_a_restaurant_event_does_not_require_gdpr_contract_text(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Seară la restaurant',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'is_restaurant' => true,
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertTrue($event->is_restaurant);
        $this->assertNull($event->gdpr_contract_text);
    }

    public function test_a_non_restaurant_event_requires_gdpr_contract_text(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment fără contract',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
        ]);

        $response->assertSessionHasErrors('gdpr_contract_text');
        $this->assertSame(0, Event::count());
    }

    public function test_an_organization_scoped_user_cannot_create_an_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $organization = Organization::factory()->create();
        $user = User::factory()->forOrganization($organization)->create();
        (new AssignRole)->grant($user, 'customer-admin', 'test');

        $this->actingAs($user)->post(route('events.store'), ['organization_id' => $organization->id, 'name' => 'x'])->assertForbidden();
        $this->assertSame(0, Event::count());
    }

    public function test_a_franchise_partner_admin_cannot_create_an_event_for_another_franchises_client(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $otherOrganization = Organization::factory()->for($otherFranchise)->create();
        $user = $this->franchisePartnerAdmin($ownFranchise);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $otherOrganization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertNotFound();
        $this->assertSame(0, Event::count());
    }

    public function test_dashboard_lists_only_the_users_own_organization_events(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $eventA = Event::factory()->for($orgA)->create(['franchise_partner_id' => $orgA->franchise_partner_id, 'name' => 'Eveniment A']);
        Event::factory()->for($orgB)->create(['franchise_partner_id' => $orgB->franchise_partner_id, 'name' => 'Eveniment B']);

        $userA = User::factory()->forOrganization($orgA)->create();

        $this->actingAs($userA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboard')
                ->has('events', 1)
                ->where('events.0.name', $eventA->name)
            );
    }

    public function test_a_30_day_retention_is_accepted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 30,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertRedirect(route('organizations.events', $organization));
        $this->assertSame(30, Event::sole()->retention_days);
    }

    public function test_an_invalid_retention_value_is_rejected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 45, // only 30, 60 or 90 are allowed
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertSessionHasErrors('retention_days');
        $this->assertSame(0, Event::count());
    }

    public function test_a_suspended_clients_organization_is_not_offered_on_the_create_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        Organization::factory()->for($franchisePartner)->create(['name' => 'Suspended Co', 'contract_status' => 'suspended']);
        Organization::factory()->for($franchisePartner)->create(['name' => 'Active Co', 'contract_status' => 'active']);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)
            ->get(route('events.create'))
            ->assertInertia(fn ($page) => $page->has('organizations', 1)->where('organizations.0.name', 'Active Co'));
    }

    public function test_an_event_cannot_be_created_for_a_suspended_client(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create(['contract_status' => 'suspended']);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Eveniment test',
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => '2026-12-01',
            'ends_at' => '2026-12-01',
            'retention_days' => 90,
            'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertSame(0, Event::count());
    }
}
