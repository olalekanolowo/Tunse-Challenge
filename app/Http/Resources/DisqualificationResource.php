<?php

namespace App\Http\Resources;

use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisqualificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'targetable_type' => $this->targetable_type === Institution::class ? 'institution' : 'user',
            'targetable_id' => $this->targetable_id,
            'phase_id' => $this->phase_id,
            'reason' => $this->reason,
            'admin_id' => $this->admin_id,
            'active' => $this->active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
