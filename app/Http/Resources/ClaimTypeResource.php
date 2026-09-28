<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase_id' => $this->phase_id,
            'code' => $this->code,
            'label' => $this->label,
            'base_points' => $this->base_points,
            'active' => $this->active,
            'validation_rule_text' => $this->validation_rule_text,
        ];
    }
}
