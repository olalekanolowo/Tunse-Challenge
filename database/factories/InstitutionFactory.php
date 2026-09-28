<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' University',
            'short_code' => strtoupper(fake()->unique()->lexify('????')),
            'state' => fake()->randomElement(['Lagos', 'Oyo', 'Osun', 'Kaduna', 'Enugu']),
            'active' => true,
            'coordinator_name' => fake()->name(),
            'next_sequence' => 0,
        ];
    }
}
