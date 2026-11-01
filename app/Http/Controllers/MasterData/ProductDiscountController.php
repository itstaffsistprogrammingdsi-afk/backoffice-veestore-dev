<?php

// app/Http/Controllers/MasterData/ProductDiscountController.php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductDiscountController extends Controller
{
    public function __construct()
    {
        // Spatie permissions
        $this->middleware('permission:view master-data-product-discounts')->only(['index']);
        $this->middleware('permission:create master-data-product-discounts')->only(['store']);
        $this->middleware('permission:update master-data-product-discounts')->only(['update']);
        $this->middleware('permission:delete master-data-product-discounts')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));
        $discountId = $request->input('discount_id');
        $storeParam = $request->input('store_id');
        $filters = [
            'q' => $q,
            'discount_id' => $discountId,
            'store_id' => $storeParam,
        ];

        // Normalisasi store_id -> array UUID
        $storeIds = [];
        if (is_array($storeParam)) {
            $storeIds = array_values(array_filter($storeParam, fn ($v) => ! empty($v)));
        } elseif (! empty($storeParam)) {
            $storeIds = [$storeParam];
        }

        // Siapkan literal array Postgres utk whereRaw (contoh: '{uuid1,uuid2}')
        $pgArray = null;
        if (! empty($storeIds)) {
            $storeIds = array_filter($storeIds, fn ($id) => preg_match('/^[0-9a-fA-F-]{36}$/', $id));
            if (! empty($storeIds)) {
                $pgArray = '{'.implode(',', $storeIds).'}';
            }
        }

        // master data untuk form
        // ambil product_id yang sudah dipakai
        $usedProductIds = DiscountProduct::pluck('product_id')->toArray();

        // hanya tampilkan produk yang belum dipakai
        $products = Product::with('category')
            ->whereNotIn('id', $usedProductIds)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'code', 'category_id']);

        $categories = ProductCategory::whereHas('products')
            ->orderBy('name')
            ->get(['id', 'name']);
        $discounts = Discount::orderBy('priority', 'desc')->orderBy('name')->limit(200)
            ->get(['id', 'name', 'type', 'value', 'is_active', 'valid_from', 'valid_until']);
        $stores = Store::orderBy('name')->get(['id', 'name', 'code']);

        // enum applied_to (kalau enum ada). Jika gagal, biarkan view fallback.
        try {
            $appliedToOptions = collect(DB::select('SELECT unnest(enum_range(NULL::discount_product_applied_to)) AS v'))
                ->pluck('v')->all();
        } catch (\Throwable $e) {
            $appliedToOptions = [];
        }

        // Listing
        $rows = DiscountProduct::query()
            ->join('products', 'products.id', '=', 'discount_products.product_id')
            ->join('discounts', 'discounts.id', '=', 'discount_products.discount_id')
            // stores.id = ANY(discount_products.store_id)
            ->leftJoin('stores', function ($join) {
                $join->whereRaw('stores.id = ANY(discount_products.store_id)');
            })
            ->when($discountId, fn ($qq) => $qq->where('discounts.id', $discountId))
            // filter store pakai overlap &&
            ->when($pgArray, fn ($qq) => $qq->whereRaw('discount_products.store_id && ?::uuid[]', [$pgArray]))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('products.name', 'ilike', "%{$q}%")
                        ->orWhere('products.code', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('discounts.priority', 'desc')
            ->orderBy('products.name')
            ->select([
                'discount_products.discount_id',
                'discount_products.product_id',
                'discount_products.applied_to',
                'discount_products.store_id', // array
                DB::raw('stores.id as store_id_single'), // untuk aksi
                'products.name  as product_name',
                'products.code  as product_code',
                'discounts.name as discount_name',
                'discounts.type as discount_type',
                'discounts.value as discount_value',
                'discounts.is_active',
                DB::raw("COALESCE(stores.name,'-') as store_name"),
                DB::raw("COALESCE(stores.code,'') as store_code"),
            ])
            ->paginate(15)
            ->appends($request->query());

        return Inertia::render('MasterData/ProductDiscounts', [
            'products' => $products,
            'discounts' => $discounts,
            'stores' => $stores,
            'rows' => $rows,
            'filters' => $filters,
            'appliedToOptions' => $appliedToOptions,
            'categories' => $categories,
            'urls' => [
                'index' => route('master-data.products-diskons.index'),
                'store' => route('master-data.products-diskons.store'),
                'update' => route('master-data.products-diskons.update'),
                'destroy' => route('master-data.products-diskons.destroy'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $isAllProducts = $request->boolean('all_products');

        // Ambil enum applied_to (fallback jika enum tidak ada)
        try {
            $appliedToOptions = collect(DB::select('SELECT unnest(enum_range(NULL::discount_product_applied_to)) AS v'))
                ->pluck('v')->all();
        } catch (\Throwable $e) {
            $appliedToOptions = ['all', 'store'];
        }

        $data = $request->validate(
            [
                'discount_id' => ['required', 'uuid', 'exists:discounts,id'],
                'all_products' => ['nullable', 'boolean'],
                'product_id' => [Rule::requiredIf(! $isAllProducts), 'array'],
                'product_id.*' => ['uuid', 'exists:products,id'],
                'applied_to' => ['required', Rule::in($appliedToOptions)],
                'store_id' => ['nullable', 'uuid', 'exists:stores,id'],
            ],
            [],
            [
                'discount_id' => 'Diskon',
                'product_id' => 'Produk',
                'applied_to' => 'Berlaku Untuk',
                'store_id' => 'Cabang',
            ]
        );

        // Normalisasi scope
        $appliedLower = strtolower($data['applied_to']);

        if ($appliedLower === 'store' && empty($data['store_id'])) {
            return back()->withErrors([
                'store_id' => 'Cabang wajib diisi jika Berlaku Untuk = store',
            ])->withInput();
        }

        if ($appliedLower !== 'store') {
            $data['store_id'] = null;
        }

        // format store array postgres
        $storeArraySql = is_null($data['store_id'])
            ? 'NULL::uuid[]'
            : "ARRAY['".$data['store_id']."']::uuid[]";

        $insertCount = 0;

        // Saat "Pilih Semua Produk" dipilih, ID tidak dikirim ke browser satu per
        // satu. Ambil langsung dari database agar formulir tetap ringan dan tidak
        // dibatasi oleh daftar produk yang ditampilkan di Select2.
        $productIds = $isAllProducts
            ? Product::query()->select('id')->orderBy('id')->cursor()
            : collect($data['product_id'] ?? [])->filter()->unique();

        foreach ($productIds as $product) {
            $productId = $isAllProducts ? $product->id : $product;

            // cek duplikat
            $dup = DiscountProduct::query()
                ->where('discount_id', $data['discount_id'])
                ->where('product_id', $productId)
                ->when(
                    is_null($data['store_id']),
                    fn ($q) => $q->whereNull('store_id'),
                    fn ($q) => $q->whereRaw('? = ANY(store_id)', [$data['store_id']])
                )
                ->exists();

            if ($dup) {
                continue;
            }

            DB::table('discount_products')->insert([
                'discount_id' => $data['discount_id'],
                'product_id' => $productId,
                'applied_to' => $data['applied_to'],
                'store_id' => DB::raw($storeArraySql),
            ]);

            $insertCount++;
        }

        if ($insertCount === 0) {
            return back()->withErrors([
                'product_id' => 'Semua produk yang dipilih sudah terdaftar.',
            ])->withInput();
        }

        return redirect()
            ->route('master-data.products-diskons.index')
            ->with('success', "{$insertCount} produk berhasil ditambahkan ke diskon.");
    }

    public function update(Request $request)
    {
        // Ambil enum applied_to (fallback jika enum tidak ada)
        try {
            $appliedToOptions = collect(DB::select('SELECT unnest(enum_range(NULL::discount_product_applied_to)) AS v'))
                ->pluck('v')->all();
        } catch (\Throwable $e) {
            $appliedToOptions = ['all', 'store'];
        }

        // Validasi: kunci asli (original_*) + nilai baru
        $data = $request->validate(
            [
                // kunci baris lama
                'original_discount_id' => ['required', 'uuid'],
                'original_product_id' => ['required', 'uuid'],
                'original_store_id' => ['nullable', 'uuid'],

                // nilai baru
                'discount_id' => ['required', 'uuid', 'exists:discounts,id'],
                'product_id' => ['required', 'uuid', 'exists:products,id'],
                'applied_to' => ['required', Rule::in($appliedToOptions)],
                'store_id' => ['nullable', 'uuid', 'exists:stores,id'],
            ],
            [],
            [
                'discount_id' => 'Diskon',
                'product_id' => 'Produk',
                'applied_to' => 'Berlaku Untuk',
                'store_id' => 'Cabang',
                'original_discount_id' => 'Diskon (asal)',
                'original_product_id' => 'Produk (asal)',
                'original_store_id' => 'Cabang (asal)',
            ]
        );

        // Normalisasi scope target
        $appliedLower = strtolower($data['applied_to']);
        if ($appliedLower === 'store' && empty($data['store_id'])) {
            return back()->withErrors(['store_id' => 'Cabang wajib diisi jika Berlaku Untuk = store'])->withInput();
        }
        if ($appliedLower !== 'store') {
            $data['store_id'] = null;
        }

        // ✅ FIX: Cek duplikat target dengan logic yang lebih jelas
        $dupQuery = DiscountProduct::query()
            ->where('discount_id', $data['discount_id'])
            ->where('product_id', $data['product_id']);

        // Filter berdasarkan store_id target
        if (is_null($data['store_id'])) {
            $dupQuery->whereNull('store_id');
        } else {
            $dupQuery->whereRaw('? = ANY(store_id)', [$data['store_id']]);
        }

        // ✅ Exclude baris original (yang sedang di-edit)
        // Gunakan subquery untuk identifikasi baris original
        $dupQuery->where(function ($q) use ($data) {
            // Baris dianggap BEDA jika salah satu key berbeda
            $q->where('discount_id', '!=', $data['original_discount_id'])
                ->orWhere('product_id', '!=', $data['original_product_id']);

            // Atau store_id berbeda (handle null dengan benar)
            if (! empty($data['original_store_id'])) {
                $q->orWhereRaw('NOT (? = ANY(store_id))', [$data['original_store_id']]);
            } else {
                $q->orWhereNotNull('store_id');
            }
        });

        $dup = $dupQuery->exists();

        if ($dup) {
            return back()->withErrors([
                'product_id' => 'Perubahan bertabrakan dengan relasi yang sudah ada (duplikat).',
            ])->withInput();
        }

        // ✅ Siapkan query untuk menemukan baris lama
        $query = DiscountProduct::query()
            ->where('discount_id', $data['original_discount_id'])
            ->where('product_id', $data['original_product_id']);

        if (! empty($data['original_store_id'])) {
            $query->whereRaw('? = ANY(store_id)', [$data['original_store_id']]);
        } else {
            $query->whereNull('store_id');
        }

        // ✅ Update dengan nilai baru
        $storeArraySql = is_null($data['store_id'])
            ? 'NULL::uuid[]'
            : "ARRAY['".$data['store_id']."']::uuid[]";

        $affected = $query->update([
            'discount_id' => $data['discount_id'],
            'product_id' => $data['product_id'],
            'applied_to' => $data['applied_to'],
            'store_id' => DB::raw($storeArraySql),
        ]);

        if ($affected < 1) {
            return back()->withErrors(['product_id' => 'Data lama tidak ditemukan / sudah berubah.'])->withInput();
        }

        return redirect()->route('master-data.products-diskons.index')
            ->with('success', 'Produk diskon berhasil diupdate.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'discount_id' => ['required', 'uuid'],
            'product_id' => ['required', 'uuid'],
            'store_id' => ['nullable', 'uuid'],
        ]);

        $query = DiscountProduct::query()
            ->where('discount_id', $request->discount_id)
            ->where('product_id', $request->product_id);

        if ($request->filled('store_id')) {
            $query->whereRaw('? = ANY(store_id)', [$request->store_id]);
        } else {
            $query->whereNull('store_id');
        }

        $query->delete();

        return back()->with('success', 'Produk diskon dihapus.');
    }
}
