<?php

namespace App\Http\Controllers\Operations;

use App\Domain\OperationalStock\Queries\OperationalStockQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperationalStockController extends Controller
{
    public function __invoke(Request $request, OperationalStockQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $warehouseId = trim((string) $request->query('warehouse_id', ''));
        $warehouseId = $warehouseId !== '' ? $warehouseId : null;

        return Inertia::render('Operations/Stock', [
            ...$query->build($q, $warehouseId),
            'filters' => [
                'q' => $q,
                'warehouse_id' => $warehouseId,
            ],
            'urls' => [
                'index' => route('operations.stock.index'),
                'store_requests' => route('monitoring.store-order-requests.index'),
                'ecommerce_orders' => route('ecommerce.orders.index'),
                'stock_wms' => route('monitoring.stok-wms'),
                'stock_barriers' => route('monitoring.stok-barier-gudang.index'),
            ],
        ]);
    }
}
