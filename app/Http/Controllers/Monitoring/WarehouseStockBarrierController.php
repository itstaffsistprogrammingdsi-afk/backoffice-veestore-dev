<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStockBarrier;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseStockBarrierController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'warehouse_id' => trim((string) $request->get('warehouse_id', '')),
            'q'            => trim((string) $request->get('q', '')),
        ];

        $barriers = WarehouseStockBarrier::query()
            ->with(['warehouse:id,name', 'product:id,code,name'])
            ->when($filters['warehouse_id'] !== '', fn ($qq) => $qq->where('warehouse_id', $filters['warehouse_id']))
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

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);
        $products   = Product::orderBy('name')->limit(200)->get(['id', 'code', 'name']);

        return view('content.monitoring.warehouse-stock-barriers.index', [
            'barriers'   => $barriers,
            'warehouses' => $warehouses,
            'products'   => $products,
            'filters'    => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        WarehouseStockBarrier::create($data);

        return redirect()
            ->route('monitoring.stok-barier-gudang.index')
            ->with('success', 'Stok barier warehouse dibuat.');
    }

    public function update(Request $request, WarehouseStockBarrier $stok_barier_gudang)
    {
        $data = $this->validated($request, $stok_barier_gudang->id);
        $stok_barier_gudang->update($data);

        return redirect()
            ->route('monitoring.stok-barier-gudang.index')
            ->with('success', 'Stok barier warehouse diupdate.');
    }

    public function destroy(WarehouseStockBarrier $stok_barier_gudang)
    {
        $stok_barier_gudang->delete();

        return redirect()
            ->route('monitoring.stok-barier-gudang.index')
            ->with('success', 'Stok barier warehouse dihapus.');
    }

    protected function validated(Request $request, $ignoreId = null): array
    {
        $uniquePerWarehouse = Rule::unique('warehouse_stock_barriers', 'product_id')
            ->where(fn ($q) => $q->where('warehouse_id', $request->input('warehouse_id')));

        if ($ignoreId) {
            $uniquePerWarehouse = $uniquePerWarehouse->ignore($ignoreId, 'id');
        }

        return $request->validate(
            [
                'warehouse_id'         => ['required', 'uuid', 'exists:warehouses,id'],
                'product_id'           => ['required', 'uuid', 'exists:products,id', $uniquePerWarehouse],
                'stock_barrier'        => ['required', 'integer', 'min:0'],
                'tolerance_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            ],
            [
                'product_id.unique' => 'Produk sudah memiliki Stok Barier di warehouse yang dipilih.',
            ],
            [
                'warehouse_id'         => 'Warehouse',
                'product_id'           => 'Produk',
                'stock_barrier'        => 'Stok barier',
                'tolerance_percentage' => 'Toleransi (%)',
            ]
        );
    }
}