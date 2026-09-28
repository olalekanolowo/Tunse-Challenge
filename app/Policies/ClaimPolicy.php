<?php

namespace App\Policies;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function view(User $user, Claim $claim): bool
    {
        return $user->isStaff() || $claim->user_id === $user->id;
    }

    public function viewPhoto(User $user, Claim $claim): bool
    {
        return $user->isStaff() || $claim->user_id === $user->id;
    }

    /**
     * A student may edit their own claim while it's still pending audit, or
     * while an auditor has asked for a correction (e.g. a mistyped phone
     * digit) — that status exists specifically so the student can fix and
     * resubmit. Once verified/rejected/flagged, the decision (and any points
     * already applied) must stand.
     */
    public function update(User $user, Claim $claim): bool
    {
        return $claim->user_id === $user->id
            && in_array($claim->status, [ClaimStatus::Submitted, ClaimStatus::CorrectionRequested], true);
    }
}
