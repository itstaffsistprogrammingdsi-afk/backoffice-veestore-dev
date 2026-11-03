<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Promo;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DiscountController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-discounts')
            ->only(['index']);

        $this->middleware('permission:create master-data-discounts')
            ->only(['store']);

        $this->middleware('permission:update master-data-discounts')
            ->only(['update']);

        $this->middleware('permission:delete master-data-discounts')
            ->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $promoId = (string) $request->query('promo_id', '');
        $type = (string) $request->query('type', '');
        $active = (string) $request->query('active', '');

        $discounts = Discount::query()
            ->with(['promo:id,name,code'])
            ->when($q !== '', fn ($query) => $query->search($q))
            ->when(
                $promoId !== '',
                fn ($query) => $query->where('promo_id', $promoId)
            )
            ->when(
                in_array($type, ['fixed', 'percentage'], true),
                fn ($query) => $query->where('type', $type)
            )
            ->when(
                $active !== '',
                fn ($query) => $query->where('is_active', (bool) ((int) $active))
            )
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $promos = Promo::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Discounts',
            'eyebrow' => 'Promo / Diskon',
            'description' => 'Kelola diskon dan aturan dasarnya.',
            'searchPlaceholder' => 'Cari diskon...',
            'items' => $discounts,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'promo.name', 'label' => 'Promo'],
                ['key' => 'type', 'label' => 'Tipe'],
                ['key' => 'value', 'label' => 'Value'],
                ['key' => 'valid_from', 'label' => 'Mulai'],
                ['key' => 'valid_until', 'label' => 'Selesai'],
                ['key' => 'is_active', 'label' => 'Aktif'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Diskon', 'required' => true],
                ['key' => 'promo_id', 'label' => 'Promo', 'type' => 'select', 'options' => $promos->map(fn ($promo) => ['value' => $promo->id, 'label' => $promo->name])->values()->all()],
                ['key' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => [['value' => 'fixed', 'label' => 'Fixed'], ['value' => 'percentage', 'label' => 'Percentage']]],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number', 'required' => true],
                ['key' => 'max_discount', 'label' => 'Max Discount', 'type' => 'number'],
                ['key' => 'min_purchase', 'label' => 'Min Purchase', 'type' => 'number'],
                ['key' => 'valid_from', 'label' => 'Valid From', 'type' => 'date'],
                ['key' => 'valid_until', 'label' => 'Valid Until', 'type' => 'date'],
                ['key' => 'usage_limit', 'label' => 'Usage Limit', 'type' => 'number'],
                ['key' => 'is_active', 'label' => 'Aktif', 'type' => 'select', 'options' => [['value' => '1', 'label' => 'Ya'], ['value' => '0', 'label' => 'Tidak']]],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.diskons.index'),
                'store' => route('master-data.diskons.store'),
                'update' => route('master-data.diskons.update', ['discount' => '__ID__']),
                'destroy' => route('master-data.diskons.destroy', ['discount' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            '_from' => 'create',
            'name' => $this->cleanName($request->input('name')),
        ]);

        $rules = $this->rules();

        $rules['name'][] = function (
            string $attribute,
            mixed $value,
            Closure $fail
        ): void {
            if ($this->discountNameExists((string) $value)) {
                $fail('Nama diskon sudah digunakan.');
            }
        };

        $data = $request->validate($rules, $this->messages());

        $data = $this->prepareData($request, $data);

        Discount::create($data);

        return redirect()
            ->route('master-data.diskons.index')
            ->with('success', 'Diskon berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        Discount $discount
    ): RedirectResponse {
        $request->merge([
            '_from' => 'edit',
            'name' => $this->cleanName($request->input('name')),
            'update_url' => route(
                'master-data.diskons.update',
                $discount->getKey()
            ),
        ]);

        $rules = $this->rules();

        $rules['name'][] = function (
            string $attribute,
            mixed $value,
            Closure $fail
        ) use ($discount): void {
            if (
                $this->discountNameExists(
                    (string) $value,
                    (string) $discount->getKey()
                )
            ) {
                $fail('Nama diskon sudah digunakan.');
            }
        };

        $data = $request->validate($rules, $this->messages());

        $data = $this->prepareData(
            $request,
            $data,
            $discount
        );

        $discount->update($data);

        return redirect()
            ->route('master-data.diskons.index')
            ->with('success', 'Diskon berhasil diperbarui.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return redirect()
            ->route('master-data.diskons.index')
            ->with('success', 'Diskon berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'promo_id' => [
                'nullable',
                'uuid',
                'exists:promos,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],
            'value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'max_discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'min_purchase' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'valid_from' => [
                'nullable',
                'date',
            ],
            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:valid_from',
            ],
            'usage_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'priority' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'combinable' => [
                'nullable',
                'boolean',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama diskon wajib diisi.',
            'name.string' => 'Nama diskon harus berupa teks.',
            'name.max' => 'Nama diskon maksimal 255 karakter.',
            'promo_id.uuid' => 'Format promo tidak valid.',
            'promo_id.exists' => 'Promo yang dipilih tidak ditemukan.',
            'type.required' => 'Tipe diskon wajib dipilih.',
            'type.in' => 'Tipe diskon tidak valid.',
            'value.required' => 'Nilai diskon wajib diisi.',
            'value.numeric' => 'Nilai diskon harus berupa angka.',
            'value.min' => 'Nilai diskon tidak boleh negatif.',
            'value.max' => 'Diskon persentase maksimal 100%.',
            'valid_until.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    private function prepareData(
        Request $request,
        array $data,
        ?Discount $discount = null
    ): array {
        if (($data['type'] ?? null) === 'percentage') {
            validator(
                ['value' => $data['value']],
                ['value' => ['numeric', 'max:100']],
                ['value.max' => 'Diskon persentase maksimal 100%.']
            )->validate();
        }

        $data['name'] = $this->cleanName($data['name']);
        $data['combinable'] = $request->boolean('combinable');
        $data['is_active'] = $request->boolean('is_active');

        if (($data['type'] ?? null) === 'fixed') {
            $data['max_discount'] = null;
        }

        $data['priority'] = isset($data['priority'])
            ? (int) $data['priority']
            : (int) ($discount?->priority ?? 100);

        $data['min_purchase'] = $data['min_purchase'] ?? 0;

        foreach (['valid_from', 'valid_until'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Carbon::parse($data[$field]);
            }
        }

        return $data;
    }

    private function cleanName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    private function discountNameExists(
        string $name,
        ?string $ignoreId = null
    ): bool {
        $normalizedName = mb_strtoupper(
            $this->cleanName($name),
            'UTF-8'
        );

        return Discount::query()
            ->when(
                $ignoreId !== null,
                fn ($query) => $query->whereKeyNot($ignoreId)
            )
            ->whereRaw(
                "UPPER(REGEXP_REPLACE(TRIM(name), '\\s+', ' ', 'g')) = ?",
                [$normalizedName]
            )
            ->exists();
    }
}
