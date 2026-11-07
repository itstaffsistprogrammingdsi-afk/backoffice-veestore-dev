<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BirthdayGreetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'uuid', 'exists:customers,id'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'voucher_id' => [
                'nullable',
                'uuid',
                Rule::exists('vouchers', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_ids.required' => 'Pilih minimal satu pelanggan penerima.',
            'customer_ids.array' => 'Daftar pelanggan penerima tidak valid.',
            'customer_ids.min' => 'Pilih minimal satu pelanggan penerima.',
            'customer_ids.*.uuid' => 'ID pelanggan tidak valid.',
            'customer_ids.*.exists' => 'Pelanggan penerima tidak ditemukan.',
            'subject.required' => 'Subjek email wajib diisi.',
            'subject.max' => 'Subjek email maksimal 150 karakter.',
            'message.required' => 'Isi sapaan wajib diisi.',
            'message.max' => 'Isi sapaan maksimal 5000 karakter.',
            'voucher_id.uuid' => 'Voucher yang dipilih tidak valid.',
            'voucher_id.exists' => 'Voucher yang dipilih tidak ditemukan.',
        ];
    }
}
