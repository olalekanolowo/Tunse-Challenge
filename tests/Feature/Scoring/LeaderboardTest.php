<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Disqualification;
use App\Models\Institution;
use App\Models\Phase;
use App\Models\ScoreAdjustment;
use App\Models\User;

function leaderboardRowFor(array $rows, int $userId): ?array
{
    return collect($rows)->firstWhere('user_id', $userId);
}

it('excludes rejected claims from the provisional leaderboard but includes submitted and flagged', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $staff = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10, 'status' => 'submitted']);
    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10, 'status' => 'rejected']);
    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10, 'status' => 'flagged']);

    $response = $this->withHeaders(apiAuthHeader($staff))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}&type=provisional");

    $row = leaderboardRowFor($response->json('data'), $student->id);
    expect($row['provisional_score'])->toBe(20);
});

it('only counts verified claims audited points toward the final leaderboard', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $staff = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['audited_points' => 10, 'status' => 'verified']);
    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['audited_points' => null, 'provisional_points' => 10, 'status' => 'submitted']);

    $response = $this->withHeaders(apiAuthHeader($staff))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}");

    $row = leaderboardRowFor($response->json('data'), $student->id);
    expect($row['audited_score'])->toBe(10);
});

it('applies score adjustments to both provisional and audited totals for the correct phase only', function () {
    $phaseOne = Phase::factory()->open()->create(['number' => 1]);
    $phaseTwo = Phase::factory()->create(['number' => 2]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    ScoreAdjustment::factory()->create([
        'targetable_type' => User::class,
        'targetable_id' => $student->id,
        'phase_id' => $phaseOne->id,
        'points' => 5,
        'admin_id' => $admin->id,
    ]);
    ScoreAdjustment::factory()->create([
        'targetable_type' => User::class,
        'targetable_id' => $student->id,
        'phase_id' => $phaseTwo->id,
        'points' => 100,
        'admin_id' => $admin->id,
    ]);

    $response = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phaseOne->id}&type=provisional");

    $row = leaderboardRowFor($response->json('data'), $student->id);
    expect($row['provisional_score'])->toBe(5)
        ->and($row['audited_score'])->toBe(5);
});

it('zeroes out and flags a disqualified students leaderboard contribution', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10, 'status' => 'submitted']);

    Disqualification::factory()->create([
        'targetable_type' => User::class,
        'targetable_id' => $student->id,
        'phase_id' => $phase->id,
        'admin_id' => $admin->id,
    ]);

    $response = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}&type=provisional");

    $row = leaderboardRowFor($response->json('data'), $student->id);
    expect($row['provisional_score'])->toBe(0)
        ->and($row['disqualified'])->toBeTrue();
});

it('aggregates only its own institutions students into the institution leaderboard', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $institutionA = Institution::factory()->create();
    $institutionB = Institution::factory()->create();
    $studentA = studentWithProfile($institutionA);
    $studentB = studentWithProfile($institutionB);
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($studentA)->for($phase)->for($claimType, 'claimType')->for($institutionA)->create(['provisional_points' => 10, 'status' => 'submitted']);
    Claim::factory()->for($studentB)->for($phase)->for($claimType, 'claimType')->for($institutionB)->create(['provisional_points' => 25, 'status' => 'submitted']);

    $response = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/institution?phase_id={$phase->id}&type=provisional");

    $rows = collect($response->json('data'));
    expect($rows->firstWhere('institution_id', $institutionA->id)['provisional_score'])->toBe(10)
        ->and($rows->firstWhere('institution_id', $institutionB->id)['provisional_score'])->toBe(25);
});

it('reports the final leaderboard as unavailable until a snapshot has been created', function () {
    $phase = Phase::factory()->create(['number' => 1]);
    $admin = User::factory()->admin()->create();

    $response = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}&type=final");

    $response->assertOk()->assertJsonPath('available', false);
});

it('sums provisional scores across phases for the cumulative leaderboard', function () {
    $phaseOne = Phase::factory()->create(['number' => 1]);
    $phaseTwo = Phase::factory()->create(['number' => 2]);
    $claimTypeOne = ClaimType::factory()->for($phaseOne)->create(['base_points' => 10]);
    $claimTypeTwo = ClaimType::factory()->for($phaseTwo)->create(['base_points' => 5]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phaseOne)->for($claimTypeOne, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10, 'status' => 'submitted']);
    Claim::factory()->for($student)->for($phaseTwo)->for($claimTypeTwo, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 5, 'status' => 'submitted']);

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/leaderboards/cumulative');

    $row = collect($response->json('data'))->firstWhere('user_id', $student->id);
    expect($row['cumulative_provisional_score'])->toBe(15);
});
