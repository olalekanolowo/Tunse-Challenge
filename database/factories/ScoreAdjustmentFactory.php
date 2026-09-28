<?php

namespace Database\Factories;

use App\Models\Phase;
use App\Models\ScoreAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreAdjustment>
 */
class ScoreAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'targetable_type' => User::class,
            'targetable_id' => User::factory(),
            'phase_id' => Phase::factory(),
            'points' => fake()->numberBetween(-10, 10),
            'reason' => fake()->sentence(),
            'admin_id' => User::factory()->admin(),
        ];
    }
}
