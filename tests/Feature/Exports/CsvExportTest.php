<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\User;

it('exports claims as csv with the expected header row', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)->create();
    $admin = User::factory()->admin()->create();

    $response = $this->withHeaders(apiAuthHeader($admin))->get('/api/admin/exports/claims.csv');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    $lines = explode("\n", trim($response->streamedContent()));
    expect($lines[0])->toBe('claimId,student,challengeId,institution,phase,claimType,recruitName,recruitPhone,state,lga,status,provisionalPoints,auditedPoints,createdAt');
    expect(count($lines))->toBe(2);
});

it('exports students and institutions csvs', function () {
    studentWithProfile();
    $admin = User::factory()->admin()->create();

    $students = $this->withHeaders(apiAuthHeader($admin))->get('/api/admin/exports/students.csv');
    $students->assertOk();
    expect(explode("\n", trim($students->streamedContent()))[0])
        ->toBe('challengeId,fullName,email,phone,institution,state,department,graduationYear,status');

    $institutions = $this->withHeaders(apiAuthHeader($admin))->get('/api/admin/exports/institutions.csv');
    $institutions->assertOk();
    expect(explode("\n", trim($institutions->streamedContent()))[0])
        ->toBe('name,shortCode,state,active,coordinatorName,createdAt');
});

it('reconciles the institution leaderboard csv with the live json endpoint', function () {
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['provisional_points' => 10]);
    $admin = User::factory()->admin()->create();

    $json = collect($this->withHeaders(apiAuthHeader($admin))
        ->getJson("/api/leaderboards/institution?phase_id={$phase->id}")->json('data'))
        ->firstWhere('institution_id', $student->studentProfile->institution_id);

    $csvResponse = $this->withHeaders(apiAuthHeader($admin))
        ->get("/api/admin/exports/leaderboard/institution.csv?phase_id={$phase->id}");
    $lines = explode("\n", trim($csvResponse->streamedContent()));
    $dataRow = str_getcsv($lines[1]);

    expect((int) $dataRow[0])->toBe($json['rank'])
        ->and($dataRow[1])->toBe($json['institution_name'])
        ->and((int) $dataRow[4])->toBe($json['provisional_score']);
});

it('forbids a student from accessing exports', function () {
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->get('/api/admin/exports/claims.csv')
        ->assertForbidden();
});
