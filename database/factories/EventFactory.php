<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $organization = Organization::factory()->create();

        return [
            'organization_id' => $organization->id,
            'franchise_partner_id' => $organization->franchise_partner_id,
            // Not required to belong to the same organization by any DB
            // constraint — a plain platform-level user is enough here;
            // override explicitly in a test that cares who the creator is.
            'created_by' => \App\Models\User::factory()->create()->id,
            'name' => fake()->company().' — eveniment '.fake()->year(),
            'purpose' => fake()->sentence(),
            'country' => 'RO',
            'language' => 'ro',
            'starts_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'ends_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'retention_days' => fake()->randomElement([60, 90]),
            'end_of_cycle_action' => fake()->randomElement(['delete', 'export']),
            'status' => 'draft',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active']);
    }
}
