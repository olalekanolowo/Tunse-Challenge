<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Strip spaces, dashes and brackets so "0801 234-5678" validates like "08012345678".
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s\-()]+/', '', $this->input('phone'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:255', "regex:/^[\pL\s.'-]+$/u"],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['required', 'string', 'regex:/^(\+?234|0)?[789][01]\d{8}$/'],
            'institution_id' => ['required', 'integer', Rule::exists('institutions', 'id')->where('active', true)],
            'state' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:255'],
            'graduation_year' => ['required', 'integer', 'min:2015', 'max:'.(now()->year + 10)],
            'student_id_number' => ['required', 'string', 'max:100'],
            'student_id_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.regex' => 'Your name may only contain letters, spaces, hyphens, apostrophes and full stops.',
            'institution_id.exists' => 'Select an institution from the list.',
            'phone.regex' => 'Enter a valid Nigerian mobile number, e.g. 0801 234 5678.',
        ];
    }
}
