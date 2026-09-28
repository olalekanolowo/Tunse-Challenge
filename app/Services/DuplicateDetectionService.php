<?php

namespace App\Services;

use App\Models\Claim;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    /**
     * Other students' claims sharing the same recruit phone as the given claim.
     * Non-blocking by design — the doc calls for flagging, not preventing, this case.
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
}
