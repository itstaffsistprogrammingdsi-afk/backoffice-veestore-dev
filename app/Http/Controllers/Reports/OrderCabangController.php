<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use App\Services\StockReservationService;
use App\Services\StoreOrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderCabangController extends Controller
{
    private const VALID_STATUSES = [
        'draft',
        'requested',
        'approved',
        'rejected',
        'processing',
        'shipped',

        'cancelled',
        'packing',
        'partial_approved',
        'received',
        'partial_received',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $status = $request->has('status')
            ? trim((string) $request->query('status', ''))
            : 'requested';

        $statusLower = strtolower($status);

        if ($status !== '' && ! in_array($statusLower, self::VALID_STATUSES, true)) {
            $status = 'requested';
            $statusLower = 'requested';
        }

        $hasCustomDate = $request->filled('from') || $request->filled('to');

        if ($hasCustomDate) {
            $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
            $to = $request->query('to') ?: now()->toDateString();
        } else {
            if ($statusLower === 'requested') {
                $from = null;
                $to = null;
            } else {
                $from = now()->startOfMonth()->toDateString();
                $to = now()->toDateString();
            }
        }

        $orders = StoreOrder::with(['store:id,name', 'warehouse:id,name'])
            ->withSum('details', 'requested_qty')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where('order_number', 'ILIKE', "%{$q}%");
            })
            ->when($status !== '', function ($qq) use ($status) {
                $qq->where('status', $status);
            })
            ->when($from && $to, function ($qq) use ($from, $to) {
                $qq->whereBetween('date', [$from, $to]);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $stores = DB::table('stores')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Reports/OrderCabang', [
            'orders' => $orders,
            'stores' => $stores,
            'statuses' => self::VALID_STATUSES,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('reports.order-cabang.index'),
                'show' => route('reports.order-cabang.show', ['order' => '__ID__']),
                'approval' => route('operations.store-orders.index'),
                'receive' => route('reports.order-cabang.receive', ['order' => '__ID__']),
                'request' => route('reports.order-cabang.request', ['order' => '__ID__']),
            ],
        ]);
    }

    public function show(StoreOrder $order)
    {
        $order->load([
            'store:id,name',
            'warehouse:id,name',
            'details' => function ($q) {
                $q->select(
                    'id',
                    'store_order_id',
                    'product_id',
                    'product_price_id',
                    'product_price',
                    'requested_qty',
                    'approved_qty',
                    'received_qty',
                    'note'
                );
            },
            'details.product:id,name',
        ]);

        $productIds = $order->details
            ->pluck('product_id')
            ->filter(fn ($x) => is_string($x) && Str::isUuid($x))
            ->unique()
            ->values()
            ->all();

        $productImages = collect();

        if (! empty($productIds) && Schema::hasTable('product_images')) {
            $productImages = DB::table('product_images')
                ->select('product_id', 'image_url')
                ->whereIn('product_id', $productIds)
                ->where('is_main', true)
                ->where('status_active', 'active')
                ->get()
                ->keyBy('product_id');
        }

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'date' => optional($order->date)->toDateString(),
            'store_id' => $order->store_id,
            'warehouse_id' => $order->warehouse_id,
            'store' => $order->store ? [
                'id' => $order->store->id,
                'name' => $order->store->name,
            ] : null,
            'warehouse' => $order->warehouse ? [
                'id' => $order->warehouse->id,
                'name' => $order->warehouse->name,
            ] : null,
            'details' => $order->details->map(function ($d) use ($productImages) {
                $imageUrl = optional($productImages->get($d->product_id))->image_url;

                return [
                    'id' => $d->id,
                    'product_id' => $d->product_id,
                    'product_price_id' => $d->product_price_id,
                    'product_price' => $d->product_price,
                    'requested_qty' => (int) $d->requested_qty,
                    'approved_qty' => (int) ($d->approved_qty ?? 0),
                    'received_qty' => (int) ($d->received_qty ?? 0),
                    'note' => $d->note,
                    'warehouse_id' => null,
                    'detail_warehouse_id' => null,
                    'product' => $d->relationLoaded('product') && $d->product
                        ? [
                            'id' => $d->product->id,
                            'name' => $d->product->name,
                            'image_url' => $imageUrl,
                        ]
                        : null,
                    'detail_warehouse' => null,
                ];
            })->values(),
        ]);
    }

    public function requestSubmit(StoreOrder $order, StoreOrderNotifier $notifier)
    {
        if (! $order->isEditable()) {
            return back()->withErrors(['status' => 'Order tidak bisa di-request.']);
        }

        $order->update([
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $notifier->requested($order->fresh(['store', 'warehouse']));

        return back()->with('success', 'Pesanan dikirim untuk persetujuan.');
    }

    public function stockByWarehouse(Request $request)
    {
        $warehouseId = (string) $request->query('warehouse_id', '');

        if (! Str::isUuid($warehouseId)) {
            return response()->json([
                'warehouse_id' => null,
                'warehouse_name' => null,
                'stocks' => [],
                'stock_details' => [],
            ]);
        }

        $warehouse = DB::table('warehouses')
            ->where('id', $warehouseId)
            ->whereNull('deleted_at')
            ->first(['id', 'name']);

        if (! $warehouse) {
            return response()->json([
                'warehouse_id' => null,
                'warehouse_name' => null,
                'stocks' => [],
                'stock_details' => [],
            ]);
        }

        $uuidIds = collect(explode(',', (string) $request->query('product_ids', '')))
            ->map(fn ($x) => trim($x))
            ->filter(fn ($x) => $x !== '' && Str::isUuid($x))
            ->values()
            ->all();

        $stockData = $this->getStockDataByProduct($warehouse->id, $uuidIds);

        return response()->json([
            'warehouse_id' => $warehouse->id,
            'warehouse_name' => $warehouse->name,
            'stocks' => $stockData['stocks'],
            'stock_details' => $stockData['details'],
        ]);
    }

    public function receive(Request $request, StoreOrder $order, StockReservationService $reservations)
    {
        if (! in_array(strtolower((string) $order->status), ['approved', 'partial_approved', 'processing', 'packing', 'shipped', 'partial_received'], true)) {
            return back()->withErrors(['status' => 'Order belum dapat diterima. Pastikan order sudah disetujui terlebih dahulu.']);
        }

        $data = $request->validate([
            'received_qty' => ['required', 'array', 'min:1'],
            'received_qty.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $details = $order->details()
            ->select(['id', 'product_id', 'approved_qty', 'received_qty'])
            ->get()
            ->keyBy('id');

        if ($details->isEmpty()) {
            return back()->withErrors(['status' => 'Detail order tidak ditemukan.']);
        }

        $receivedInput = $data['received_qty'];

        foreach (array_keys($receivedInput) as $detailId) {
            if (! $details->has($detailId)) {
                return back()->withErrors(['status' => 'Detail penerimaan tidak valid.']);
            }
        }

        foreach ($details as $detail) {
            $received = (int) ($receivedInput[$detail->id] ?? $detail->received_qty ?? 0);
            if ($received > (int) ($detail->approved_qty ?? 0)) {
                return back()->withErrors(['status' => 'Qty diterima tidak boleh melebihi qty yang disetujui.']);
            }
        }

        DB::transaction(function () use ($order, $details, $receivedInput, $reservations): void {
            foreach ($details as $detail) {
                $received = (int) ($receivedInput[$detail->id] ?? $detail->received_qty ?? 0);

                DB::table('store_order_details')
                    ->where('id', $detail->id)
                    ->update([
                        'received_qty' => $received,
                        'updated_at' => now(),
                    ]);
            }

            $allReceived = $details->every(function ($detail) use ($receivedInput): bool {
                $approved = (int) ($detail->approved_qty ?? 0);
                $received = (int) ($receivedInput[$detail->id] ?? $detail->received_qty ?? 0);

                return $approved === 0 || $received >= $approved;
            });

            $order->update(['status' => $allReceived ? 'received' : 'partial_received']);
            $reservations->completeStoreOrder($order->id);
        });

        return back()->with('success', 'Penerimaan disimpan. Qty dipesan berkurang sesuai qty yang sudah diterima.');
    }

    private function getStockDataByProduct(string $warehouseId, array $productIds = []): array
    {
        $uuidIds = array_values(array_filter(
            $productIds,
            fn ($x) => is_string($x) && Str::isUuid($x)
        ));

        $bindings = [
            'warehouse_id_stock' => $warehouseId,
        ];

        $whereStock = '';

        if (! empty($uuidIds)) {
            $stockPlaceholders = [];

            foreach ($uuidIds as $i => $uuid) {
                $stockKey = "product_stock_{$i}";
                $stockPlaceholders[] = ":{$stockKey}";
                $bindings[$stockKey] = $uuid;
            }

            $whereStock = ' AND i.product_id IN ('.implode(',', $stockPlaceholders).') ';
        }

        $sql = <<<SQL
SELECT
    i.product_id,

    SUM(
        CASE
            WHEN LOWER(COALESCE(c.status::text, c.name, '')) = 'good'
            THEN 1 ELSE 0
        END
    )::int AS qty_good,

    SUM(
        CASE
            WHEN LOWER(COALESCE(c.status::text, c.name, '')) = 'good_after_repair'
            THEN 1 ELSE 0
        END
    )::int AS qty_good_after_repair,

    SUM(
        CASE
            WHEN LOWER(COALESCE(c.status::text, c.name, '')) = 'bad'
            THEN 1 ELSE 0
        END
    )::int AS qty_bad,

    (
        SUM(
            CASE
                WHEN LOWER(COALESCE(c.status::text, c.name, '')) = 'good'
                THEN 1 ELSE 0
            END
        ) +
        SUM(
            CASE
                WHEN LOWER(COALESCE(c.status::text, c.name, '')) = 'good_after_repair'
                THEN 1 ELSE 0
            END
        )
    )::int AS qty_available_stock

FROM items i
JOIN warehouse_items wi
    ON wi.item_id = i.id
LEFT JOIN item_conditions c
    ON c.id = i.current_condition_id
WHERE wi.warehouse_id = :warehouse_id_stock
  AND i.status = 'warehouse_stock'
  {$whereStock}
GROUP BY
    i.product_id
SQL;

        $rows = DB::select($sql, $bindings);

        // Stok yang sudah di-reservasi lewat tabel stock_reservations
        // (reference_type ecommerce_order maupun store_order) berstatus active.
        $reservedMap = $this->getActiveReservationMap($warehouseId, $uuidIds);

        $stocks = [];
        $details = [];

        foreach ($rows as $row) {
            $productId = (string) $row->product_id;
            $reserved = (int) ($reservedMap[$productId] ?? 0);

            $available = max(0, (int) $row->qty_available_stock - $reserved);

            $stocks[$productId] = $available;

            $details[$productId] = [
                'qty_good' => (int) $row->qty_good,
                'qty_good_after_repair' => (int) $row->qty_good_after_repair,
                'qty_bad' => (int) $row->qty_bad,
                'qty_available_stock' => (int) $row->qty_available_stock,
                'qty_reserved' => $reserved,
                'qty_available_after_order' => $available,
            ];
        }

        foreach ($uuidIds as $productId) {
            if (! array_key_exists($productId, $stocks)) {
                $reserved = (int) ($reservedMap[$productId] ?? 0);

                $stocks[$productId] = 0;

                $details[$productId] = [
                    'qty_good' => 0,
                    'qty_good_after_repair' => 0,
                    'qty_bad' => 0,
                    'qty_available_stock' => 0,
                    'qty_reserved' => $reserved,
                    'qty_available_after_order' => 0,
                ];
            }
        }

        return [
            'stocks' => $stocks,
            'details' => $details,
        ];
    }

    private function getActiveReservationMap(string $warehouseId, array $productIds = []): array
    {
        if (! Schema::hasTable('stock_reservations')) {
            return [];
        }

        return app(StockReservationService::class)
            ->remainingByLocation('warehouse')
            ->get()
            ->filter(function ($row) use ($warehouseId, $productIds): bool {
                return (string) $row->location_id === $warehouseId
                    && (empty($productIds) || in_array((string) $row->product_id, $productIds, true));
            })
            ->pluck('qty_reserved', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
