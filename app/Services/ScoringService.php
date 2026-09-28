<?php

namespace App\Services;

use App\Enums\AuditOutcome;
use App\Models\Claim;
use App\Models\ClaimType;

class ScoringService
{
    public function provisionalPointsFor(ClaimType $claimType): int
    {
        return $claimType->base_points ?? 0;
    }

    /**
     * The audited points a claim should carry after a given audit outcome,
     * mirroring the frontend's adminStore.reviewClaim mapping exactly:
     * verified -> claim type's base points (falling back to the provisional
     * points already recorded), rejected -> 0, flagged/correction -> unchanged (null).
     */
    public function auditedPointsFor(Claim $claim, AuditOutcome $outcome): ?int
    {
        return match ($outcome) {
            AuditOutcome::Verified => $claim->claimType->base_points ?? $claim->provisional_points,
            AuditOutcome::Rejected => 0,
            AuditOutcome::Flagged, AuditOutcome::Correction => null,
        };
    }
}
