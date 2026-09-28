<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'claim_id' => $this->claim_id,
            'auditor_name' => $this->whenLoaded('auditor', fn () => $this->auditor->name),
            'outcome' => $this->outcome->value,
            'backend_lookup_key' => $this->backend_lookup_key,
            'audit_notes' => $this->audit_notes,
            'audited_at' => $this->audited_at?->toIso8601String(),
        ];
    }
}
