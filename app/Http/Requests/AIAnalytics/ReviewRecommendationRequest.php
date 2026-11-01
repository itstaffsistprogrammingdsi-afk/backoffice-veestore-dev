<?php

namespace App\Http\Requests\AIAnalytics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['reviewed', 'approved', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
