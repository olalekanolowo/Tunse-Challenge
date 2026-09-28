<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Institution;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'institution_id' => Institution::factory(),
            'phase_id' => Phase::factory(),
            'claim_type_id' => ClaimType::factory(),
            'recruit_name' => fake()->name(),
            'recruit_phone' => '+234'.fake()->unique()->numerify('##########'),
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'category' => 'plumber',
            'date_recruited' => now()->toDateString(),
            'notes' => fake()->sentence(),
            'declaration_at' => now(),
            'status' => ClaimStatus::Submitted,
            'provisional_points' => 10,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => ClaimStatus::Verified]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ClaimStatus::Rejected, 'audited_points' => 0]);
    }
}
