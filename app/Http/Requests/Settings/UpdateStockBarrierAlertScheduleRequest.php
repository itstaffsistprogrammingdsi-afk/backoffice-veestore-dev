<?php

namespace App\Http\Requests\Settings;

use App\Services\StockBarrierEmailSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockBarrierAlertScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $mode = (string) $this->input('mode');

        return [
            'mode' => ['required', Rule::in(StockBarrierEmailSchedule::modes())],
            'time' => [
                'nullable',
                'date_format:H:i',
                Rule::requiredIf($mode !== StockBarrierEmailSchedule::MODE_INSTANT),
            ],
            'day_of_week' => [
                'nullable',
                'integer',
                'between:1,7',
                Rule::requiredIf($mode === StockBarrierEmailSchedule::MODE_WEEKLY),
            ],
            'day_of_month' => [
                'nullable',
                'integer',
                'between:1,28',
                Rule::requiredIf($mode === StockBarrierEmailSchedule::MODE_MONTHLY),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Frekuensi email wajib dipilih.',
            'mode.in' => 'Frekuensi email tidak valid.',
            'time.required' => 'Waktu pengiriman wajib diisi.',
            'time.date_format' => 'Format waktu harus HH:MM.',
            'day_of_week.required' => 'Hari pengiriman wajib dipilih.',
            'day_of_month.required' => 'Tanggal pengiriman wajib diisi.',
        ];
    }
}
