<?php

namespace App\Http\Requests\ClaimType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimTypeRequest extends FormRequest
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
            'label' => ['sometimes', 'string', 'max:255'],
            'base_points' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'validation_rule_text' => ['nullable', 'string'],
        ];
    }
}
