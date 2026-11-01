<?php

namespace App\Http\Requests\AIAnalytics;

use Illuminate\Foundation\Http\FormRequest;

class IndexAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'uuid', 'exists:stores,id'],
            'risk_level' => ['nullable', 'in:low,medium,high,critical'],
            'status' => ['nullable', 'in:recommended,reviewed,approved,rejected'],
        ];
    }
}
