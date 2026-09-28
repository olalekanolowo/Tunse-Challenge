<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'challenge_id' => $this->challenge_id,
            'institution_id' => $this->institution_id,
            'institution_name' => $this->whenLoaded('institution', fn () => $this->institution->name),
            'phone' => $this->phone,
            'state' => $this->state,
            'department' => $this->department,
            'graduation_year' => $this->graduation_year,
            'status' => $this->status->value,
            'approved_at' => $this->approved_at?->toIso8601String(),
        ];
    }
}
