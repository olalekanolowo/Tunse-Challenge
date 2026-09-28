<?php

use App\Enums\PhaseStatus;
use App\Models\Claim;
use App\Models\ClaimType;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function claimPayload(ClaimType $claimType, array $overrides = []): array
{
    return array_merge([
        'claim_type_id' => $claimType->id,
        'recruit_name' => 'Recruit One',
        'recruit_phone' => '08099998888',
        'state' => 'Lagos',
        'lga' => 'Ikeja',
        'category' => 'plumber',
        'date_recruited' => now()->toDateString(),
        'notes' => 'Met at the market',
        'declaration' => true,
    ], $overrides);
}

it('awards the correct provisional points for a verified t-worker claim', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();

    $response = $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType));

    $response->assertCreated()
        ->assertJsonPath('data.provisional_points', 10)
        ->assertJsonPath('data.status', 'submitted');
});

it('awards the correct provisional points for an active verifier claim', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'active_verifier', 'base_points' => 25]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType))
        ->assertCreated()
        ->assertJsonPath('data.provisional_points', 25);
});

it('rejects a second role-family claim for the same recruit in the same phase', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $tworker = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $verifier = ClaimType::factory()->for($phase)->create(['code' => 'active_verifier', 'base_points' => 25]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($tworker, ['recruit_phone' => '08099998888']))
        ->assertCreated();

    $response = $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($verifier, ['recruit_phone' => '08099998888']));

    $response->assertUnprocessable()->assertJsonValidationErrors('recruit_phone');
    expect(Claim::count())->toBe(1);
});

it('rejects an exact duplicate claim submission by the same student', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType))
        ->assertCreated();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType))
        ->assertUnprocessable();

    expect(Claim::count())->toBe(1);
});

it('allows different students to claim the same recruit phone but flags it', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $studentA = studentWithProfile();
    $studentB = studentWithProfile();

    $this->withHeaders(apiAuthHeader($studentA))
        ->postJson('/api/claims', claimPayload($claimType, ['recruit_phone' => '08011112222']))
        ->assertCreated();

    $this->withHeaders(apiAuthHeader($studentB))
        ->postJson('/api/claims', claimPayload($claimType, ['recruit_phone' => '08011112222']))
        ->assertCreated();

    expect(Claim::where('recruit_phone', '+2348011112222')->count())->toBe(2);
});

it('submits a phase 2 qualified vendor claim with null base points as zero provisional points', function () {
    $phase = Phase::factory()->open()->create(['number' => 2]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'qualified_vendor', 'base_points' => null]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType))
        ->assertCreated()
        ->assertJsonPath('data.provisional_points', 0);
});

it('accepts phase 3 fields and requires a rating only for rated job claims', function () {
    $phase = Phase::factory()->open()->create(['number' => 3]);
    $ratedJob = ClaimType::factory()->for($phase)->create(['code' => 'rated_job', 'base_points' => 5]);
    $completedJob = ClaimType::factory()->for($phase)->create(['code' => 'completed_job', 'base_points' => 15]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($ratedJob, [
            'recruit_phone' => '08033334444',
            'tworker_phone' => '08055556666',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rating');

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($ratedJob, [
            'recruit_phone' => '08033334444',
            'tworker_phone' => '08055556666',
            'rating' => 5,
        ]))
        ->assertCreated();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($completedJob, [
            'recruit_phone' => '08077778888',
            'transaction_reference' => 'TXN-001',
            'approx_value' => 15000,
        ]))
        ->assertCreated()
        ->assertJsonPath('data.transaction_reference', 'TXN-001');
});

it('validates the claim photo file type and size', function () {
    Storage::fake('local');
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType, [
            'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photo');

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType, [
            'photo' => UploadedFile::fake()->image('evidence.jpg'),
        ]))
        ->assertCreated();
});

it('is rate limited after repeated claim submissions', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();
    $headers = apiAuthHeader($student);

    $lastResponse = null;
    for ($i = 0; $i < 11; $i++) {
        $lastResponse = $this->withHeaders($headers)->postJson('/api/claims', claimPayload($claimType, [
            'recruit_phone' => '0803300'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        ]));
    }

    $lastResponse->assertStatus(429);
});

it('rejects a claim submission when the phase is not open', function () {
    $phase = Phase::factory()->create(['number' => 1, 'status' => PhaseStatus::Frozen]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $student = studentWithProfile();

    $this->withHeaders(apiAuthHeader($student))
        ->postJson('/api/claims', claimPayload($claimType))
        ->assertUnprocessable();
});

it('forbids staff from submitting a claim', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $admin = User::factory()->admin()->create();

    $this->withHeaders(apiAuthHeader($admin))
        ->postJson('/api/claims', claimPayload($claimType))
        ->assertForbidden();
});

it('lets a student view only their own claims and forbids viewing others', function () {
    $phase = Phase::factory()->open()->create(['number' => 1]);
    $claimType = ClaimType::factory()->for($phase)->create(['code' => 'verified_tworker', 'base_points' => 10]);
    $studentA = studentWithProfile();
    $studentB = studentWithProfile();

    $created = $this->withHeaders(apiAuthHeader($studentA))
        ->postJson('/api/claims', claimPayload($claimType))
        ->json('data');

    $this->withHeaders(apiAuthHeader($studentB))
        ->getJson("/api/claims/{$created['id']}")
        ->assertForbidden();

    $this->withHeaders(apiAuthHeader($studentA))
        ->getJson('/api/claims')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
