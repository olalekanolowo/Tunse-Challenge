<?php

namespace App\Http\Requests\Claim;

use App\Models\ClaimType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
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
            'claim_type_id' => ['required', 'integer', 'exists:claim_types,id'],
            'recruit_name' => ['required', 'string', 'max:255'],
            'recruit_phone' => ['required', 'string', 'max:20'],
            'state' => ['required', 'string', 'max:100'],
            'lga' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'date_recruited' => ['required', 'date', 'before_or_equal:today'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'declaration' => ['required', 'accepted'],
            'tworker_phone' => ['nullable', 'string', 'max:20'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'approx_value' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $claimType = ClaimType::with('phase')->find($this->input('claim_type_id'));

            if (! $claimType) {
                return;
            }

            if ($claimType->phase->number === 3 && $claimType->code === 'rated_job' && ! $this->filled('rating')) {
                $validator->errors()->add('rating', 'A rating is required for a rated job claim.');
            }
        });
    }
}
