<?php

namespace App\Http\Controllers\Operations;

use App\Domain\WarehouseOperations\Queries\InboundOperationsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InboundController extends Controller
{
    public function __invoke(Request $request, InboundOperationsQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $warehouseId = trim((string) $request->query('warehouse_id', ''));
        $warehouseId = $warehouseId !== '' ? $warehouseId : null;
        $status = trim((string) $request->query('status', ''));
        $status = in_array($status, ['pending', 'received'], true) ? $status : null;
        $supplierId = trim((string) $request->query('supplier_id', ''));
        $supplierId = $supplierId !== '' ? $supplierId : null;

        return Inertia::render('Operations/Inbound', [
            ...$query->build($q, $warehouseId, $status, $supplierId),
            'warehouses' => DB::table('warehouses')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']),
            'suppliers' => DB::table('suppliers')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'q' => $q,
                'warehouse_id' => $warehouseId,
                'status' => $status,
                'supplier_id' => $supplierId,
            ],
            'urls' => [
                'index' => route('operations.inbound.index'),
                'stock' => route('operations.stock.index'),
                'outbound' => route('operations.outbound.index'),
                'monitoring' => route('monitoring.warehouse-inbound'),
            ],
        ]);
    }
}
