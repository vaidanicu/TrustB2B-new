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
        ],
        'franchisor-admin' => [
            'franchise-partners.manage', 'organizations.manage', 'users.manage', 'events.manage', 'audit-log.view',
        ],
        'franchise-partner-admin' => [
            'organizations.manage', 'users.manage', 'events.manage', 'audit-log.view',
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
