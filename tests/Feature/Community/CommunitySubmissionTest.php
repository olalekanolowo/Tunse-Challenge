<?php

use App\Models\CommunitySubmission;
use App\Models\User;

function communitySubmissionPayload(array $overrides = []): array
{
    return array_merge([
        'platform' => 'x',
        'post_url' => 'https://x.com/example/status/123',
        'community_type' => 'students',
        'consent_confirmed' => true,
        'title' => 'Spreading the word',
        'description' => 'Posted about Tunse to my campus community.',
    ], $overrides);
}

it('requires consent to be confirmed', function () {
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/community-submissions', communitySubmissionPayload(['consent_confirmed' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('consent_confirmed');
});

it('creates a community submission for the authenticated student', function () {
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/community-submissions', communitySubmissionPayload())
        ->assertCreated()
        ->assertJsonPath('data.platform', 'x');
});

it('scopes visibility so a student sees only their own submissions while staff sees all', function () {
    $studentA = studentWithProfile();
    $studentB = studentWithProfile();
    $admin = User::factory()->admin()->create();

    CommunitySubmission::factory()->for($studentA)->for($studentA->studentProfile->institution)->create();
    CommunitySubmission::factory()->for($studentB)->for($studentB->studentProfile->institution)->create();

    $this->withHeaders(apiAuthHeader($studentA))
        ->getJson('/api/community-submissions')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->withHeaders(apiAuthHeader($admin))
        ->getJson('/api/admin/community-submissions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lets staff set a score note on a submission', function () {
    $student = studentWithProfile();
    $admin = User::factory()->admin()->create();
    $submission = CommunitySubmission::factory()->for($student)->for($student->studentProfile->institution)->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->patchJson("/api/admin/community-submissions/{$submission->id}/score-note", ['score_note' => 'Great reach'])
        ->assertOk()
        ->assertJsonPath('data.score_note', 'Great reach');
});
