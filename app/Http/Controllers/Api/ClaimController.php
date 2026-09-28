<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditOutcome;
use App\Enums\ClaimStatus;
use App\Enums\PhaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\ReviewClaimRequest;
use App\Http\Requests\Claim\StoreClaimRequest;
use App\Http\Requests\Claim\UpdateClaimRequest;
use App\Http\Resources\ClaimResource;
use App\Models\Audit;
use App\Models\Claim;
use App\Models\ClaimType;
use App\Services\ActivityLogger;
use App\Services\ClaimUpgradeService;
use App\Services\DuplicateDetectionService;
use App\Services\PhoneNormalizer;
use App\Services\RiskFlagService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClaimController extends Controller
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly ClaimUpgradeService $claimUpgradeService,
        private readonly ScoringService $scoringService,
        private readonly DuplicateDetectionService $duplicateDetectionService,
        private readonly RiskFlagService $riskFlagService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $query = Claim::query()->with(['claimType'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('phase_id')) {
            $query->where('phase_id', $request->integer('phase_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ClaimResource::collection($query->latest()->paginate(20));
    }

    public function show(Request $request, Claim $claim)
    {
        $this->authorize('view', $claim);

        $claim->load(['claimType', 'audits.auditor', 'institution']);

        if ($request->user()->isStaff()) {
            $claim->load('user.studentProfile');
            $claim->duplicateWarnings = $this->duplicateDetectionService->findDuplicatesFor($claim)
                ->map(fn (Claim $duplicate) => [
                    'claim_id' => $duplicate->id,
                    'student_name' => $duplicate->user->name,
                ])->values();
        }

        return new ClaimResource($claim);
    }

    public function store(StoreClaimRequest $request)
    {
        $data = $request->validated();
        $claimType = ClaimType::with('phase')->findOrFail($data['claim_type_id']);
        $phase = $claimType->phase;

        if ($phase->status !== PhaseStatus::Open) {
            throw ValidationException::withMessages([
                'claim_type_id' => ["The {$phase->name} phase is not currently open for submissions."],
            ]);
        }

        $recruitPhone = $this->phoneNormalizer->normalize($data['recruit_phone']);
        $user = $request->user();
        $institutionId = $user->studentProfile->institution_id;

        $this->claimUpgradeService->assertNoRoleConflict($user->id, $phase->id, $recruitPhone, $claimType);

        if (Claim::where('user_id', $user->id)
            ->where('claim_type_id', $claimType->id)
            ->where('recruit_phone', $recruitPhone)
            ->where('phase_id', $phase->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'recruit_phone' => ['You have already submitted this exact claim.'],
            ]);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('claim-photos', 'local');
        }

        $claim = Claim::create([
            'user_id' => $user->id,
            'institution_id' => $institutionId,
            'phase_id' => $phase->id,
            'claim_type_id' => $claimType->id,
            'recruit_name' => $data['recruit_name'],
            'recruit_phone' => $recruitPhone,
            'state' => $data['state'],
            'lga' => $data['lga'],
            'category' => $data['category'] ?? null,
            'date_recruited' => $data['date_recruited'],
            'photo_path' => $photoPath,
            'notes' => $data['notes'] ?? null,
            'declaration_at' => now(),
            'status' => ClaimStatus::Submitted,
            'provisional_points' => $this->scoringService->provisionalPointsFor($claimType),
            'tworker_phone' => $data['tworker_phone'] ?? null,
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'approx_value' => $data['approx_value'] ?? null,
            'rating' => $data['rating'] ?? null,
        ]);

        $duplicates = $this->duplicateDetectionService->findDuplicatesFor($claim);

        $this->activityLogger->record('claim.submitted', $claim, [
            'claim_type' => $claimType->code,
            'points' => $claim->provisional_points,
        ], $user);

        $claim->load('claimType');
        $claim->duplicateWarnings = $duplicates->map(fn (Claim $duplicate) => [
            'claim_id' => $duplicate->id,
        ])->values();

        return (new ClaimResource($claim))->response()->setStatusCode(201);
    }

    public function update(UpdateClaimRequest $request, Claim $claim)
    {
        $this->authorize('update', $claim);

        $data = $request->validated();
        $claimType = isset($data['claim_type_id'])
            ? ClaimType::with('phase')->findOrFail($data['claim_type_id'])
            : $claim->claimType;

        $recruitPhone = isset($data['recruit_phone'])
            ? $this->phoneNormalizer->normalize($data['recruit_phone'])
            : $claim->recruit_phone;

        if (isset($data['claim_type_id']) || isset($data['recruit_phone'])) {
            $this->claimUpgradeService->assertNoRoleConflict(
                $claim->user_id,
                $claim->phase_id,
                $recruitPhone,
                $claimType,
                excludeClaimId: $claim->id,
            );

            if (Claim::where('user_id', $claim->user_id)
                ->where('id', '!=', $claim->id)
                ->where('claim_type_id', $claimType->id)
                ->where('recruit_phone', $recruitPhone)
                ->where('phase_id', $claim->phase_id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'recruit_phone' => ['You already have another claim for this recruit and claim type.'],
                ]);
            }
        }

        $photoPath = $claim->photo_path;
        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('claim-photos', 'local');
        }

        $wasCorrectionRequested = $claim->status === ClaimStatus::CorrectionRequested;

        $claim->update([
            'claim_type_id' => $claimType->id,
            'recruit_name' => $data['recruit_name'] ?? $claim->recruit_name,
            'recruit_phone' => $recruitPhone,
            'state' => $data['state'] ?? $claim->state,
            'lga' => $data['lga'] ?? $claim->lga,
            'category' => $data['category'] ?? $claim->category,
            'date_recruited' => $data['date_recruited'] ?? $claim->date_recruited,
            'photo_path' => $photoPath,
            'notes' => $data['notes'] ?? $claim->notes,
            'status' => $wasCorrectionRequested ? ClaimStatus::Submitted : $claim->status,
            'provisional_points' => $this->scoringService->provisionalPointsFor($claimType),
            'tworker_phone' => $data['tworker_phone'] ?? $claim->tworker_phone,
            'transaction_reference' => $data['transaction_reference'] ?? $claim->transaction_reference,
            'approx_value' => $data['approx_value'] ?? $claim->approx_value,
            'rating' => $data['rating'] ?? $claim->rating,
        ]);

        $this->activityLogger->record(
            $wasCorrectionRequested ? 'claim.correction_submitted' : 'claim.updated',
            $claim,
            ['claim_type' => $claimType->code, 'points' => $claim->provisional_points],
            $request->user(),
        );

        $claim->load('claimType');

        return new ClaimResource($claim);
    }

    public function adminIndex(Request $request)
    {
        $query = Claim::query()->with(['claimType', 'user.studentProfile', 'institution']);

        if ($request->filled('phase_id')) {
            $query->where('phase_id', $request->integer('phase_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->integer('institution_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->value();
            $query->where(fn ($q) => $q->where('recruit_name', 'like', "%{$search}%")
                ->orWhere('recruit_phone', 'like', "%{$search}%"));
        }

        $claims = $query->get()->map(function (Claim $claim) {
            $claim->riskReasons = $this->riskFlagService->reasonsFor($claim);

            return $claim;
        });

        if ($request->filled('risk_reason')) {
            $claims = $claims->filter(fn (Claim $claim) => in_array($request->string('risk_reason')->value(), $claim->riskReasons, true))->values();
        }

        $sorted = $request->string('sort')->value() === 'risk'
            ? $claims->sortByDesc(fn (Claim $claim) => count($claim->riskReasons))->values()
            : $claims->sortByDesc('created_at')->values();

        $perPage = 20;
        $page = $request->integer('page', 1);
        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return ClaimResource::collection($paginator);
    }

    public function review(ReviewClaimRequest $request, Claim $claim)
    {
        $data = $request->validated();
        $outcome = AuditOutcome::from($data['outcome']);

        $auditedPoints = $this->scoringService->auditedPointsFor($claim, $outcome);
        $newStatus = match ($outcome) {
            AuditOutcome::Verified => ClaimStatus::Verified,
            AuditOutcome::Rejected => ClaimStatus::Rejected,
            AuditOutcome::Flagged => ClaimStatus::Flagged,
            AuditOutcome::Correction => ClaimStatus::CorrectionRequested,
        };

        $audit = Audit::create([
            'claim_id' => $claim->id,
            'auditor_id' => $request->user()->id,
            'outcome' => $outcome,
            'backend_lookup_key' => $data['backend_lookup_key'] ?? null,
            'audit_notes' => $data['audit_notes'] ?? null,
            'audited_at' => now(),
        ]);

        $claim->update([
            'status' => $newStatus,
            'audited_points' => $auditedPoints,
        ]);

        $this->activityLogger->record('claim.reviewed', $claim, [
            'outcome' => $outcome->value,
            'audited_points' => $auditedPoints,
        ], $request->user());

        $claim->load(['claimType', 'audits.auditor', 'institution', 'user.studentProfile']);

        return new ClaimResource($claim);
    }
}
