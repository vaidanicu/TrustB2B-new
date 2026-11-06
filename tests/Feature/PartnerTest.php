<?php

namespace Tests\Feature;

use App\Actions\Tenancy\AssignRole;
use App\Mail\StaffAccountCreated;
use App\Models\Event;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Partner: a regional reseller assigned per-event as the sales-responsible
 * party (Event.partner_id) — a different concept from FranchisePartner,
 * which is a tenancy boundary. Managed by the agency (partners.manage),
 * never by a client's own staff. See Partner's own docblock.
 */
class PartnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function franchisePartnerAdmin(FranchisePartner $franchisePartner): User
    {
        $user = User::factory()->forFranchisePartner($franchisePartner)->create();
        (new AssignRole)->grant($user, 'franchise-partner-admin', 'test');

        return $user;
    }

    private function partnerAccount(Partner $partner): User
    {
        $user = User::factory()->forFranchisePartner($partner->franchisePartner)->create();
        (new AssignRole)->grant($user, 'partner', 'test');
        $partner->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    public function test_a_franchise_partner_admin_can_create_a_partner(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('partners.store'), [
            'name' => 'Auras srl',
            'country' => 'RO',
        ]);

        $response->assertRedirect(route('partners.index'));

        $partner = Partner::sole();
        $this->assertSame('Auras srl', $partner->name);
        $this->assertSame($franchisePartner->id, $partner->franchise_partner_id);
    }

    public function test_creating_a_partner_with_a_new_email_flashes_the_password_as_a_fallback(): void
    {
        Mail::fake();

        $franchisePartner = FranchisePartner::factory()->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('partners.store'), [
            'name' => 'Csaba',
            'country' => 'RO',
            'email' => 'office-ro@ttc-group.hu',
        ]);

        $response->assertRedirect(route('partners.index'));
        $response->assertSessionHas('newAccountEmail', 'office-ro@ttc-group.hu');
        $response->assertSessionHas('newAccountPassword');

        $account = User::where('email', 'office-ro@ttc-group.hu')->sole();
        $this->assertTrue($account->hasRole('partner'));

        Mail::assertSent(StaffAccountCreated::class, fn (StaffAccountCreated $mail) => $mail->hasTo('office-ro@ttc-group.hu'));
    }

    public function test_creating_a_partner_with_an_existing_email_does_not_flash_a_password(): void
    {
        Mail::fake();

        $franchisePartner = FranchisePartner::factory()->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);
        $existing = User::factory()->create(['email' => 'office-ro@ttc-group.hu']);

        $response = $this->actingAs($user)->post(route('partners.store'), [
            'name' => 'Csaba',
            'country' => 'RO',
            'email' => 'office-ro@ttc-group.hu',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('newAccountPassword');
        $response->assertSessionHas('reusedExistingAccount', 'office-ro@ttc-group.hu');
        $this->assertTrue($existing->fresh()->hasRole('partner'));

        Mail::assertNotSent(StaffAccountCreated::class);
    }

    public function test_a_system_operator_must_pick_a_franchise_partner_explicitly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('platform-admin');

        $response = $this->actingAs($user)->post(route('partners.store'), [
            'name' => 'Auras srl',
            'country' => 'RO',
        ]);

        $response->assertSessionHasErrors('franchise_partner_id');
        $this->assertSame(0, Partner::count());
    }

    public function test_an_organization_scoped_user_cannot_view_or_create_partners(): void
    {
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)->get(route('partners.index'))->assertForbidden();
        $this->actingAs($user)->post(route('partners.store'), ['name' => 'x', 'country' => 'RO'])->assertForbidden();
    }

    public function test_a_franchise_partner_admin_can_edit_a_partners_name_and_country(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create(['name' => 'Gall', 'country' => 'HU']);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)
            ->patch(route('partners.update', $partner), ['name' => 'Gall SRL', 'country' => 'ro'])
            ->assertRedirect(route('partners.index'));

        $fresh = $partner->fresh();
        $this->assertSame('Gall SRL', $fresh->name);
        $this->assertSame('RO', $fresh->country);
    }

    public function test_changing_a_partners_email_relinks_the_account_and_revokes_the_old_one(): void
    {
        Mail::fake();

        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create(['email' => 'old@example.test']);
        $oldUser = $this->partnerAccount($partner);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->patch(route('partners.update', $partner), [
            'name' => $partner->name,
            'country' => $partner->country,
            'email' => 'new@example.test',
        ]);

        $response->assertSessionHas('newAccountEmail', 'new@example.test');
        $response->assertSessionHas('newAccountPassword');

        $this->assertFalse($oldUser->fresh()->hasRole('partner'));

        $newUser = User::where('email', 'new@example.test')->sole();
        $this->assertTrue($newUser->hasRole('partner'));
        $this->assertSame($newUser->id, $partner->fresh()->user_id);
    }

    public function test_removing_a_partners_email_revokes_the_role_and_unlinks_the_account(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create(['email' => 'linked@example.test']);
        $linkedUser = $this->partnerAccount($partner);
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->patch(route('partners.update', $partner), [
            'name' => $partner->name,
            'country' => $partner->country,
            'email' => '',
        ]);

        $this->assertFalse($linkedUser->fresh()->hasRole('partner'));
        $this->assertNull($partner->fresh()->user_id);
    }

    public function test_an_organization_scoped_user_cannot_edit_a_partner(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $user = User::factory()->forOrganization()->create();

        $this->actingAs($user)
            ->patch(route('partners.update', $partner), ['name' => 'x', 'country' => 'RO'])
            ->assertNotFound();
    }

    public function test_a_franchise_partner_admin_can_delete_a_partner_without_an_account(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($user)->delete(route('partners.destroy', $partner))->assertRedirect();

        $this->assertSame(0, Partner::count());
    }

    public function test_deleting_a_partner_with_an_account_only_revokes_the_role_not_the_account(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $admin = $this->franchisePartnerAdmin($franchisePartner);

        $this->actingAs($admin)->delete(route('partners.destroy', $partner))->assertRedirect();

        $this->assertSame(0, Partner::count());
        $this->assertNotNull($partnerUser->fresh());
        $this->assertFalse($partnerUser->fresh()->hasRole('partner'));
    }

    public function test_deleting_a_partner_does_not_delete_its_events_just_the_attribution(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $admin = $this->franchisePartnerAdmin($franchisePartner);
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'partner_id' => $partner->id,
        ]);

        $this->actingAs($admin)->delete(route('partners.destroy', $partner));

        $this->assertNotNull($event->fresh());
        $this->assertNull($event->fresh()->partner_id);
    }

    public function test_deleting_a_partner_linked_to_an_admin_account_keeps_its_admin_role(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create(['name' => 'MM Kft.']);
        $admin = $this->franchisePartnerAdmin($franchisePartner);
        $partner->forceFill(['user_id' => $admin->id])->save();
        (new AssignRole)->grant($admin, 'partner', 'test');

        $this->actingAs($admin)->delete(route('partners.destroy', $partner))->assertRedirect();

        $fresh = $admin->fresh();
        $this->assertFalse($fresh->hasRole('partner'));
        $this->assertTrue($fresh->hasRole('franchise-partner-admin'));
    }

    public function test_an_organization_scoped_user_cannot_delete_a_partner(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $user = User::factory()->forOrganization()->create();

        // Partner's own TenantScope ('organization_id') already excludes
        // this row from route-model-binding's query for an org-scoped
        // actor — 404, not 403, same as every other tenant-scoped model
        // in this app (see EventShowTest's own equivalent case). The same
        // scope would also hide it from a plain Partner::count() run
        // while still "acting as" that org-scoped user, hence
        // withoutGlobalScopes() here.
        $this->actingAs($user)->delete(route('partners.destroy', $partner))->assertNotFound();
        $this->assertSame(1, Partner::withoutGlobalScopes()->count());
    }

    public function test_a_franchise_partner_admin_only_sees_their_own_partners(): void
    {
        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        Partner::factory()->for($ownFranchise)->create(['name' => 'Mine']);
        Partner::factory()->for($otherFranchise)->create(['name' => 'Not mine']);

        $user = $this->franchisePartnerAdmin($ownFranchise);

        $this->actingAs($user)
            ->get(route('partners.index'))
            ->assertInertia(fn ($page) => $page->has('partners', 1)->where('partners.0.name', 'Mine'));
    }

    public function test_an_event_can_be_assigned_the_partner_responsible_for_the_sale(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($franchisePartner)->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $user = $this->franchisePartnerAdmin($franchisePartner);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO', 'language' => 'ro',
            'starts_at' => '2026-12-01', 'ends_at' => '2026-12-01',
            'retention_days' => 90, 'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'client_company_name' => 'Optimedia SRL',
            'partner_id' => $partner->id,
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertSame($partner->id, $event->partner_id);
        $this->assertSame('Optimedia SRL', $event->client_company_name);
    }

    public function test_an_event_cannot_be_assigned_a_partner_from_another_franchise(): void
    {
        $ownFranchise = FranchisePartner::factory()->create();
        $otherFranchise = FranchisePartner::factory()->create();
        $organization = Organization::factory()->for($ownFranchise)->create();
        $foreignPartner = Partner::factory()->for($otherFranchise)->create();
        $user = $this->franchisePartnerAdmin($ownFranchise);

        $response = $this->actingAs($user)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO', 'language' => 'ro',
            'starts_at' => '2026-12-01', 'ends_at' => '2026-12-01',
            'retention_days' => 90, 'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'partner_id' => $foreignPartner->id,
        ]);

        $response->assertNotFound();
        $this->assertSame(0, Event::count());
    }

    public function test_a_partner_account_only_sees_clients_it_has_an_event_with(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);

        $theirClient = Organization::factory()->for($franchisePartner)->create(['name' => 'Theirs']);
        Event::factory()->for($theirClient)->create(['franchise_partner_id' => $franchisePartner->id, 'partner_id' => $partner->id]);

        $otherClient = Organization::factory()->for($franchisePartner)->create(['name' => 'Not theirs']);
        Event::factory()->for($otherClient)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizations/Index')
                ->where('canManage', true)
                ->has('organizations', 1)
                ->where('organizations.0.name', 'Theirs'));
    }

    public function test_a_partner_can_create_a_client_organization(): void
    {
        Mail::fake();

        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);

        $response = $this->actingAs($partnerUser)->post(route('organizations.store'), [
            'name' => 'Hotel Aurora',
            'country' => 'RO',
            'admin_name' => 'Bianca Ionescu',
            'admin_email' => 'bianca@hotelaurora.ro',
        ]);

        $response->assertRedirect(route('organizations.index'));

        $organization = Organization::sole();
        $this->assertSame('Hotel Aurora', $organization->name);
        $this->assertSame($partner->id, $organization->partner_id);
        $this->assertSame($franchisePartner->id, $organization->franchise_partner_id);

        $admin = User::where('email', 'bianca@hotelaurora.ro')->sole();
        $this->assertTrue($admin->hasRole('customer-admin'));
    }

    public function test_a_partner_sees_a_client_it_created_even_with_no_events_yet(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        Organization::factory()->for($franchisePartner)->create(['name' => 'Fresh client', 'partner_id' => $partner->id]);

        $this->actingAs($partnerUser)
            ->get(route('organizations.index'))
            ->assertInertia(fn ($page) => $page->has('organizations', 1)->where('organizations.0.name', 'Fresh client'));
    }

    public function test_a_partner_can_change_the_contract_status_of_its_own_client(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create(['partner_id' => $partner->id, 'contract_status' => 'onboarding']);

        $this->actingAs($partnerUser)
            ->patch(route('organizations.update-status', $organization), ['contract_status' => 'active'])
            ->assertRedirect();

        $this->assertSame('active', $organization->fresh()->contract_status);
    }

    public function test_a_partner_cannot_change_the_contract_status_of_a_client_it_does_not_own(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create(['contract_status' => 'onboarding']);

        $this->actingAs($partnerUser)
            ->patch(route('organizations.update-status', $organization), ['contract_status' => 'active'])
            ->assertForbidden();

        $this->assertSame('onboarding', $organization->fresh()->contract_status);
    }

    public function test_a_partner_sees_every_event_of_a_client_it_owns_not_just_its_own(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create(['partner_id' => $partner->id]);

        Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id, 'partner_id' => $partner->id]);
        Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)
            ->get(route('organizations.events', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('events', 2));
    }

    public function test_a_partner_account_sees_only_its_own_events_for_a_client_with_photographer_and_contact_columns(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();

        $theirEvent = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'partner_id' => $partner->id,
            'photographer' => 'Ion Popescu',
            'client_contact_name' => 'Bianca Ionescu',
            'client_contact_email' => 'bianca@hotelaurora.ro',
        ]);
        Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)
            ->get(route('organizations.events', $organization))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizations/Events')
                ->where('canManage', true)
                ->has('events', 1)
                ->where('events.0.id', $theirEvent->id)
                ->where('events.0.photographerName', 'Ion Popescu')
                ->where('events.0.clientContactEmail', 'bianca@hotelaurora.ro'));
    }

    public function test_a_partner_account_cannot_view_a_clients_events_it_has_no_event_with(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);

        $organization = Organization::factory()->for($franchisePartner)->create();
        Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)->get(route('organizations.events', $organization))->assertForbidden();
    }

    public function test_a_partner_account_can_create_an_event_for_any_client_in_its_franchise(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();

        $response = $this->actingAs($partnerUser)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO', 'language' => 'ro',
            'starts_at' => '2026-12-01', 'ends_at' => '2026-12-01',
            'retention_days' => 90, 'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
        ]);

        $response->assertRedirect(route('organizations.events', $organization));

        $event = Event::sole();
        $this->assertSame($partner->id, $event->partner_id);
        $this->assertSame($partnerUser->id, $event->created_by);
    }

    public function test_a_partner_account_cannot_pick_a_different_partner_for_its_own_event(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $otherPartner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();

        $this->actingAs($partnerUser)->post(route('events.store'), [
            'organization_id' => $organization->id,
            'name' => 'Firmenevent',
            'country' => 'RO', 'language' => 'ro',
            'starts_at' => '2026-12-01', 'ends_at' => '2026-12-01',
            'retention_days' => 90, 'end_of_cycle_action' => 'delete',
            'gdpr_contract_text' => 'Text.',
            'partner_id' => $otherPartner->id,
        ]);

        $this->assertSame($partner->id, Event::sole()->partner_id);
    }

    public function test_a_partner_account_sees_its_own_name_locked_on_the_create_event_page(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create(['name' => 'MM Kft.']);
        $partnerUser = $this->partnerAccount($partner);

        $this->actingAs($partnerUser)
            ->get(route('events.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Events/Create')
                ->where('lockedPartnerName', 'MM Kft.')
                ->where('partners', []));
    }

    public function test_a_partner_account_can_reschedule_or_delete_its_own_event(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
            'partner_id' => $partner->id,
        ]);

        $this->actingAs($partnerUser)
            ->patch(route('events.update', $event), ['starts_at' => '2026-12-15 10:00', 'ends_at' => '2026-12-15 18:00'])
            ->assertRedirect();

        $this->assertSame('2026-12-15 10:00:00', $event->fresh()->starts_at->format('Y-m-d H:i:s'));

        $this->actingAs($partnerUser)->delete(route('events.destroy', $event))->assertRedirect();
        $this->assertNull(Event::find($event->id));
    }

    public function test_a_partner_account_cannot_reschedule_or_delete_someone_elses_event(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();
        $event = Event::factory()->for($organization)->create([
            'franchise_partner_id' => $franchisePartner->id,
        ]);

        $this->actingAs($partnerUser)
            ->patch(route('events.update', $event), ['starts_at' => '2026-12-15 10:00', 'ends_at' => '2026-12-15 18:00'])
            ->assertForbidden();

        $this->actingAs($partnerUser)->delete(route('events.destroy', $event))->assertForbidden();
    }

    public function test_a_partner_account_can_view_its_own_event_but_not_someone_elses(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create();
        $partnerUser = $this->partnerAccount($partner);
        $organization = Organization::factory()->for($franchisePartner)->create();

        $ownEvent = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id, 'partner_id' => $partner->id]);
        $otherEvent = Event::factory()->for($organization)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($partnerUser)->get(route('events.show', $ownEvent))->assertOk();
        $this->actingAs($partnerUser)->get(route('events.show', $otherEvent))->assertForbidden();
    }

    public function test_an_admin_account_linked_as_a_partner_still_sees_the_full_client_list_and_dashboard(): void
    {
        $franchisePartner = FranchisePartner::factory()->create();
        $partner = Partner::factory()->for($franchisePartner)->create(['name' => 'MM Kft.', 'country' => 'HU']);
        $admin = $this->franchisePartnerAdmin($franchisePartner);
        $partner->forceFill(['user_id' => $admin->id])->save();
        (new AssignRole)->grant($admin, 'partner', 'test');

        $theirClient = Organization::factory()->for($franchisePartner)->create(['name' => 'Theirs']);
        Event::factory()->for($theirClient)->create(['franchise_partner_id' => $franchisePartner->id, 'partner_id' => $partner->id]);

        $otherClient = Organization::factory()->for($franchisePartner)->create(['name' => 'Other']);
        Event::factory()->for($otherClient)->create(['franchise_partner_id' => $franchisePartner->id]);

        $this->actingAs($admin)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Organizations/Index')
                ->where('canManage', true)
                ->has('organizations', 2));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('organizations.index'));
    }
}
