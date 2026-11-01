<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePointSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'in_idr_base' => ['required', 'integer', 'min:1'],
            'in_points_per_base' => ['required', 'numeric', 'gt:0'],
            'out_points_base' => ['required', 'numeric', 'gt:0'],
            'out_idr_per_base' => ['required', 'numeric', 'min:1'],
        ];
    }
}
