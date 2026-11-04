<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Queries\StoreStockOperationsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreStockOperationsController extends Controller
{
    public function __invoke(Request $request, StoreStockOperationsQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = trim((string) $request->query('store_id', ''));
        $storeId = $storeId !== '' ? $storeId : null;

        return Inertia::render('Operations/StoreStock', [
            ...$query->build($q, $storeId),
            'filters' => [
                'q' => $q,
                'store_id' => $storeId,
            ],
            'urls' => [
                'index' => route('operations.store-stock.index'),
                'store_monitoring' => route('monitoring.stok-percabang'),
                'stock_barriers' => route('monitoring.stok-barier-cabang.index'),
                'store_inbound' => route('operations.store-inbound.index'),
                'store_outbound' => route('operations.store-outbound.index'),
                'outbound_report' => route('monitoring.warehouse-outbound.index'),
            ],
        ]);
    }
}
