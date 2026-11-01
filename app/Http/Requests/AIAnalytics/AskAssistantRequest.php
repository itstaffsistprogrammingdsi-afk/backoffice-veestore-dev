<?php

namespace App\Http\Requests\AIAnalytics;

use Illuminate\Foundation\Http\FormRequest;

class AskAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Pertanyaan wajib diisi.',
            'prompt.max' => 'Pertanyaan maksimal 1000 karakter.',
        ];
    }
}
