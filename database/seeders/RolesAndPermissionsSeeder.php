<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The 8 roles required by AP06 / roadmap §2, and a first cut of the
 * permissions the spec calls out explicitly — most importantly that a
 * Fotograf (photographer) can upload but never export or even view
 * participant lists. Permission names are seeded ahead of the features
 * that will check them (events/participants/media/GDPR cases don't exist
 * yet) so the policy is declared now and enforced as each AP lands.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'platform-admin' => [
            'franchise-partners.manage', 'organizations.manage', 'users.manage', 'events.manage', 'audit-log.view',
            'system.view-logs', 'partners.manage',
        ],
        'franchisor-admin' => [
            'franchise-partners.manage', 'organizations.manage', 'users.manage', 'events.manage', 'audit-log.view',
            'system.view-logs', 'partners.manage',
        ],
        'franchise-partner-admin' => [
            'organizations.manage', 'users.manage', 'events.manage', 'audit-log.view', 'partners.manage',
        ],
        // events.manage moved to franchise/platform level: events (and the
        // GDPR contract participants must accept) are set up by the agency
        // on the client's behalf, never by the client's own staff — see
        // EventController's docblock.
        'customer-admin' => [
            'users.manage', 'participants.view', 'participants.export',
            'media.upload', 'media.publish', 'media.moderate-deletion-requests',
            'gdpr-cases.manage', 'audit-log.view',
        ],
        'event-manager' => [
            'participants.view', 'participants.export', 'media.upload', 'media.publish',
        ],
        'data-protection-officer' => [
            'participants.view', 'media.moderate-deletion-requests', 'gdpr-cases.manage', 'audit-log.view',
        ],
        // Upload only — deliberately no participants.view / participants.export:
        // "Fotograf: Nur Upload... kein Teilnehmerdatenexport" (roadmap §2).
        'photographer' => [
            'media.upload',
        ],
        // No admin permissions at all — participants reach the platform
        // through personalised invitation links, not role-based access.
        'participant' => [],
        // The client-side contact who signed the contract (Event's
        // client_contact_email) — reported: they need to know their event
        // was actually created, and see it (every event they're the
        // contact for, not just one) without depending on a link that
        // expires. View-only, identified by matching email on
        // EventController::show()/DashboardController, not by any
        // permission here — see those controllers' own docblocks.
        'client-contact' => [],
        // A regional reseller's own login (PartnerController::findOrCreateAccount).
        // Reported: in this business a Partner IS its own franchise — same
        // permissions as franchise-partner-admin (full management of every
        // client/event within its own franchise, via TenantScope's existing
        // franchise_partner_id filtering — never another franchise's data),
        // plus removal-requests.view for the cross-event flagged-photos
        // dashboard a plain franchise-partner-admin has no reason to need.
        // See User::isActingAsPartnerOnly()'s own docblock for how this
        // permission (not the 'franchise-partner-admin' role name, kept
        // only for whatever legacy account might still hold it bare) is
        // what actually decides "full access" everywhere in the app now.
        'partner' => [
            'removal-requests.view',
            'organizations.manage', 'users.manage', 'events.manage', 'audit-log.view', 'partners.manage',
        ],
    ];

    public function run(): void
    {
        foreach (array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))) as $permission) {
            Permission::findOrCreate($permission);
        }

        // Permissions created above are invisible to findByName()/syncPermissions()
        // below until this cache is dropped — spatie/permission does not
        // auto-invalidate it on direct model writes within the same process.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }
    }
}
