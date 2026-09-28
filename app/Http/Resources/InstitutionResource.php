<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_code' => $this->short_code,
            'state' => $this->state,
            'active' => $this->active,
            'coordinator_name' => $this->when($request->user()?->isStaff(), $this->coordinator_name),
            'participant_count' => $this->whenCounted('studentProfiles'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
