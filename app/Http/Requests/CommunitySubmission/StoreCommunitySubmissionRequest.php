<?php

namespace App\Http\Requests\CommunitySubmission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunitySubmissionRequest extends FormRequest
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
            'platform' => ['required', Rule::in(['x', 'facebook', 'youtube'])],
            'post_url' => ['required', 'url', 'max:2048'],
            'community_type' => ['required', Rule::in(['verifiers', 'tworkers', 'customers', 'vendors', 'students'])],
            'consent_confirmed' => ['required', 'accepted'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
