<?php

namespace App\Http\Requests\MasterData;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    protected $errorBag = 'createBrand';

    public function authorize(): bool
    {
        return $this->user()?->can('create', Brand::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeName($this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (Brand::query()
                        ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$value])
                        ->exists()) {
                        $fail('Nama brand sudah digunakan.');
                    }
                },
            ],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama brand wajib diisi.',
            'name.string' => 'Nama brand harus berupa teks.',
            'name.max' => 'Nama brand maksimal 255 karakter.',
            'logo.image' => 'File harus berupa gambar.',
            'logo.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran gambar maksimal 2 MB.',
        ];
    }

    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
