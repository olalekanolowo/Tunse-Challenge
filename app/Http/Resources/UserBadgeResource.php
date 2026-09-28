<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'badge' => new BadgeResource($this->whenLoaded('badge')),
            'phase_id' => $this->phase_id,
            'awarded_at' => $this->awarded_at?->toIso8601String(),
        ];
    }
}
