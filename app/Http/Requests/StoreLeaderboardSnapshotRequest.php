<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaderboardSnapshotRequest extends FormRequest
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
            'snapshot_type' => ['required', Rule::in(['provisional', 'final'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
