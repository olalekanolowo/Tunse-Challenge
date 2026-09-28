<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstitutionRequest extends FormRequest
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
            'short_code' => ['sometimes', 'string', 'max:20', 'alpha_dash', Rule::unique('institutions', 'short_code')->ignore($this->route('institution'))],
            'state' => ['sometimes', 'string', 'max:100'],
            'coordinator_name' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
