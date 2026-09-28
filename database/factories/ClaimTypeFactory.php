<?php

namespace Database\Factories;

use App\Models\ClaimType;
use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaimType>
 */
class ClaimTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phase_id' => Phase::factory(),
            'code' => fake()->unique()->slug(2),
            'label' => fake()->words(3, true),
            'base_points' => fake()->numberBetween(5, 25),
            'active' => true,
            'validation_rule_text' => fake()->sentence(),
        ];
    }
}
