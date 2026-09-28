<?php

namespace Database\Factories;

use App\Models\CommunitySubmission;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunitySubmission>
 */
class CommunitySubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'institution_id' => Institution::factory(),
            'platform' => 'x',
            'post_url' => 'https://x.com/example/status/'.fake()->unique()->randomNumber(6),
            'community_type' => 'students',
            'consent_confirmed' => true,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
        ];
    }
}
