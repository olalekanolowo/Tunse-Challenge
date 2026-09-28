<?php

namespace App\Http\Requests\Claim;

use App\Enums\AuditOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ReviewClaimRequest extends FormRequest
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
            'outcome' => ['required', new Enum(AuditOutcome::class)],
            'audit_notes' => ['nullable', 'string', 'max:2000'],
            'backend_lookup_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
