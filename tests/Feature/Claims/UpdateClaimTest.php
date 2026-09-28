<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\User;

it('lets a student edit their own pending claim', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['recruit_name' => 'Original Name', 'status' => 'submitted']);

    $response = $this->withHeaders(apiAuthHeader($student))
        ->patchJson("/api/claims/{$claim->id}", [
            'recruit_name' => 'Corrected Name',
            'notes' => 'Fixed a typo in the name',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.recruit_name', 'Corrected Name')
        ->assertJsonPath('data.notes', 'Fixed a typo in the name');

    expect($claim->fresh()->recruit_name)->toBe('Corrected Name');
});

it('recalculates provisional points when the claim type changes', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $tworker = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $verifier = ClaimType::factory()->for($phase)->create(['code' => 'active_verifier', 'base_points' => 25]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($tworker, 'claimType')->for($student->studentProfile->institution)
        ->create(['recruit_phone' => '+2348011112222', 'provisional_points' => 10, 'status' => 'submitted']);

    $response = $this->withHeaders(apiAuthHeader($student))
        ->patchJson("/api/claims/{$claim->id}", ['claim_type_id' => $verifier->id]);

    $response->assertOk()->assertJsonPath('data.provisional_points', 25);
});

it('forbids editing a claim that has already been audited', function (string $status) {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['status' => $status]);

    $this->withHeaders(apiAuthHeader($student))
        ->patchJson("/api/claims/{$claim->id}", ['recruit_name' => 'Should not apply'])
        ->assertForbidden();

    expect($claim->fresh()->recruit_name)->not->toBe('Should not apply');
})->with(['verified', 'rejected', 'flagged']);

it('lets a student submit a correction and moves the claim back to submitted for re-audit', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['recruit_phone' => '+2348011110000', 'status' => 'correction_requested']);

    $response = $this->withHeaders(apiAuthHeader($student))
        ->patchJson("/api/claims/{$claim->id}", ['recruit_phone' => '08011119999']);

    $response->assertOk()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.recruit_phone', '+2348011119999');

    expect($claim->fresh()->status->value)->toBe('submitted');
});

it('forbids a student from editing another students claim', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $owner = studentWithProfile();
    $otherStudent = studentWithProfile();
    $claim = Claim::factory()->for($owner)->for($phase)->for($claimType, 'claimType')->for($owner->studentProfile->institution)
        ->create(['status' => 'submitted']);

    $this->withHeaders(apiAuthHeader($otherStudent))
        ->patchJson("/api/claims/{$claim->id}", ['recruit_name' => 'Hijacked'])
        ->assertForbidden();
});

it('forbids staff from using the student edit endpoint', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['status' => 'submitted']);
    $admin = User::factory()->admin()->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->patchJson("/api/claims/{$claim->id}", ['recruit_name' => 'Admin edit'])
        ->assertForbidden();
});

it('does not false-positive the role-conflict check against the claim being edited', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $verifier = ClaimType::factory()->for($phase)->create(['code' => 'active_verifier', 'base_points' => 25]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($verifier, 'claimType')->for($student->studentProfile->institution)
        ->create(['recruit_phone' => '+2348011112222', 'status' => 'submitted']);

    $this->withHeaders(apiAuthHeader($student))
        ->patchJson("/api/claims/{$claim->id}", ['recruit_name' => 'Still fine'])
        ->assertOk();
});

it('rejects moving a claim to a claim type in a different phase', function () {
    $phase1 = Phase::factory()->open()->create(['number' => 1]);
    $phase2 = Phase::factory()->open()->create(['number' => 2]);
    $tworker = ClaimType::factory()->for($phase1)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $otherPhaseType = ClaimType::factory()->for($phase2)->create(['code' => 'registered_customer', 'base_points' => 5]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase1)->for($tworker, 'claimType')->for($student->studentProfile->institution)
        ->create(['status' => 'submitted']);

    $this->withHeaders(apiAuthHeader($student))
        ->patchJson("/api/claims/{$claim->id}", ['claim_type_id' => $otherPhaseType->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('claim_type_id');
});
