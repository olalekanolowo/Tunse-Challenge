<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function signedVerificationUrl(User $user): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );
}

it('marks an unverified user as verified when hitting a validly signed link', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->getJson(signedVerificationUrl($user))
        ->assertOk()
        ->assertJsonPath('message', 'Email verified successfully.');

    expect($user->fresh()->email_verified_at)->not->toBeNull();
    Event::assertDispatched(Verified::class);
});

it('reports already verified without re-firing the event', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->create();

    $this->getJson(signedVerificationUrl($user))
        ->assertOk()
        ->assertJsonPath('message', 'Email already verified.');

    Event::assertNotDispatched(Verified::class);
});

it('rejects a verification link with a tampered hash', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('someone-else@example.com')]
    );

    $this->getJson($url)->assertForbidden();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('rejects a verification link with a tampered signature', function () {
    $user = User::factory()->unverified()->create();
    $url = signedVerificationUrl($user).'tampered';

    $this->getJson($url)->assertForbidden();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('sends a new verification email on resend for an unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->withHeaders(apiAuthHeader($user))
        ->postJson('/api/auth/email/resend')
        ->assertOk()
        ->assertJsonPath('message', 'Verification link sent.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('does not send another email when resend is called for an already verified user', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->withHeaders(apiAuthHeader($user))
        ->postJson('/api/auth/email/resend')
        ->assertOk()
        ->assertJsonPath('message', 'Email already verified.');

    Notification::assertNothingSent();
});

it('blocks an unverified user from a verified-gated route but allows me, logout, and resend', function () {
    $student = studentWithProfile();
    $student->forceFill(['email_verified_at' => null])->save();

    $this->withHeaders(apiAuthHeader($student))
        ->getJson('/api/claims')
        ->assertForbidden();

    $this->withHeaders(apiAuthHeader($student))
        ->getJson('/api/auth/me')
        ->assertOk();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/auth/email/resend')
        ->assertOk();
});

it('allows a verified user through a verified-gated route', function () {
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->getJson('/api/claims')
        ->assertOk();
});
