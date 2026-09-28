<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\Disqualification;
use App\Models\Institution;
use App\Models\Phase;
use App\Models\ScoreAdjustment;
use App\Models\User;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function individualLeaderboard(Phase $phase, ?int $institutionId = null, string $type = 'provisional'): Collection
    {
        $students = User::query()
            ->where('role', UserRole::Student)
            ->with(['studentProfile.institution'])
            ->whereHas('studentProfile', fn ($query) => $institutionId
                ? $query->where('institution_id', $institutionId)
                : $query->whereNotNull('id'))
            ->get();

        $adjustments = $this->adjustmentTotals(User::class, $phase->id);
        $disqualifiedUserIds = $this->disqualifiedTargetIds(User::class, $phase->id);

        $claims = Claim::query()
            ->where('phase_id', $phase->id)
            ->whereIn('user_id', $students->pluck('id'))
            ->get();

        $rows = $students->map(function (User $user) use ($claims, $adjustments, $disqualifiedUserIds, $phase) {
            $isDisqualified = $disqualifiedUserIds->contains($user->id);
            $userClaims = $claims->where('user_id', $user->id);

            $provisionalScore = $isDisqualified ? 0 : $userClaims
                ->where('status', '!=', ClaimStatus::Rejected)
                ->sum('provisional_points') + ($adjustments[$user->id] ?? 0);

            $verifiedClaims = $userClaims->where('status', ClaimStatus::Verified);
            $auditedScore = $isDisqualified ? 0 : $verifiedClaims->sum('audited_points') + ($adjustments[$user->id] ?? 0);

            return [
                'user_id' => $user->id,
                'full_name' => $user->name,
                'challenge_id' => $user->studentProfile?->challenge_id,
                'institution_id' => $user->studentProfile?->institution_id,
                'institution_name' => $user->studentProfile?->institution?->name,
                'provisional_score' => max(0, $provisionalScore),
                'audited_score' => max(0, $auditedScore),
                'verified_claim_count' => $verifiedClaims->count(),
                'unique_verified_recruits' => $verifiedClaims->pluck('recruit_phone')->unique()->count(),
                'earliest_verified_at' => $verifiedClaims->min('updated_at'),
                'disqualified' => $isDisqualified,
                'phase_id' => $phase->id,
            ];
        });

        return $this->sortAndRank($rows, $type);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function institutionLeaderboard(Phase $phase, string $type = 'provisional'): Collection
    {
        $individual = $this->individualLeaderboard($phase);
        $institutions = Institution::all();
        $adjustments = $this->adjustmentTotals(Institution::class, $phase->id);
        $disqualifiedInstitutionIds = $this->disqualifiedTargetIds(Institution::class, $phase->id);

        $rows = $institutions->map(function (Institution $institution) use ($individual, $adjustments, $disqualifiedInstitutionIds, $phase) {
            $isDisqualified = $disqualifiedInstitutionIds->contains($institution->id);
            $students = $individual->where('institution_id', $institution->id);

            $provisionalScore = $isDisqualified ? 0 : $students->sum('provisional_score') + ($adjustments[$institution->id] ?? 0);
            $auditedScore = $isDisqualified ? 0 : $students->sum('audited_score') + ($adjustments[$institution->id] ?? 0);

            return [
                'institution_id' => $institution->id,
                'institution_name' => $institution->name,
                'state' => $institution->state,
                'provisional_score' => max(0, $provisionalScore),
                'audited_score' => max(0, $auditedScore),
                'verified_participant_count' => $students->where('verified_claim_count', '>', 0)->count(),
                'total_participant_count' => $students->count(),
                'disqualified' => $isDisqualified,
                'phase_id' => $phase->id,
            ];
        });

        return $this->sortAndRank($rows, $type);
    }

    /**
     * Sums provisional points (non-rejected) across every non-community phase (number > 0),
     * honoring per-phase disqualification, mirroring computeCumulativeIndividualLeaderboard.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function cumulativeIndividualLeaderboard(): Collection
    {
        $phases = Phase::where('number', '>', 0)->get();

        $rows = $phases->flatMap(fn (Phase $phase) => $this->individualLeaderboard($phase))
            ->groupBy('user_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'user_id' => $first['user_id'],
                    'full_name' => $first['full_name'],
                    'challenge_id' => $first['challenge_id'],
                    'institution_id' => $first['institution_id'],
                    'institution_name' => $first['institution_name'],
                    'cumulative_provisional_score' => $rows->sum('provisional_score'),
                    'cumulative_audited_score' => $rows->sum('audited_score'),
                ];
            })
            ->values();

        return $rows->sortByDesc('cumulative_audited_score')->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortAndRank(Collection $rows, string $type): Collection
    {
        $scoreKey = $type === 'final' ? 'audited_score' : 'provisional_score';

        $sorted = $rows->sortBy([
            [$scoreKey, 'desc'],
            ['unique_verified_recruits', 'desc'],
            ['earliest_verified_at', 'asc'],
        ])->values();

        return $sorted->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;

            return $row;
        });
    }

    /**
     * @return array<int, int>
     */
    private function adjustmentTotals(string $targetableType, int $phaseId): array
    {
        return ScoreAdjustment::query()
            ->where('targetable_type', (new $targetableType)->getMorphClass())
            ->where('phase_id', $phaseId)
            ->selectRaw('targetable_id, sum(points) as total')
            ->groupBy('targetable_id')
            ->pluck('total', 'targetable_id')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    /**
     * @return Collection<int, int>
     */
    private function disqualifiedTargetIds(string $targetableType, int $phaseId): Collection
    {
        return Disqualification::query()
            ->where('targetable_type', (new $targetableType)->getMorphClass())
            ->where('phase_id', $phaseId)
            ->where('active', true)
            ->pluck('targetable_id');
    }
}
