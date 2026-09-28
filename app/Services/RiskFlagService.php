<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Models\Claim;

class RiskFlagService
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService,
        private readonly DuplicateDetectionService $duplicateDetectionService,
    ) {}

    /**
     * @return array<int, string>
     */
    public function reasonsFor(Claim $claim): array
    {
        $reasons = [];

        $individual = $this->leaderboardService->individualLeaderboard($claim->phase);
        $studentRow = $individual->firstWhere('user_id', $claim->user_id);

        if ($studentRow && $studentRow['rank'] <= 10) {
            $reasons[] = 'top10_national';
        }

        if ($studentRow) {
            $institutionRank = $individual
                ->where('institution_id', $claim->institution_id)
                ->sortByDesc('provisional_score')
                ->values()
                ->search(fn ($row) => $row['user_id'] === $claim->user_id);

            if ($institutionRank === 0) {
                $reasons[] = 'top1_institution';
            }
        }

        $institutionLeaderboard = $this->leaderboardService->institutionLeaderboard($claim->phase);
        $institutionRow = $institutionLeaderboard->firstWhere('institution_id', $claim->institution_id);
        if ($institutionRow && $institutionRow['rank'] <= 3) {
            $reasons[] = 'institution_top3';
        }

        $recentClaimCount = Claim::where('user_id', $claim->user_id)
            ->where('phase_id', $claim->phase_id)
            ->where('created_at', '>=', now()->subHours(72))
            ->count();
        if ($recentClaimCount >= 3) {
            $reasons[] = 'high_velocity';
        }

        if ($this->duplicateDetectionService->findDuplicatesFor($claim)->isNotEmpty()) {
            $reasons[] = 'duplicate_phone';
        }

        if ($claim->status === ClaimStatus::Flagged) {
            $reasons[] = 'manual_flag';
        }

        return $reasons;
    }
}
