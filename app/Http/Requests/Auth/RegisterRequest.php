<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['required', 'string', 'max:20'],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'state' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'student_id_number' => ['required', 'string', 'max:100'],
            'student_id_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
