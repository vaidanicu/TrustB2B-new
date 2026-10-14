<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        $event = Event::factory()->create();

        return [
            'event_id' => $event->id,
            'organization_id' => $event->organization_id,
            'franchise_partner_id' => $event->franchise_partner_id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'invited',
            'photo_consent' => null,
            'responded_at' => null,
            'link_expires_at' => $event->starts_at->copy()->subDay(),
        ];
    }

    public function attending(bool $photoConsent = true): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'attending',
            'photo_consent' => $photoConsent,
            'responded_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'photo_consent' => null,
            'responded_at' => now(),
        ]);
    }
}
