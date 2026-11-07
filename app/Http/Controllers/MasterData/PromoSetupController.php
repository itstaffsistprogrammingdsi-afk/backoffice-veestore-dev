<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Store;
use App\Models\Voucher;
use App\Services\VoucherCodeGenerator;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Membuat satu alur input untuk Promo, aturan Diskon, produk sasaran, dan voucher.
 *
 * Data tetap disimpan ke tabel master yang sekarang, sehingga halaman lama
 * dan proses checkout yang sudah ada tetap kompatibel.
 */
class PromoSetupController extends Controller
{
    private const VOUCHER_CONTEXTS = [
        'ecommerce_checkout',
        'ecommerce_shipping',
        'store_checkout',
        'store_customer_return',
    ];

    public function __construct()
    {
        $this->middleware('permission:view master-data-promos')->only('index');

        // Satu klik simpan membuat tiga jenis data, jadi semua izin create
        // terkait tetap wajib dimiliki pengguna.
        $this->middleware('permission:create master-data-promos')->only('store');
        $this->middleware('permission:create master-data-discounts')->only('store');
        $this->middleware('permission:create master-data-product-discounts')->only('store');
    }

    public function index(Request $request)
    {
        return Inertia::render('MasterData/PromoSetup', [
            'stores' => Store::query()->orderBy('name')->get(['id', 'name', 'code']),
            'products' => Product::query()->orderBy('name')->limit(500)->get(['id', 'name', 'code']),
            'recentPromos' => Promo::query()
                ->orderByDesc('start_date')
                ->limit(10)
                ->get(['id', 'name', 'code', 'start_date', 'end_date', 'all_store']),
            'voucherContexts' => self::VOUCHER_CONTEXTS,
            'canCreateVoucher' => (bool) $request->user()?->can('create master-data-vouchers'),
            'urls' => [
                'index' => route('master-data.promo-setup.index'),
                'store' => route('master-data.promo-setup.store'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'promo_name' => $this->normalizeName($request->input('promo_name')),
            'promo_code' => $this->normalizeCode($request->input('promo_code')),
            'discount_name' => $this->cleanName(
                $request->input('discount_name') ?: $request->input('promo_name')
            ),
        ]);

        $allStores = $request->boolean('all_stores');
        $allProducts = $request->boolean('all_products');
        $createVoucher = $request->boolean('create_voucher');

        if ($createVoucher && ! $request->user()?->can('create master-data-vouchers')) {
            return back()->withErrors([
                'create_voucher' => 'Anda tidak memiliki izin membuat voucher.',
            ])->withInput();
        }

        $data = $request->validate([
            'promo_name' => [
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->promoNameExists((string) $value)) {
                        $fail('Nama promo sudah digunakan.');
                    }
                },
            ],
            'promo_code' => [
                'required', 'string', 'max:100',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->promoCodeExists((string) $value)) {
                        $fail('Kode promo sudah digunakan.');
                    }
                },
            ],
            'promo_description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'all_stores' => ['nullable', 'boolean'],
            'store_ids' => [Rule::requiredIf(! $allStores), 'array'],
            'store_ids.*' => ['uuid', 'exists:stores,id'],
            'discount_name' => [
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->discountNameExists((string) $value)) {
                        $fail('Nama diskon sudah digunakan.');
                    }
                },
            ],
            'discount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'all_products' => ['nullable', 'boolean'],
            'product_ids' => [Rule::requiredIf(! $allProducts), 'array'],
            'product_ids.*' => ['uuid', 'exists:products,id'],
            'create_voucher' => ['nullable', 'boolean'],
            'voucher_name' => ['nullable', 'string', 'max:150'],
            'voucher_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'voucher_value' => ['nullable', 'numeric', 'min:0'],
            'voucher_min_purchase' => ['nullable', 'numeric', 'min:0'],
            'voucher_max_discount' => ['nullable', 'numeric', 'min:0'],
            'voucher_usage_limit' => [
                Rule::requiredIf($createVoucher && $request->input('voucher_distribution_type') === 'unique_code'),
                'nullable', 'integer', 'min:1',
            ],
            'voucher_distribution_type' => [
                Rule::requiredIf($createVoucher),
                'nullable',
                Rule::in(['public', 'unique_code']),
            ],
            'voucher_contexts' => [Rule::requiredIf($createVoucher), 'array', 'min:1'],
            'voucher_contexts.*' => ['string', 'distinct', Rule::in(self::VOUCHER_CONTEXTS)],
            'voucher_is_active' => ['nullable', 'boolean'],
        ], [
            'store_ids.required' => 'Pilih minimal satu cabang atau aktifkan Semua Cabang.',
            'product_ids.required' => 'Pilih minimal satu produk atau aktifkan Semua Produk.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'voucher_usage_limit.required' => 'Batas pemakaian wajib diisi untuk voucher kode unik.',
            'voucher_contexts.required' => 'Pilih minimal satu konteks voucher.',
            'voucher_contexts.*.in' => 'Konteks voucher tidak valid.',
        ]);

        if ($data['discount_type'] === 'percentage' && (float) $data['discount_value'] > 100) {
            return back()->withErrors([
                'discount_value' => 'Diskon persentase maksimal 100%.',
            ])->withInput();
        }

        if ($createVoucher) {
            $voucherType = $data['voucher_type'] ?? $data['discount_type'];
            $voucherValue = $data['voucher_value'] ?? $data['discount_value'];

            if ($voucherType === 'percentage' && (float) $voucherValue > 100) {
                return back()->withErrors([
                    'voucher_value' => 'Voucher persentase maksimal 100%.',
                ])->withInput();
            }
        }

        $storeIds = collect($data['store_ids'] ?? [])->filter()->unique()->values()->all();
        $productIds = $allProducts
            ? Product::query()->select('id')->orderBy('id')->cursor()
            : collect($data['product_ids'] ?? [])->filter()->unique();

        $productCount = 0;
        $voucherCreated = false;
        $voucherIsActive = $request->boolean('voucher_is_active', true);

        DB::transaction(function () use (
            $data,
            $allStores,
            $storeIds,
            $productIds,
            $createVoucher,
            $voucherIsActive,
            &$productCount,
            &$voucherCreated
        ): void {
            $promo = Promo::create([
                'name' => $data['promo_name'],
                'code' => $data['promo_code'],
                'description' => trim((string) ($data['promo_description'] ?? '')),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'all_store' => $allStores,
                'store_id' => $allStores ? null : $storeIds,
            ]);

            $discount = Discount::create([
                'promo_id' => $promo->getKey(),
                'name' => $data['discount_name'],
                'description' => trim((string) ($data['promo_description'] ?? '')),
                'type' => $data['discount_type'],
                'value' => $data['discount_value'],
                'max_discount' => $data['discount_type'] === 'percentage'
                    ? ($data['max_discount'] ?? null)
                    : null,
                'min_purchase' => $data['min_purchase'] ?? 0,
                'valid_from' => $data['start_date'],
                'valid_until' => $data['end_date'],
                'priority' => 100,
                'combinable' => false,
                'is_active' => true,
            ]);

            // Tabel lama menyimpan cabang sebagai uuid[]. Untuk promo semua
            // cabang nilainya null; untuk cabang terpilih, satu mapping dapat
            // menyimpan semua cabang tersebut tanpa input berulang.
            $storeArraySql = $allStores
                ? 'NULL::uuid[]'
                : "'{".implode(',', $storeIds)."}'::uuid[]";

            foreach ($productIds as $product) {
                $productId = is_object($product) ? $product->id : $product;

                DB::table('discount_products')->insert([
                    'discount_id' => $discount->getKey(),
                    'product_id' => $productId,
                    'applied_to' => $allStores ? 'global' : 'store',
                    'store_id' => DB::raw($storeArraySql),
                ]);

                $productCount++;
            }

            if ($createVoucher) {
                $this->createVoucher($data, $voucherIsActive);
                $voucherCreated = true;
            }
        });

        $message = "Promo, diskon, dan {$productCount} produk berhasil disimpan.";

        if ($voucherCreated) {
            $message = "Promo, diskon, {$productCount} produk, dan voucher berhasil disimpan.";
        }

        return redirect()
            ->route('master-data.promo-setup.index')
            ->with('success', $message);
    }

    private function createVoucher(array $data, bool $isActive): Voucher
    {
        $generator = app(VoucherCodeGenerator::class);
        $type = $data['voucher_type'] ?? $data['discount_type'];
        $value = $data['voucher_value'] ?? $data['discount_value'];
        $distributionType = $data['voucher_distribution_type'] ?? 'public';
        $contexts = array_values(array_unique($data['voucher_contexts'] ?? ['store_checkout']));
        $hasContextTable = Schema::hasTable('voucher_context');

        $voucher = Voucher::create([
            'code' => $generator->generateMasterCode(),
            'name' => ($data['voucher_name'] ?? '') ?: $data['promo_name'],
            'description' => trim((string) ($data['promo_description'] ?? '')),
            'type' => $type,
            'value' => $value,
            'max_discount' => $type === 'percentage'
                ? ($data['voucher_max_discount'] ?? $data['max_discount'] ?? null)
                : null,
            'min_purchase' => $data['voucher_min_purchase'] ?? $data['min_purchase'] ?? 0,
            'valid_from' => $data['start_date'],
            'valid_until' => $data['end_date'],
            'usage_limit' => $data['voucher_usage_limit'] ?? null,
            'usage_count' => 0,
            'is_active' => $isActive,
            'context' => $hasContextTable ? null : ($contexts[0] ?? null),
            'distribution_type' => $distributionType,
        ]);

        if ($distributionType === 'unique_code') {
            $generator->generateCodes($voucher, (int) $data['voucher_usage_limit']);
        }

        if ($hasContextTable) {
            $now = now();

            DB::table('voucher_context')->insert(array_map(fn (string $context) => [
                'voucher_id' => $voucher->getKey(),
                'context' => $context,
                'created_at' => $now,
                'updated_at' => $now,
            ], $contexts));
        }

        return $voucher;
    }

    private function normalizeName(?string $value): string
    {
        return mb_strtoupper($this->cleanName($value), 'UTF-8');
    }

    private function normalizeCode(?string $value): string
    {
        return mb_strtoupper(
            preg_replace('/\s+/u', '', trim((string) $value)) ?? '',
            'UTF-8'
        );
    }

    private function cleanName(?string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
    }

    private function promoNameExists(string $name): bool
    {
        return Promo::query()
            ->whereRaw("UPPER(REGEXP_REPLACE(TRIM(name), '\\s+', ' ', 'g')) = ?", [$name])
            ->exists();
    }

    private function promoCodeExists(string $code): bool
    {
        return Promo::query()->whereRaw('UPPER(TRIM(code)) = ?', [$code])->exists();
    }

    private function discountNameExists(string $name): bool
    {
        return Discount::query()
            ->whereRaw("UPPER(REGEXP_REPLACE(TRIM(name), '\\s+', ' ', 'g')) = ?", [mb_strtoupper($name, 'UTF-8')])
            ->exists();
    }
}
