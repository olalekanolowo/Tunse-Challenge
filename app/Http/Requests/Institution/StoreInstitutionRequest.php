<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'short_code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:institutions,short_code'],
            'state' => ['required', 'string', 'max:100'],
            'coordinator_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
