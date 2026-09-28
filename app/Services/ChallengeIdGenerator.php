<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Facades\DB;

class ChallengeIdGenerator
{
    /**
     * Generate the next immutable Challenge ID for an institution, e.g. TCH-UNILAG-0042.
     *
     * Race-safe across concurrent registrations via a row lock inside a transaction.
     */
    public function generateFor(Institution $institution): string
    {
        return DB::transaction(function () use ($institution) {
            $locked = Institution::whereKey($institution->id)->lockForUpdate()->firstOrFail();
            $locked->increment('next_sequence');

            return sprintf('TCH-%s-%04d', $locked->short_code, $locked->next_sequence);
        });
    }
}
