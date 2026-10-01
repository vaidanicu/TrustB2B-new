<?php

namespace Database\Factories;

use App\Models\FranchisePartner;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'franchise_partner_id' => FranchisePartner::factory(),
            'name' => fake()->company(),
            'country' => fake()->randomElement(['RO', 'HU', 'DE']),
            'languages' => ['ro'],
            'data_protection_contact_email' => fake()->unique()->companyEmail(),
            'contract_status' => 'active',
            'storage_quota_mb' => 5000,
            'event_quota' => null,
        ];
    }
}
