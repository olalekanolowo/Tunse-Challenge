<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScoreAdjustmentRequest extends FormRequest
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
            'targetable_type' => ['required', Rule::in(['user', 'institution'])],
            'targetable_id' => [
                'required',
                'integer',
                Rule::exists($this->input('targetable_type') === 'institution' ? (new Institution)->getTable() : (new User)->getTable(), 'id'),
            ],
            'phase_id' => ['required', 'integer', 'exists:phases,id'],
            'points' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function targetableModelClass(): string
    {
        return $this->input('targetable_type') === 'institution' ? Institution::class : User::class;
    }
}
