<?php

namespace App\Http\Requests\ClaimType;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimTypeRequest extends FormRequest
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
            'phase_id' => ['required', 'integer', 'exists:phases,id'],
            'code' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'base_points' => ['nullable', 'integer', 'min:0'],
            'validation_rule_text' => ['nullable', 'string'],
        ];
    }
}
