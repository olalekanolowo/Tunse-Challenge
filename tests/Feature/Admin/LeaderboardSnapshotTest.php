<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\ScoreAdjustment;
use App\Models\User;

it('blocks a final snapshot before the phase is closed', function () {
    $phase = Phase::factory()->open()->create();
    $admin = User::factory()->admin()->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/leaderboard-snapshots', ['phase_id' => $phase->id, 'snapshot_type' => 'final'])
        ->assertUnprocessable();
});

it('creates a final snapshot once the phase is closed and freezes the current leaderboard', function () {
    $phase = Phase::factory()->create(['status' => 'closed']);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['status' => 'verified', 'audited_points' => 10]);

    $response = $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/leaderboard-snapshots', ['phase_id' => $phase->id, 'snapshot_type' => 'final']);

    $response->assertCreated();
    $snapshotId = $response->json('data.id');

    $final = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}&type=final");

    $final->assertOk()->assertJsonPath('available', true)->assertJsonPath('snapshot_id', $snapshotId);
    $row = collect($final->json('data'))->firstWhere('user_id', $student->id);
    expect($row['audited_score'])->toBe(10);
});

it('does not let a later score adjustment change an already-created final snapshot', function () {
    $phase = Phase::factory()->create(['status' => 'closed']);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['status' => 'verified', 'audited_points' => 10]);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/admin/leaderboard-snapshots', ['phase_id' => $phase->id, 'snapshot_type' => 'final'])
        ->assertCreated();

    $firstRead = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}&type=final")
        ->json('data');
    $firstScore = collect($firstRead)->firstWhere('user_id', $student->id)['audited_score'];

    ScoreAdjustment::factory()->create([
        'targetable_type' => User::class,
        'targetable_id' => $student->id,
        'phase_id' => $phase->id,
        'points' => 500,
        'admin_id' => $admin->id,
    ]);

    $secondRead = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}&type=final")
        ->json('data');
    $secondScore = collect($secondRead)->firstWhere('user_id', $student->id)['audited_score'];

    expect($secondScore)->toBe($firstScore);
});

it('forbids a non-admin from creating a snapshot', function () {
    $phase = Phase::factory()->create(['status' => 'closed']);
    $auditor = User::factory()->auditor()->create();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson('/api/admin/leaderboard-snapshots', ['phase_id' => $phase->id, 'snapshot_type' => 'final'])
        ->assertForbidden();
});
