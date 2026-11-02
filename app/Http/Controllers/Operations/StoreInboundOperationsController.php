<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Queries\StoreInboundOperationsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StoreInboundOperationsController extends Controller
{
    public function __invoke(Request $request, StoreInboundOperationsQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = trim((string) $request->query('store_id', ''));
        $storeId = $storeId !== '' ? $storeId : null;
        $warehouseId = trim((string) $request->query('warehouse_id', ''));
        $warehouseId = $warehouseId !== '' ? $warehouseId : null;
        $status = trim((string) $request->query('status', ''));
        $status = in_array($status, ['pending', 'partial_passed', 'all_passed', 'rejected'], true) ? $status : null;

        return Inertia::render('Operations/StoreInbound', [
            ...$query->build($q, $storeId, $warehouseId, $status),
            'stores' => DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']),
            'warehouses' => DB::table('warehouses')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'store_id' => $storeId,
                'warehouse_id' => $warehouseId,
                'status' => $status,
            ],
            'urls' => [
                'index' => route('operations.store-inbound.index'),
                'stock' => route('operations.store-stock.index'),
                'outbound' => route('operations.store-outbound.index'),
                'monitoring' => route('monitoring.store-inbound.index'),
            ],
        ]);
    }
}
