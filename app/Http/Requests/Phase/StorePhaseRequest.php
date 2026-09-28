<?php

namespace App\Http\Requests\Phase;

use Illuminate\Foundation\Http\FormRequest;

class StorePhaseRequest extends FormRequest
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
            'number' => ['required', 'integer', 'min:0', 'max:255', 'unique:phases,number'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'prize_text' => ['nullable', 'string'],
            'rules_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
