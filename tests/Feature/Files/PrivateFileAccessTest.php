<?php

use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('forbids a student from downloading another students id file', function () {
    Storage::fake('local');
    $studentA = studentWithProfile();
    $studentA->studentProfile->update(['student_id_file_path' => 'student-ids/a.jpg']);
    Storage::disk('local')->put('student-ids/a.jpg', 'fake-image-content');
    $studentB = studentWithProfile();

    $this->withHeaders(apiAuthHeader($studentB))
        ->get("/api/students/{$studentA->studentProfile->id}/id-file")
        ->assertForbidden();
});

it('lets staff download any students id file', function () {
    Storage::fake('local');
    $student = studentWithProfile();
    $student->studentProfile->update(['student_id_file_path' => 'student-ids/a.jpg']);
    Storage::disk('local')->put('student-ids/a.jpg', 'fake-image-content');
    $admin = User::factory()->admin()->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->get("/api/students/{$student->studentProfile->id}/id-file")
        ->assertOk();
});

it('lets the owning student download their own claim photo and staff download any', function () {
    Storage::fake('local');
    $phase = Phase::factory()->open()->create();
    $claimType = ClaimType::factory()->for($phase)->create(['base_points' => 10]);
    $student = studentWithProfile();
    $claim = Claim::factory()->for($student)->for($phase)->for($claimType, 'claimType')->for($student->studentProfile->institution)
        ->create(['photo_path' => 'claim-photos/photo.jpg']);
    Storage::disk('local')->put('claim-photos/photo.jpg', 'fake-image-content');
    $otherStudent = studentWithProfile();
    $admin = User::factory()->admin()->create();

    $this->withHeaders(apiAuthHeader($student))
        ->get("/api/claims/{$claim->id}/photo")
        ->assertOk();

    $this->withHeaders(apiAuthHeader($otherStudent))
        ->get("/api/claims/{$claim->id}/photo")
        ->assertForbidden();

    $this->withHeaders(apiAuthHeader($admin))
        ->get("/api/claims/{$claim->id}/photo")
        ->assertOk();
});

it('requires authentication to download any protected file and never exposes a public storage path', function () {
    Storage::fake('local');
    $student = studentWithProfile();
    $student->studentProfile->update(['student_id_file_path' => 'student-ids/a.jpg']);
    Storage::disk('local')->put('student-ids/a.jpg', 'fake-image-content');

    $this->get("/api/students/{$student->studentProfile->id}/id-file")->assertUnauthorized();

    // No public symlink is published for the private disk, so the file is never
    // reachable via a guessed /storage/... URL regardless of the exact status
    // the server returns for that unmapped path (404 or a static-asset 403).
    expect($this->get('/storage/student-ids/a.jpg')->status())->not->toBe(200);
});
