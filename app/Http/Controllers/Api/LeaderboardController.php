<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardSnapshot;
use App\Models\Phase;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(private readonly LeaderboardService $leaderboardService) {}

    public function individual(Request $request): JsonResponse
    {
        $phase = Phase::findOrFail($request->integer('phase_id'));
        $type = $request->string('type')->value() ?: 'provisional';

        if ($type === 'final') {
            return $this->snapshotResponse($phase, 'final', 'individual');
        }

        $rows = $this->leaderboardService->individualLeaderboard(
            $phase,
            $request->filled('institution_id') ? $request->integer('institution_id') : null,
            'provisional'
        );

        return response()->json(['data' => $rows, 'available' => true]);
    }

    public function institution(Request $request): JsonResponse
    {
        $phase = Phase::findOrFail($request->integer('phase_id'));
        $type = $request->string('type')->value() ?: 'provisional';

        if ($type === 'final') {
            return $this->snapshotResponse($phase, 'final', 'institution');
        }

        $rows = $this->leaderboardService->institutionLeaderboard($phase, 'provisional');

        return response()->json(['data' => $rows, 'available' => true]);
    }

    public function cumulative(): JsonResponse
    {
        return response()->json(['data' => $this->leaderboardService->cumulativeIndividualLeaderboard()]);
    }

    private function snapshotResponse(Phase $phase, string $snapshotType, string $board): JsonResponse
    {
        $snapshot = LeaderboardSnapshot::where('phase_id', $phase->id)
            ->where('snapshot_type', $snapshotType)
            ->latest('created_at')
            ->first();

        if (! $snapshot) {
            return response()->json(['data' => [], 'available' => false]);
        }

        return response()->json([
            'data' => $snapshot->payload[$board] ?? [],
            'available' => true,
            'snapshot_id' => $snapshot->id,
        ]);
    }
}
