<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Disqualification;
use App\Models\Institution;
use App\Models\Phase;
use App\Models\User;

it('immediately zeroes a disqualified users leaderboard contribution', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10]);

    $before = collect($this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}")->json('data'))
        ->firstWhere('user_id', $student->id);
    expect($before['provisional_score'])->toBe(10);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/disqualifications', [
            'targetable_type' => 'user',
            'targetable_id' => $student->id,
            'phase_id' => $phase->id,
            'reason' => 'Fabricated evidence found on review.',
        ])->assertCreated();

    $after = collect($this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}")->json('data'))
        ->firstWhere('user_id', $student->id);
    expect($after['provisional_score'])->toBe(0)
        ->and($after['disqualified'])->toBeTrue();
});

it('zeroes an institutions aggregate when the institution itself is disqualified', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $institution = Institution::factory()->create();
    $student = studentWithProfile($institution);
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($institution)->create(['provisional_points' => 10]);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/disqualifications', [
            'targetable_type' => 'institution',
            'targetable_id' => $institution->id,
            'phase_id' => $phase->id,
            'reason' => 'Coordinated fraud across multiple students.',
        ])->assertCreated();

    $rows = collect($this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/institution?phase_id={$phase->id}")->json('data'));

    $row = $rows->firstWhere('institution_id', $institution->id);
    expect($row['provisional_score'])->toBe(0)->and($row['disqualified'])->toBeTrue();

    $studentRow = collect($this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}")->json('data'))
        ->firstWhere('user_id', $student->id);
    expect($studentRow['disqualified'])->toBeFalse();
});

it('reinstates by flipping active to false on the same record rather than inserting a new one', function () {
    $phase = Phase::factory()->create();
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();
    $disqualification = Disqualification::factory()->create([
        'targetable_type' => User::class,
        'targetable_id' => $student->id,
        'phase_id' => $phase->id,
        'admin_id' => $admin->id,
    ]);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/disqualifications/{$disqualification->id}/reinstate")
        ->assertOk()
        ->assertJsonPath('data.id', $disqualification->id)
        ->assertJsonPath('data.active', false);

    expect(Disqualification::count())->toBe(1);
});

it('forbids a non-admin from disqualifying a student', function () {
    $phase = Phase::factory()->create();
    $student = studentWithProfile();
    $auditor = User::factory()->auditor()->create();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson('/api/admin/disqualifications', [
            'targetable_type' => 'user',
            'targetable_id' => $student->id,
            'phase_id' => $phase->id,
            'reason' => 'Suspicious activity.',
        ])->assertForbidden();
});

it('requires a reason for both disqualification and score adjustment', function () {
    $phase = Phase::factory()->create();
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/disqualifications', [
            'targetable_type' => 'user',
            'targetable_id' => $student->id,
            'phase_id' => $phase->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/score-adjustments', [
            'targetable_type' => 'user',
            'targetable_id' => $student->id,
            'phase_id' => $phase->id,
            'points' => 5,
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');
});
