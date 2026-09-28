<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Institution;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'institution_id' => Institution::factory(),
            'challenge_id' => 'TCH-'.strtoupper(fake()->lexify('????')).'-'.fake()->unique()->numerify('####'),
            'phone' => '+234'.fake()->numerify('##########'),
            'state' => fake()->randomElement(['Lagos', 'Oyo', 'Osun', 'Kaduna', 'Enugu']),
            'student_id_number' => fake()->bothify('??/####/###'),
            'department' => fake()->randomElement(['Computer Science', 'Economics', 'Mass Communication']),
            'graduation_year' => fake()->numberBetween(2026, 2029),
            'status' => StudentStatus::Approved,
            'approved_at' => now(),
        ];
    }
}
