<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'max:'.config('assistant.limits.max_message_length', 2000),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Pertanyaan wajib diisi.',
            'message.max' => 'Pertanyaan terlalu panjang.',
        ];
    }
}
