<?php

namespace Database\Factories;

use App\Models\FranchisePartner;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'franchise_partner_id' => FranchisePartner::factory(),
            'name' => fake()->company(),
            'country' => fake()->randomElement(['RO', 'HU', 'DE', 'AT']),
        ];
    }
}
