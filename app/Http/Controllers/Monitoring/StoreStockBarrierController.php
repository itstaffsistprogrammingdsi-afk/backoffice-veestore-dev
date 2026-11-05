<?php
// app/Http/Controllers/Monitoring/StoreStockBarrierController.php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\StoreStockBarrier;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreStockBarrierController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'store_id' => trim((string) $request->get('store_id', '')),
            'q'        => trim((string) $request->get('q', '')),
        ];

        $barriers = StoreStockBarrier::query()
            ->with(['store:id,name', 'product:id,code,name'])
            ->when($filters['store_id'] !== '', fn ($qq) => $qq->where('store_id', $filters['store_id']))
            ->when($filters['q'] !== '', function ($qq) use ($filters) {
                $q = $filters['q'];

                $qq->whereHas('product', function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                      ->orWhere('code', 'ilike', "%{$q}%");
                });
            })
            ->orderByRaw('COALESCE(updated_at, created_at) DESC')
            ->paginate(10)
            ->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->limit(200)->get(['id', 'code', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Store Stock Barriers',
            'description' => 'Laporan monitoring.',
            'data' => [
            'barriers' => $barriers,
            'stores'   => $stores,
            'products' => $products,
            'filters'  => $filters,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        StoreStockBarrier::create($data);

        return redirect()
            ->route('monitoring.stok-barier-cabang.index')
            ->with('success', 'Stok barier dibuat.');
    }

    public function update(Request $request, StoreStockBarrier $barrier)
    {
        $data = $this->validated($request, $barrier->id);

        $barrier->update($data);

        return redirect()
            ->route('monitoring.stok-barier-cabang.index')
            ->with('success', 'Stok barier berhasil diperbarui.');
    }

    public function destroy(StoreStockBarrier $barrier)
    {
        $barrier->delete();

        return redirect()
            ->route('monitoring.stok-barier-cabang.index')
            ->with('success', 'Stok barier dihapus.');
    }

    protected function validated(Request $request, $ignoreId = null): array
    {
        $uniqueProductPerStore = Rule::unique('store_stock_barriers', 'product_id')
            ->where(fn ($q) => $q->where('store_id', $request->input('store_id')));

        if ($ignoreId) {
            $uniqueProductPerStore = $uniqueProductPerStore->ignore($ignoreId, 'id');
        }

        return $request->validate(
            [
                'store_id'             => ['required', 'uuid', 'exists:stores,id'],
                'product_id'           => ['required', 'uuid', 'exists:products,id', $uniqueProductPerStore],
                'stock_barrier'        => ['required', 'integer', 'min:0'],
                'tolerance_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ],
            [
                'product_id.unique' => 'Produk sudah memiliki Stok Barier di cabang yang dipilih.',
            ],
            [
                'store_id'             => 'Cabang',
                'product_id'           => 'Produk',
                'stock_barrier'        => 'Stok barier',
                'tolerance_percentage' => 'Toleransi (%)',
            ]
        );
    }
}