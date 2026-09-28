<?php

namespace Database\Factories;

use App\Models\Disqualification;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disqualification>
 */
class DisqualificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'targetable_type' => User::class,
            'targetable_id' => User::factory(),
            'phase_id' => Phase::factory(),
            'reason' => fake()->sentence(),
            'admin_id' => User::factory()->admin(),
            'active' => true,
        ];
    }
}
