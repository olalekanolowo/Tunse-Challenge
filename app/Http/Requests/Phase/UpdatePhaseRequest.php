<?php

namespace App\Http\Requests\Phase;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhaseRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
            'prize_text' => ['nullable', 'string'],
            'rules_version' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
