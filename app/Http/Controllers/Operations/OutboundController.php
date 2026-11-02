<?php

namespace App\Http\Controllers\Operations;

use App\Domain\WarehouseOperations\Queries\OutboundOperationsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OutboundController extends Controller
{
    public function __invoke(Request $request, OutboundOperationsQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $warehouseId = trim((string) $request->query('warehouse_id', ''));
        $warehouseId = $warehouseId !== '' ? $warehouseId : null;
        $status = trim((string) $request->query('status', ''));
        $status = in_array($status, ['qc_pending', 'release_pending', 'missing_awb', 'completed'], true) ? $status : null;
        $channel = trim((string) $request->query('channel', ''));
        $channel = in_array($channel, ['ecommerce', 'store'], true) ? $channel : null;
        $from = trim((string) $request->query('from', ''));
        $from = $from !== '' ? $from : null;
        $to = trim((string) $request->query('to', ''));
        $to = $to !== '' ? $to : null;

        return Inertia::render('Operations/Outbound', [
            ...$query->build($q, $warehouseId, $status, $channel, $from, $to),
            'warehouses' => DB::table('warehouses')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'warehouse_id' => $warehouseId,
                'status' => $status,
                'channel' => $channel,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('operations.outbound.index'),
                'stock' => route('operations.stock.index'),
                'inbound' => route('operations.inbound.index'),
                'monitoring' => route('monitoring.warehouse-outbound.index'),
            ],
        ]);
    }
}
