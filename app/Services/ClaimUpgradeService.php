<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimType;
use Illuminate\Validation\ValidationException;

class ClaimUpgradeService
{
    /**
     * Phase 1 codes that all attribute the same recruit to a single role:
     * a student may only hold one of these per recruit phone per phase.
     *
     * @var array<int, string>
     */
    private const PHASE_ONE_ROLE_FAMILY = ['verified_tworker', 'active_verifier'];

    /**
     * Reject a new claim submission if the same student already has a claim
     * for the same recruit phone, in the same phase, in the same role family
     * (e.g. Verified T-worker vs Active Verifier) — regardless of which
     * direction the "upgrade" would go. The student is pointed at the
     * existing claim rather than having it silently mutated.
     *
     * @throws ValidationException
     */
    public function assertNoRoleConflict(int $userId, int $phaseId, string $recruitPhone, ClaimType $newClaimType, ?int $excludeClaimId = null): void
    {
        if (! in_array($newClaimType->code, self::PHASE_ONE_ROLE_FAMILY, true)) {
            return;
        }

        $existing = Claim::query()
            ->where('user_id', $userId)
            ->where('phase_id', $phaseId)
            ->where('recruit_phone', $recruitPhone)
            ->when($excludeClaimId, fn ($query) => $query->where('id', '!=', $excludeClaimId))
            ->whereHas('claimType', fn ($query) => $query->whereIn('code', self::PHASE_ONE_ROLE_FAMILY))
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'recruit_phone' => ["You already have a claim (#{$existing->id}) for this recruit in this phase. Request a correction instead of submitting a new claim."],
            ]);
        }
    }
}
