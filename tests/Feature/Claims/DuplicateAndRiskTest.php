<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\User;

function riskReasonsFor(array $claimRows, int $claimId): array
{
    return collect($claimRows)->firstWhere('id', $claimId)['risk_reasons'] ?? [];
}

it('flags duplicate_phone when two different students claim the same recruit phone', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $studentA = studentWithProfile();
    $studentB = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $claimA = Claim::factory()->for($studentA)->for($phase)->for($claimType, 'claimType')->for($studentA->studentProfile->institution)->create(['recruit_phone' => '+2348011112222']);
    Claim::factory()->for($studentB)->for($phase)->for($claimType, 'claimType')->for($studentB->studentProfile->institution)->create(['recruit_phone' => '+2348011112222']);

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims');

    expect(riskReasonsFor($response->json('data'), $claimA->id))->toContain('duplicate_phone');
});

it('does not flag duplicate_phone for a claim with a unique recruit phone', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['recruit_phone' => '+2348011119999']);

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims');

    expect(riskReasonsFor($response->json('data'), $claim->id))->not->toContain('duplicate_phone');
});

it('flags high_velocity when a student submits three or more claims within 72 hours', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $claims = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->count(3)
        ->sequence(
            ['recruit_phone' => '+2348020000001'],
            ['recruit_phone' => '+2348020000002'],
            ['recruit_phone' => '+2348020000003'],
        )
        ->create();

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims');

    expect(riskReasonsFor($response->json('data'), $claims->first()->id))->toContain('high_velocity');
});

it('does not flag high_velocity for a single isolated claim', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create();

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims');

    expect(riskReasonsFor($response->json('data'), $claim->id))->not->toContain('high_velocity');
});

it('flags manual_flag only when the claim status is flagged', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $flagged = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['status' => 'flagged']);
    $submitted = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create(['status' => 'submitted']);

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims');
    $rows = $response->json('data');

    expect(riskReasonsFor($rows, $flagged->id))->toContain('manual_flag')
        ->and(riskReasonsFor($rows, $submitted->id))->not->toContain('manual_flag');
});

it('surfaces the top10_national and institution_top3 reasons as scores change', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 100]);
    $topStudent = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $claim = Claim::factory()->for($topStudent)->for($phase)->for($claimType, 'claimType')->for($topStudent->studentProfile->institution)->create(['provisional_points' => 100]);

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims');

    $reasons = riskReasonsFor($response->json('data'), $claim->id);
    expect($reasons)->toContain('top10_national')
        ->toContain('top1_institution')
        ->toContain('institution_top3');
});

it('sorts the admin claims list by risk score when requested', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $flaggedStudent = studentWithProfile();
    $plainStudent = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $plainClaim = Claim::factory()->for($plainStudent)->for($phase)->for($claimType, 'claimType')->for($plainStudent->studentProfile->institution)->create(['status' => 'submitted']);
    $flaggedClaim = Claim::factory()->for($flaggedStudent)->for($phase)->for($claimType, 'claimType')->for($flaggedStudent->studentProfile->institution)->create(['status' => 'flagged']);

    $response = $this->withHeaders(apiAuthHeader($admin))->getJson('/api/admin/claims?sort=risk');

    $ids = collect($response->json('data'))->pluck('id')->values();
    expect($ids->search($flaggedClaim->id))->toBeLessThan($ids->search($plainClaim->id));
});
