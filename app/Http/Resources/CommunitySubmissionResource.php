<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunitySubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'institution_id' => $this->institution_id,
            'platform' => $this->platform,
            'post_url' => $this->post_url,
            'community_type' => $this->community_type,
            'consent_confirmed' => $this->consent_confirmed,
            'title' => $this->title,
            'description' => $this->description,
            'score_note' => $this->score_note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
