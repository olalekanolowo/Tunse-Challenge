<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isStaff = $request->user()?->isStaff();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'student_email' => $this->when($isStaff, fn () => $this->whenLoaded('user', fn () => $this->user->email)),
            'student_profile' => $this->when(
                $isStaff && $this->relationLoaded('user') && $this->user->relationLoaded('studentProfile'),
                fn () => new StudentProfileResource($this->user->studentProfile)
            ),
            'institution_id' => $this->institution_id,
            'institution_name' => $this->whenLoaded('institution', fn () => $this->institution->name),
            'phase_id' => $this->phase_id,
            'claim_type' => new ClaimTypeResource($this->whenLoaded('claimType')),
            'recruit_name' => $this->recruit_name,
            'recruit_phone' => $this->recruit_phone,
            'state' => $this->state,
            'lga' => $this->lga,
            'category' => $this->category,
            'date_recruited' => $this->date_recruited?->toDateString(),
            'has_photo' => (bool) $this->photo_path,
            'notes' => $this->notes,
            'declaration_at' => $this->declaration_at?->toIso8601String(),
            'status' => $this->status->value,
            'provisional_points' => $this->provisional_points,
            'audited_points' => $this->audited_points,
            'tworker_phone' => $this->tworker_phone,
            'transaction_reference' => $this->transaction_reference,
            'approx_value' => $this->approx_value,
            'rating' => $this->rating,
            'created_at' => $this->created_at?->toIso8601String(),
            'audits' => $this->when($isStaff, fn () => AuditResource::collection($this->whenLoaded('audits'))),
            'duplicate_warnings' => $this->when(
                $isStaff && isset($this->duplicateWarnings),
                fn () => $this->duplicateWarnings
            ),
            'risk_reasons' => $this->when($isStaff && isset($this->riskReasons), fn () => $this->riskReasons),
        ];
    }
}
