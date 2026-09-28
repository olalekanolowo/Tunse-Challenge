<?php

namespace App\Http\Requests\Claim;

use App\Models\Claim;
use App\Models\ClaimType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimRequest extends FormRequest
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
            'claim_type_id' => ['sometimes', 'integer', 'exists:claim_types,id'],
            'recruit_name' => ['sometimes', 'string', 'max:255'],
            'recruit_phone' => ['sometimes', 'string', 'max:20'],
            'state' => ['sometimes', 'string', 'max:100'],
            'lga' => ['sometimes', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'date_recruited' => ['sometimes', 'date', 'before_or_equal:today'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'tworker_phone' => ['nullable', 'string', 'max:20'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'approx_value' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Claim $claim */
            $claim = $this->route('claim');
            $claimTypeId = $this->input('claim_type_id', $claim->claim_type_id);
            $claimType = ClaimType::with('phase')->find($claimTypeId);

            if (! $claimType) {
                return;
            }

            if ($claimType->phase_id !== $claim->phase_id) {
                $validator->errors()->add('claim_type_id', 'A claim cannot be moved to a different phase.');

                return;
            }

            $hasRating = $this->filled('rating') || ($claim->rating !== null && ! $this->has('rating'));
            if ($claimType->phase->number === 3 && $claimType->code === 'rated_job' && ! $hasRating) {
                $validator->errors()->add('rating', 'A rating is required for a rated job claim.');
            }
        });
    }
}
