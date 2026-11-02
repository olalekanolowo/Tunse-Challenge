<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Models\Claim;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DuplicateDetectionService
{
    /**
     * Other students' claims sharing the same recruit phone as the given claim.
     * New submissions are blocked by assertPhoneNotClaimedByAnotherStudent(), so this
     * mainly surfaces claims created before that rule existed (and race conditions).
     *
     * @return Collection<int, Claim>
     */
    public function findDuplicatesFor(Claim $claim): Collection
    {
        return Claim::query()
            ->where('recruit_phone', $claim->recruit_phone)
            ->where('user_id', '!=', $claim->user_id)
            ->when($claim->id, fn ($query) => $query->where('id', '!=', $claim->id))
            ->with('user:id,name')
            ->get();
    }

    /**
     * Reject a claim whose recruit phone another student already claimed in the same
     * phase. Rejected claims don't count, so an admin rejecting a false claim frees
     * the number for the student who genuinely recruited that person.
     *
     * @throws ValidationException
     */
    public function assertPhoneNotClaimedByAnotherStudent(int $userId, int $phaseId, string $recruitPhone): void
    {
        $claimedElsewhere = Claim::query()
            ->where('recruit_phone', $recruitPhone)
            ->where('phase_id', $phaseId)
            ->where('user_id', '!=', $userId)
            ->where('status', '!=', ClaimStatus::Rejected)
            ->exists();

        if ($claimedElsewhere) {
            throw ValidationException::withMessages([
                'recruit_phone' => ['This recruit phone number has already been claimed by another student. If you recruited this person, contact the challenge admin.'],
            ]);
        }
    }
}
