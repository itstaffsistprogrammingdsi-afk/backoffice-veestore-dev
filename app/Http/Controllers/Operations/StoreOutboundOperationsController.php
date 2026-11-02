<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Queries\StoreOutboundOperationsQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StoreOutboundOperationsController extends Controller
{
    public function __invoke(Request $request, StoreOutboundOperationsQuery $query)
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = trim((string) $request->query('store_id', ''));
        $storeId = $storeId !== '' ? $storeId : null;
        $channel = trim((string) $request->query('channel', ''));
        $channel = in_array($channel, ['store', 'ecommerce'], true) ? $channel : null;
        $status = trim((string) $request->query('status', ''));
        $status = in_array($status, ['missing_shipping', 'completed'], true) ? $status : null;
        $from = trim((string) $request->query('from', ''));
        $from = $from !== '' ? $from : null;
        $to = trim((string) $request->query('to', ''));
        $to = $to !== '' ? $to : null;

        return Inertia::render('Operations/StoreOutbound', [
            ...$query->build($q, $storeId, $channel, $status, $from, $to),
            'stores' => DB::table('stores')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'store_id' => $storeId,
                'channel' => $channel,
                'status' => $status,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('operations.store-outbound.index'),
                'stock' => route('operations.store-stock.index'),
                'inbound' => route('operations.store-inbound.index'),
                'monitoring' => route('monitoring.store-outbound.index'),
            ],
        ]);
    }
}
