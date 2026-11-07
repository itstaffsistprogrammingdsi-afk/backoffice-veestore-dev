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
        $movement = trim((string) $request->query('movement', ''));
        $movement = in_array($movement, ['slow', 'fast'], true) ? $movement : null;

        return Inertia::render('Operations/Stock', [
            ...$query->build($q, $warehouseId, $movement),
            'filters' => [
                'q' => $q,
                'warehouse_id' => $warehouseId,
                'movement' => $movement,
            ],
            'urls' => [
                'index' => route('operations.stock.index'),
                'store_requests' => route('monitoring.store-order-requests.index'),
                'ecommerce_orders' => route('ecommerce.orders.index'),
                'stock_wms' => route('monitoring.stok-wms'),
                'stock_barriers' => route('monitoring.stok-barier-gudang.index'),
                'email_alert_settings' => route('settings.stock-barrier-alerts.edit'),
                'outbound_report' => route('monitoring.warehouse-outbound.index'),
                'reservations' => route('monitoring.stok-wms.reservations'),
            ],
        ]);
    }
}
