<?php

namespace Database\Factories;

use App\Enums\PhaseStatus;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Phase>
 */
class PhaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'number' => fake()->unique()->numberBetween(1, 250),
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(20),
            'status' => PhaseStatus::Draft,
            'prize_text' => fake()->sentence(),
            'rules_version' => 'v1.0',
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => PhaseStatus::Open]);
    }

    public function frozen(): static
    {
        return $this->state(fn () => ['status' => PhaseStatus::Frozen]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => PhaseStatus::Closed]);
    }
}
