<?php

use App\Models\Institution;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Bearer-auth header for a user, for use with withHeaders() in Feature tests.
 *
 * Sanctum's RequestGuard caches its resolved user for the guard instance's
 * lifetime, and within a single test the container (and guard) persists across
 * simulated requests. Forgetting guards before each new "actor" forces
 * re-resolution so tests exercise the real per-request auth behavior.
 */
function apiAuthHeader(User $user): array
{
    Auth::forgetGuards();

    return ['Authorization' => 'Bearer '.$user->createToken('auth')->plainTextToken];
}

/**
 * A student user with an approved profile, for Feature tests that need a
 * fully registered student (claims, leaderboards, community submissions).
 */
function studentWithProfile(?Institution $institution = null): User
{
    $institution ??= Institution::factory()->create();

    return User::factory()
        ->has(StudentProfile::factory()->for($institution, 'institution'), 'studentProfile')
        ->create();
}
