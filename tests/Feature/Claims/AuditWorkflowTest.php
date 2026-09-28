<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\User;

it('marks a claim verified and sets audited points to the claim types base points', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10]);
    $auditor = User::factory()->auditor()->create();

    $response = $this->withHeaders(apiAuthHeader($auditor))
        ->postJson("/api/admin/claims/{$claim->id}/review", [
            'outcome' => 'verified',
            'audit_notes' => 'Confirmed on the Tunse backend.',
            'backend_lookup_key' => $claim->recruit_phone,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'verified')
        ->assertJsonPath('data.audited_points', 10);

    $this->assertDatabaseHas('audits', ['claim_id' => $claim->id, 'outcome' => 'verified']);
});

it('marks a claim rejected and zeroes its audited points', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create();
    $auditor = User::factory()->auditor()->create();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson("/api/admin/claims/{$claim->id}/review", ['outcome' => 'rejected'])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.audited_points', 0);
});

it('marks a claim flagged or correction requested and leaves audited points unset', function (string $outcome, string $expectedStatus) {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create();
    $auditor = User::factory()->auditor()->create();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson("/api/admin/claims/{$claim->id}/review", ['outcome' => $outcome])
        ->assertOk()
        ->assertJsonPath('data.status', $expectedStatus)
        ->assertJsonPath('data.audited_points', null);
})->with([
    ['flagged', 'flagged'],
    ['correction', 'correction_requested'],
]);

it('forbids a student from reviewing a claim', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson("/api/admin/claims/{$claim->id}/review", ['outcome' => 'verified'])
        ->assertForbidden();
});

it('records audit history visible in chronological order on the claim detail', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create();
    $auditor = User::factory()->auditor()->create();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson("/api/admin/claims/{$claim->id}/review", ['outcome' => 'flagged', 'audit_notes' => 'Needs a second look'])
        ->assertOk();

    $this->withHeaders(apiAuthHeader($auditor))
        ->postJson("/api/admin/claims/{$claim->id}/review", ['outcome' => 'verified', 'audit_notes' => 'Confirmed'])
        ->assertOk();

    $response = $this->withHeaders(apiAuthHeader($auditor))->getJson("/api/claims/{$claim->id}");

    $audits = $response->json('data.audits');
    expect($audits)->toHaveCount(2)
        ->and($audits[0]['outcome'])->toBe('flagged')
        ->and($audits[1]['outcome'])->toBe('verified');
});

it('reflects a claim review in the leaderboard within the same request cycle', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['provisional_points' => 10]);
    $admin = User::factory()->admin()->create();

    $before = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}")
        ->json('data');
    expect(collect($before)->firstWhere('user_id', $student->id)['audited_score'])->toBe(0);

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson("/api/admin/claims/{$claim->id}/review", ['outcome' => 'verified'])
        ->assertOk();

    $after = $this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/individual?phase_id={$phase->id}")
        ->json('data');
    expect(collect($after)->firstWhere('user_id', $student->id)['audited_score'])->toBe(10);
});
