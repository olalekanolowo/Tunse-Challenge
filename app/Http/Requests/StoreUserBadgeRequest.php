<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'badge_id' => ['required', 'integer', 'exists:badges,id'],
            'phase_id' => ['required', 'integer', 'exists:phases,id'],
        ];
    }
}
