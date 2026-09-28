<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaderboardSnapshotRequest;
use App\Http\Resources\LeaderboardSnapshotResource;
use App\Models\LeaderboardSnapshot;
use App\Models\Phase;
use App\Services\ActivityLogger;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaderboardSnapshotController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request)
    {
        $query = LeaderboardSnapshot::query();

        if ($request->filled('phase_id')) {
            $query->where('phase_id', $request->integer('phase_id'));
        }

        return LeaderboardSnapshotResource::collection($query->latest('created_at')->get());
    }

    public function store(StoreLeaderboardSnapshotRequest $request)
    {
        $data = $request->validated();
        $phase = Phase::findOrFail($data['phase_id']);

        if ($data['snapshot_type'] === 'final' && $phase->status !== PhaseStatus::Closed) {
            throw ValidationException::withMessages([
                'snapshot_type' => ['A final snapshot can only be created once the phase is closed.'],
            ]);
        }

        $payload = [
            'individual' => $this->leaderboardService->individualLeaderboard($phase, null, $data['snapshot_type'])->values(),
            'institution' => $this->leaderboardService->institutionLeaderboard($phase, $data['snapshot_type'])->values(),
        ];

        $snapshot = LeaderboardSnapshot::create([
            'phase_id' => $phase->id,
            'snapshot_type' => $data['snapshot_type'],
            'note' => $data['note'] ?? null,
            'payload' => $payload,
            'created_by' => $request->user()->id,
        ]);

        $this->activityLogger->record('leaderboard_snapshot.created', $snapshot, [
            'snapshot_type' => $snapshot->snapshot_type,
        ], $request->user());

        return (new LeaderboardSnapshotResource($snapshot))->response()->setStatusCode(201);
    }
}
