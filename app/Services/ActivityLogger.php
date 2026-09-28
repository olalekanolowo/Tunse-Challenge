<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function record(string $action, ?Model $subject = null, ?array $data = null, ?User $actor = null): ActivityLog
    {
        return ActivityLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'data' => $data,
        ]);
    }
}
