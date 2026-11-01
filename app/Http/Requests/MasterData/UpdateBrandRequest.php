<?php

namespace App\Http\Requests\MasterData;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    protected $errorBag = 'editBrand';

    public function authorize(): bool
    {
        $brand = $this->route('brand');

        return $brand instanceof Brand
            && ($this->user()?->can('update', $brand) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeName($this->input('name')),
        ]);
    }

    public function rules(): array
    {
        $brand = $this->route('brand');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($brand) {
                    $query = Brand::query()
                        ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$value]);

                    if ($brand instanceof Brand) {
                        $query->whereKeyNot($brand->getKey());
                    }

                    if ($query->exists()) {
                        $fail('Nama brand sudah digunakan.');
                    }
                },
            ],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'id' => ['nullable', 'uuid'],
            'update_url' => ['nullable', 'string'],
            'current_logo_url' => ['nullable', 'string'],
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
