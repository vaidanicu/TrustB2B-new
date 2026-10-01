<?php

namespace Database\Factories;

use App\Models\FranchisePartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FranchisePartner>
 */
class FranchisePartnerFactory extends Factory
{
    protected $model = FranchisePartner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'region_code' => strtoupper(fake()->unique()->lexify('??-???')),
            'country' => fake()->randomElement(['RO', 'HU', 'DE']),
            'contact_email' => fake()->unique()->companyEmail(),
            'contract_status' => 'active',
        ];
    }
}
