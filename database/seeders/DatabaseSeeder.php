<?php

namespace Database\Seeders;

use App\Actions\Tenancy\AssignRole;
use App\Models\FranchisePartner;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $assignRole = new AssignRole;

        $platformAdmin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $assignRole->grant($platformAdmin, 'platform-admin', 'seed: initial platform administrator');

        $franchisePartner = FranchisePartner::factory()->create([
            'name' => 'Confort Events SRL',
            'region_code' => 'RO-Auras',
            'country' => 'RO',
            'contact_email' => 'franciza@confortevents.ro',
        ]);

        $franchiseAdmin = User::factory()->forFranchisePartner($franchisePartner)->create([
            'name' => 'Elena Marton',
            'email' => 'elena.marton@confortevents.ro',
        ]);
        $assignRole->grant($franchiseAdmin, 'franchise-partner-admin', 'seed: regional partner administrator');

        // BelongsToOrganization auto-stamps organization_id/franchise_partner_id on
        // creating() from TenantContext — act as the franchise admin so the demo
        // organization and its users land under the right partner, exactly as it
        // would if Elena had created them herself through the app.
        TenantContext::runAs($franchiseAdmin, function () use ($assignRole, $franchisePartner): void {
            $organization = Organization::factory()->create([
                'franchise_partner_id' => $franchisePartner->id,
                'name' => 'Hotel Aurora',
                'country' => 'RO',
                'data_protection_contact_email' => 'dpo@hotelaurora.ro',
            ]);

            $customerAdmin = User::factory()->forOrganization($organization)->create([
                'name' => 'Bianca Ionescu',
                'email' => 'bianca.ionescu@hotelaurora.ro',
            ]);
            $assignRole->grant($customerAdmin, 'customer-admin', 'seed: initial customer administrator');

            $photographer = User::factory()->forOrganization($organization)->create([
                'name' => 'Grup Media Est SRL',
                'email' => 'contact@grupmediaest.ro',
            ]);
            $assignRole->grant($photographer, 'photographer', 'seed: demo photographer account');
        });
    }
}
