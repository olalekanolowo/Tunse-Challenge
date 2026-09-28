<?php

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('logs in a student and returns their role and profile', function () {
    $user = User::factory()->has(StudentProfile::factory(), 'studentProfile')->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.role', 'student')
        ->assertJsonPath('user.student_profile.id', $user->studentProfile->id)
        ->assertJsonStructure(['token']);
});

it('logs in staff and returns their role with no student profile', function () {
    User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.role', 'admin')
        ->assertJsonPath('user.student_profile', null);
});

it('rejects a wrong password without leaking a token', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
    $response->assertJsonMissingPath('token');
});

it('revokes the token on logout so it can no longer authenticate', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $token = $user->createToken('auth')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/auth/logout')
        ->assertNoContent();

    // Sanctum's RequestGuard caches its resolved user for the guard instance's
    // lifetime; within a single test the container (and guard) persists across
    // simulated requests, so force re-resolution to exercise the real behavior.
    Auth::forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/auth/me')
        ->assertUnauthorized();
});
