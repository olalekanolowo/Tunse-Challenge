<?php

use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function validRegistrationPayload(Institution $institution, array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Jane Student',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '08012345678',
        'institution_id' => $institution->id,
        'state' => 'Lagos',
        'department' => 'Computer Science',
        'graduation_year' => 2027,
        'student_id_number' => 'CSC/2021/001',
        'student_id_file' => UploadedFile::fake()->image('id.jpg'),
    ], $overrides);
}

it('registers a student with a generated challenge id and returns a token', function () {
    Storage::fake('local');
    Notification::fake();
    $institution = Institution::factory()->create(['short_code' => 'UNILAG']);

    $response = $this->postJson('/api/auth/register', validRegistrationPayload($institution));

    $response->assertCreated()
        ->assertJsonPath('user.role', 'student')
        ->assertJsonPath('user.student_profile.challenge_id', 'TCH-UNILAG-0001')
        ->assertJsonPath('user.student_profile.phone', '+2348012345678')
        ->assertJsonStructure(['token']);

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'student']);

    $user = User::where('email', 'jane@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects registration with a duplicate email', function () {
    Storage::fake('local');
    $institution = Institution::factory()->create();
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/auth/register', validRegistrationPayload($institution));

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('rejects registration when password confirmation does not match', function () {
    Storage::fake('local');
    $institution = Institution::factory()->create();

    $response = $this->postJson('/api/auth/register', validRegistrationPayload($institution, [
        'password_confirmation' => 'something-else',
    ]));

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('rejects registration when a required field is missing', function () {
    Storage::fake('local');
    $institution = Institution::factory()->create();
    $payload = validRegistrationPayload($institution);
    unset($payload['phone']);

    $response = $this->postJson('/api/auth/register', $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors('phone');
});

it('normalizes phone numbers to the canonical +234 format regardless of input format', function (string $input, string $expected) {
    Storage::fake('local');
    $institution = Institution::factory()->create();

    $response = $this->postJson('/api/auth/register', validRegistrationPayload($institution, [
        'email' => $input.'@example.com',
        'phone' => $input,
    ]));

    $response->assertCreated()->assertJsonPath('user.student_profile.phone', $expected);
})->with([
    ['08012345678', '+2348012345678'],
    ['+2348012345678', '+2348012345678'],
    ['2348012345678', '+2348012345678'],
]);

it('never collides on the challenge id sequence for concurrent registrations at the same institution', function () {
    Storage::fake('local');
    $institution = Institution::factory()->create(['short_code' => 'UNILAG']);

    $this->postJson('/api/auth/register', validRegistrationPayload($institution, ['email' => 'a@example.com']))
        ->assertCreated()
        ->assertJsonPath('user.student_profile.challenge_id', 'TCH-UNILAG-0001');

    $this->postJson('/api/auth/register', validRegistrationPayload($institution, ['email' => 'b@example.com']))
        ->assertCreated()
        ->assertJsonPath('user.student_profile.challenge_id', 'TCH-UNILAG-0002');

    expect(Institution::find($institution->id)->next_sequence)->toBe(2);
});
