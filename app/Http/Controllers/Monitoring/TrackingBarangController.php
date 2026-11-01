<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\TrackingBarangExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TrackingBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-tracking-barang')
            ->only(['index', 'detail', 'export', 'downloadQr']);
    }

    public function index(Request $request)
    {
        $keyword = $this->normalizeNullableString($request->query('keyword')) ?? '';
        $productId = $this->normalizeNullableString($request->query('product_id'));
        $itemId = $this->normalizeNullableString($request->query('item_id'));
        $storeId = $this->normalizeNullableString($request->query('store_id'));
        $warehouseId = $this->normalizeNullableString($request->query('warehouse_id'));
        $trackingStatus = $this->normalizeNullableString($request->query('tracking_status'));
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $products = DB::table('products')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = $this->getRows(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus,
            perPage: $perPage
        );

        return view('content.monitoring.tracking-barang.index', [
            'rows' => $rows,
            'products' => $products,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'keyword' => $keyword,
            'productId' => $productId,
            'itemId' => $itemId,
            'storeId' => $storeId,
            'warehouseId' => $warehouseId,
            'trackingStatus' => $trackingStatus,
            'perPage' => $perPage,
        ]);
    }

    public function export(Request $request)
    {
        $keyword = $this->normalizeNullableString($request->query('keyword')) ?? '';
        $productId = $this->normalizeNullableString($request->query('product_id'));
        $itemId = $this->normalizeNullableString($request->query('item_id'));
        $storeId = $this->normalizeNullableString($request->query('store_id'));
        $warehouseId = $this->normalizeNullableString($request->query('warehouse_id'));
        $trackingStatus = $this->normalizeNullableString($request->query('tracking_status'));

        $summaryRows = $this->getRowsForExport(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        );

        $realtimeRows = $this->getRealtimeExportRows(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        );

        $dailyRows = $this->getDailyExportRows(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        );

        $monthlyRows = $this->getMonthlyExportRows(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        );

        $filename = 'tracking-barang-laporan-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new TrackingBarangExport(
                $summaryRows,
                $realtimeRows,
                $dailyRows,
                $monthlyRows,
                [
                    'keyword' => $keyword ?: '-',
                    'product_id' => $productId ?: '-',
                    'item_id' => $itemId ?: '-',
                    'store_id' => $storeId ?: '-',
                    'warehouse_id' => $warehouseId ?: '-',
                    'tracking_status' => $trackingStatus ?: '-',
                ]
            ),
            $filename
        );
    }

    private function buildMovementTimelineQuery(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null
    ) {
        $timeline = DB::table('warehouse_inbound_details as wid')
            ->join('items as i', 'i.warehouse_inbound_detail_id', '=', 'wid.id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->selectRaw("
                wid.created_at as event_time,
                p.id as product_id,
                p.name as product_name,
                i.id as item_id,
                COALESCE(rt.value, CAST(i.id AS TEXT)) as display_item_id,
                rt.value as rfid_value,
                'INBOUND_WAREHOUSE' as event_code,
                'Inbound Warehouse' as event_name,
                'IN' as movement_type,
                null::text as warehouse_id,
                null::text as store_id,
                wid.warehouse_inbound_id::text as reference_info,
                CONCAT('qty=', COALESCE(wid.quantity::text, ''), ', note=', COALESCE(wid.note, '')) as detail_info
            ")
            ->unionAll(
                DB::table('warehouse_outbound_details as wod')
                    ->join('items as i', 'i.id', '=', 'wod.item_id')
                    ->join('products as p', 'p.id', '=', 'i.product_id')
                    ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
                    ->selectRaw("
                        wod.created_at as event_time,
                        p.id as product_id,
                        p.name as product_name,
                        i.id as item_id,
                        COALESCE(rt.value, CAST(i.id AS TEXT)) as display_item_id,
                        rt.value as rfid_value,
                        'OUTBOUND_WAREHOUSE' as event_code,
                        'Outbound Warehouse' as event_name,
                        'OUT' as movement_type,
                        null::text as warehouse_id,
                        null::text as store_id,
                        wod.warehouse_outbound_id::text as reference_info,
                        'item outbound from warehouse' as detail_info
                    ")
            )
            ->unionAll(
                DB::table('store_inbound_details as sid')
                    ->join('items as i', 'i.id', '=', 'sid.item_id')
                    ->join('products as p', 'p.id', '=', 'i.product_id')
                    ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
                    ->selectRaw("
                        sid.created_at as event_time,
                        p.id as product_id,
                        p.name as product_name,
                        i.id as item_id,
                        COALESCE(rt.value, CAST(i.id AS TEXT)) as display_item_id,
                        rt.value as rfid_value,
                        'INBOUND_STORE' as event_code,
                        'Inbound Store' as event_name,
                        'IN' as movement_type,
                        null::text as warehouse_id,
                        null::text as store_id,
                        sid.store_inbound_id::text as reference_info,
                        CONCAT('has_been_qc=', COALESCE(sid.has_been_qc::text, '')) as detail_info
                    ")
            )
            ->unionAll(
                DB::table('store_cart_details as scd')
                    ->join('items as i', 'i.id', '=', 'scd.item_id')
                    ->join('products as p', 'p.id', '=', 'i.product_id')
                    ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
                    ->selectRaw("
                        scd.created_at as event_time,
                        p.id as product_id,
                        p.name as product_name,
                        i.id as item_id,
                        COALESCE(rt.value, CAST(i.id AS TEXT)) as display_item_id,
                        rt.value as rfid_value,
                        'SOLD' as event_code,
                        'Sold' as event_name,
                        'OUT' as movement_type,
                        null::text as warehouse_id,
                        null::text as store_id,
                        scd.cart_id::text as reference_info,
                        CONCAT('product_price_id=', COALESCE(scd.product_price_id::text, '')) as detail_info
                    ")
            );

        $query = DB::query()->fromSub($timeline, 'mv');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('mv.product_name', 'ilike', "%{$keyword}%")
                    ->orWhereRaw('CAST(mv.item_id AS TEXT) ILIKE ?', ["%{$keyword}%"])
                    ->orWhere('mv.display_item_id', 'ilike', "%{$keyword}%")
                    ->orWhere('mv.rfid_value', 'ilike', "%{$keyword}%");
            });
        }

        if ($productId) {
            $query->where('mv.product_id', $productId);
        }

        if ($itemId) {
            $query->where(function ($q) use ($itemId) {
                $q->whereRaw('CAST(mv.item_id AS TEXT) ILIKE ?', ["%{$itemId}%"])
                    ->orWhere('mv.display_item_id', 'ilike', "%{$itemId}%")
                    ->orWhere('mv.rfid_value', 'ilike', "%{$itemId}%");
            });
        }

        if ($trackingStatus) {
            $map = [
                'inbound_warehouse' => 'INBOUND_WAREHOUSE',
                'outbound_warehouse' => 'OUTBOUND_WAREHOUSE',
                'inbound_store' => 'INBOUND_STORE',
                'sold' => 'SOLD',
            ];

            if (isset($map[$trackingStatus])) {
                $query->where('mv.event_code', $map[$trackingStatus]);
            }
        }

        return $query;
    }

    private function getRealtimeExportRows(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null
    ) {
        return $this->buildMovementTimelineQuery(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        )
            ->orderBy('event_time', 'desc')
            ->get();
    }

    private function getDailyExportRows(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null
    ) {
        return DB::query()
            ->fromSub(
                $this->buildMovementTimelineQuery(
                    keyword: $keyword,
                    productId: $productId,
                    itemId: $itemId,
                    storeId: $storeId,
                    warehouseId: $warehouseId,
                    trackingStatus: $trackingStatus
                ),
                'mv'
            )
            ->selectRaw("
                DATE(mv.event_time) as period_date,
                mv.product_id,
                mv.product_name,
                mv.item_id,
                mv.display_item_id,
                mv.rfid_value,
                SUM(CASE WHEN mv.movement_type = 'IN' THEN 1 ELSE 0 END) as in_count,
                SUM(CASE WHEN mv.movement_type = 'OUT' THEN 1 ELSE 0 END) as out_count,
                SUM(CASE WHEN mv.movement_type = 'IN' THEN 1 ELSE 0 END)
                - SUM(CASE WHEN mv.movement_type = 'OUT' THEN 1 ELSE 0 END) as balance_count
            ")
            ->groupByRaw("
                DATE(mv.event_time),
                mv.product_id,
                mv.product_name,
                mv.item_id,
                mv.display_item_id,
                mv.rfid_value
            ")
            ->orderBy('period_date', 'desc')
            ->orderBy('product_name')
            ->get();
    }

    private function getMonthlyExportRows(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null
    ) {
        return DB::query()
            ->fromSub(
                $this->buildMovementTimelineQuery(
                    keyword: $keyword,
                    productId: $productId,
                    itemId: $itemId,
                    storeId: $storeId,
                    warehouseId: $warehouseId,
                    trackingStatus: $trackingStatus
                ),
                'mv'
            )
            ->selectRaw("
                TO_CHAR(DATE_TRUNC('month', mv.event_time), 'YYYY-MM') as period_month,
                mv.product_id,
                mv.product_name,
                mv.item_id,
                mv.display_item_id,
                mv.rfid_value,
                SUM(CASE WHEN mv.movement_type = 'IN' THEN 1 ELSE 0 END) as in_count,
                SUM(CASE WHEN mv.movement_type = 'OUT' THEN 1 ELSE 0 END) as out_count,
                SUM(CASE WHEN mv.movement_type = 'IN' THEN 1 ELSE 0 END)
                - SUM(CASE WHEN mv.movement_type = 'OUT' THEN 1 ELSE 0 END) as balance_count
            ")
            ->groupByRaw("
                DATE_TRUNC('month', mv.event_time),
                mv.product_id,
                mv.product_name,
                mv.item_id,
                mv.display_item_id,
                mv.rfid_value
            ")
            ->orderBy('period_month', 'desc')
            ->orderBy('product_name')
            ->get();
    }

    private function buildLatestStatusSubquery()
    {
        $timeline = DB::table('items as i')
            ->selectRaw("
                i.id as item_id,
                'ITEM_CREATED' as event_code,
                i.created_at as event_time
            ")
            ->unionAll(
                DB::table('warehouse_inbound_details as wid')
                    ->join('items as i2', 'i2.warehouse_inbound_detail_id', '=', 'wid.id')
                    ->selectRaw("
                        i2.id as item_id,
                        'INBOUND_WAREHOUSE' as event_code,
                        wid.created_at as event_time
                    ")
            )
            ->unionAll(
                DB::table('warehouse_items as wi')
                    ->selectRaw("
                        wi.item_id,
                        'STOCK_WAREHOUSE' as event_code,
                        wi.created_at as event_time
                    ")
            )
            ->unionAll(
                DB::table('warehouse_qc as wq')
                    ->selectRaw("
                        wq.item_id,
                        'IN_WAREHOUSE' as event_code,
                        COALESCE(wq.performed_at, wq.created_at) as event_time
                    ")
            )
            ->unionAll(
                DB::table('warehouse_outbound_details as wod')
                    ->selectRaw("
                        wod.item_id,
                        'OUTBOUND_WAREHOUSE' as event_code,
                        wod.created_at as event_time
                    ")
            )
            ->unionAll(
                DB::table('store_inbound_details as sid')
                    ->selectRaw("
                        sid.item_id,
                        'INBOUND_STORE' as event_code,
                        sid.created_at as event_time
                    ")
            )
            ->unionAll(
                DB::table('store_items as si')
                    ->selectRaw("
                        si.item_id,
                        'STOCK_STORE' as event_code,
                        si.created_at as event_time
                    ")
            )
            ->unionAll(
                DB::table('store_qc as sq')
                    ->selectRaw("
                        sq.item_id,
                        'DISPLAY_STORE' as event_code,
                        COALESCE(sq.performed_at, sq.created_at) as event_time
                    ")
            )
            ->unionAll(
                DB::table('store_outbound_details as sod')
                    ->selectRaw("
                        sod.item_id,
                        'DISPLAY_STORE' as event_code,
                        sod.created_at as event_time
                    ")
            )
            ->unionAll(
                DB::table('store_cart_details as scd')
                    ->selectRaw("
                        scd.item_id,
                        'SOLD' as event_code,
                        scd.created_at as event_time
                    ")
            );

        return DB::query()->fromSub(
            DB::query()->fromSub($timeline, 't')
                ->selectRaw("
                    t.item_id,
                    t.event_code,
                    t.event_time,
                    ROW_NUMBER() OVER (
                        PARTITION BY t.item_id
                        ORDER BY t.event_time DESC NULLS LAST, t.event_code DESC
                    ) as rn
                "),
            'latest_status_raw'
        )
            ->selectRaw("
                latest_status_raw.item_id,
                latest_status_raw.event_code,
                latest_status_raw.event_time,
                CASE latest_status_raw.event_code
                    WHEN 'INBOUND_WAREHOUSE' THEN 'Inbound Warehouse'
                    WHEN 'IN_WAREHOUSE' THEN 'In Warehouse'
                    WHEN 'STOCK_WAREHOUSE' THEN 'Stock Warehouse'
                    WHEN 'OUTBOUND_WAREHOUSE' THEN 'Outbound Warehouse'
                    WHEN 'INBOUND_STORE' THEN 'Inbound Store'
                    WHEN 'STOCK_STORE' THEN 'Stock Store'
                    WHEN 'DISPLAY_STORE' THEN 'Display Store'
                    WHEN 'SOLD' THEN 'Sold'
                    ELSE 'Inbound Warehouse'
                END as tracking_status_label
            ")
            ->where('latest_status_raw.rn', 1);
    }

    private function buildIndexBaseQuery(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null
    ) {
        $latestStatusSub = $this->buildLatestStatusSubquery();

        $soldSub = DB::query()->fromSub(
            DB::table('store_cart_details as scd')
                ->join('store_carts as sc', 'sc.id', '=', 'scd.cart_id')
                ->leftJoin('customers as c', 'c.id', '=', 'sc.customer_id')
                ->selectRaw("
                    scd.item_id,
                    scd.created_at as sold_at,
                    sc.id as cart_id,
                    sc.customer_id,
                    c.name as customer_name,
                    ROW_NUMBER() OVER (
                        PARTITION BY scd.item_id
                        ORDER BY scd.created_at DESC NULLS LAST, scd.id DESC
                    ) as rn
                "),
            'sold_raw'
        )
            ->select([
                'sold_raw.item_id',
                'sold_raw.sold_at',
                'sold_raw.cart_id',
                'sold_raw.customer_id',
                'sold_raw.customer_name',
            ])
            ->where('sold_raw.rn', 1);

        $storeItemSub = DB::query()->fromSub(
            DB::table('store_items as si')
                ->selectRaw("
                    si.item_id,
                    si.store_id,
                    si.created_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY si.item_id
                        ORDER BY si.created_at DESC NULLS LAST, si.id DESC
                    ) as rn
                "),
            'si_raw'
        )
            ->select([
                'si_raw.item_id',
                'si_raw.store_id',
                'si_raw.created_at',
            ])
            ->where('si_raw.rn', 1);

        $warehouseItemSub = DB::query()->fromSub(
            DB::table('warehouse_items as wi')
                ->selectRaw("
                    wi.item_id,
                    wi.warehouse_id,
                    wi.created_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY wi.item_id
                        ORDER BY wi.created_at DESC NULLS LAST, wi.id DESC
                    ) as rn
                "),
            'wi_raw'
        )
            ->select([
                'wi_raw.item_id',
                'wi_raw.warehouse_id',
                'wi_raw.created_at',
            ])
            ->where('wi_raw.rn', 1);

        $query = DB::table('products as p')
            ->leftJoin('items as i', 'i.product_id', '=', 'p.id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->leftJoinSub($storeItemSub, 'si', function ($join) {
                $join->on('si.item_id', '=', 'i.id');
            })
            ->leftJoinSub($warehouseItemSub, 'wi', function ($join) {
                $join->on('wi.item_id', '=', 'i.id');
            })
            ->leftJoinSub($soldSub, 'sold', function ($join) {
                $join->on('sold.item_id', '=', 'i.id');
            })
            ->leftJoinSub($latestStatusSub, 'ls', function ($join) {
                $join->on('ls.item_id', '=', 'i.id');
            })
            ->whereNull('p.deleted_at');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('p.name', 'ilike', "%{$keyword}%")
                    ->orWhere('p.code', 'ilike', "%{$keyword}%")
                    ->orWhereRaw('CAST(i.id AS TEXT) ILIKE ?', ["%{$keyword}%"])
                    ->orWhere('rt.value', 'ilike', "%{$keyword}%")
                    ->orWhere('sold.customer_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($productId) {
            $query->where('p.id', $productId);
        }

        if ($itemId) {
            $query->where(function ($q) use ($itemId) {
                $q->whereRaw('CAST(i.id AS TEXT) ILIKE ?', ["%{$itemId}%"])
                    ->orWhere('rt.value', 'ilike', "%{$itemId}%");
            });
        }

        if ($storeId) {
            $query->where(function ($q) use ($storeId) {
                $q->where('si.store_id', $storeId)
                    ->orWhereNull('i.id');
            });
        }

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('wi.warehouse_id', $warehouseId)
                    ->orWhereNull('i.id');
            });
        }

        return $query
            ->selectRaw("
                p.id as product_id,
                p.name as product_name,
                COUNT(DISTINCT i.id) as total_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'INBOUND_WAREHOUSE' THEN i.id END) as inbound_warehouse_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'IN_WAREHOUSE' THEN i.id END) as in_warehouse_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'STOCK_WAREHOUSE' THEN i.id END) as stock_warehouse_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'OUTBOUND_WAREHOUSE' THEN i.id END) as outbound_warehouse_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'INBOUND_STORE' THEN i.id END) as inbound_store_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'STOCK_STORE' THEN i.id END) as stock_store_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'DISPLAY_STORE' THEN i.id END) as display_store_items,
                COUNT(DISTINCT CASE WHEN ls.event_code = 'SOLD' THEN i.id END) as sold_items,
                MAX(i.updated_at) as last_item_update
            ")
            ->groupBy('p.id', 'p.name')
            ->when($trackingStatus, function ($q) use ($trackingStatus) {
                $map = [
                    'inbound_warehouse' => 'INBOUND_WAREHOUSE',
                    'in_warehouse' => 'IN_WAREHOUSE',
                    'stock_warehouse' => 'STOCK_WAREHOUSE',
                    'outbound_warehouse' => 'OUTBOUND_WAREHOUSE',
                    'inbound_store' => 'INBOUND_STORE',
                    'stock_store' => 'STOCK_STORE',
                    'display_store' => 'DISPLAY_STORE',
                    'sold' => 'SOLD',
                ];

                if (isset($map[$trackingStatus])) {
                    $eventCode = $map[$trackingStatus];

                    $q->havingRaw("
                        COUNT(DISTINCT CASE
                            WHEN ls.event_code = ? THEN i.id
                        END) > 0
                    ", [$eventCode]);
                }
            })
            ->orderBy('p.name');
    }

    private function getRows(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null,
        int $perPage = 10
    ) {
        return $this->buildIndexBaseQuery(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        )
            ->paginate($perPage)
            ->withQueryString();
    }

    private function getRowsForExport(
        string $keyword = '',
        ?string $productId = null,
        ?string $itemId = null,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $trackingStatus = null
    ) {
        return $this->buildIndexBaseQuery(
            keyword: $keyword,
            productId: $productId,
            itemId: $itemId,
            storeId: $storeId,
            warehouseId: $warehouseId,
            trackingStatus: $trackingStatus
        )->get();
    }

    public function detail(string $productId, Request $request)
    {
        $perPage = (int) ($request->query('per_page', 10) ?: 10);
        $itemId = $this->normalizeNullableString($request->query('item_id'));

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $product = DB::table('products')
            ->where('id', $productId)
            ->first(['id', 'name', 'code']);

        abort_if(!$product, 404);

        $latestStatusSub = $this->buildLatestStatusSubquery();

        $soldLastSub = DB::query()->fromSub(
            DB::table('store_cart_details as scd')
                ->join('store_carts as sc', 'sc.id', '=', 'scd.cart_id')
                ->leftJoin('customers as cust', 'cust.id', '=', 'sc.customer_id')
                ->selectRaw("
                    scd.item_id,
                    scd.cart_id,
                    scd.product_price_id,
                    scd.created_at as sold_at,
                    cust.name as customer_name,
                    cust.wa_number as customer_phone,
                    cust.email as customer_email,
                    ROW_NUMBER() OVER (
                        PARTITION BY scd.item_id
                        ORDER BY scd.created_at DESC NULLS LAST, scd.id DESC
                    ) as rn
                "),
            'sold_last_raw'
        )
            ->select([
                'sold_last_raw.item_id',
                'sold_last_raw.cart_id',
                'sold_last_raw.product_price_id',
                'sold_last_raw.sold_at',
                'sold_last_raw.customer_name',
                'sold_last_raw.customer_phone',
                'sold_last_raw.customer_email',
            ])
            ->where('sold_last_raw.rn', 1);

        $lastLocationSub = DB::query()->fromSub(
            DB::table('item_location_histories as ilh')
                ->selectRaw("
                    ilh.item_id,
                    ilh.location_id,
                    ilh.created_at as moved_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY ilh.item_id
                        ORDER BY ilh.created_at DESC NULLS LAST, ilh.id DESC
                    ) as rn
                "),
            'llh_raw'
        )
            ->select([
                'llh_raw.item_id',
                'llh_raw.location_id',
                'llh_raw.moved_at',
            ])
            ->where('llh_raw.rn', 1);

        $storeItemSub = DB::query()->fromSub(
            DB::table('store_items as si')
                ->selectRaw("
                    si.item_id,
                    si.store_id,
                    si.created_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY si.item_id
                        ORDER BY si.created_at DESC NULLS LAST, si.id DESC
                    ) as rn
                "),
            'si_raw'
        )
            ->select([
                'si_raw.item_id',
                'si_raw.store_id',
                'si_raw.created_at',
            ])
            ->where('si_raw.rn', 1);

        $warehouseItemSub = DB::query()->fromSub(
            DB::table('warehouse_items as wi')
                ->selectRaw("
                    wi.item_id,
                    wi.warehouse_id,
                    wi.created_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY wi.item_id
                        ORDER BY wi.created_at DESC NULLS LAST, wi.id DESC
                    ) as rn
                "),
            'wi_raw'
        )
            ->select([
                'wi_raw.item_id',
                'wi_raw.warehouse_id',
                'wi_raw.created_at',
            ])
            ->where('wi_raw.rn', 1);

        $query = DB::table('items as i')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->leftJoinSub($storeItemSub, 'si', function ($join) {
                $join->on('si.item_id', '=', 'i.id');
            })
            ->leftJoinSub($warehouseItemSub, 'wi', function ($join) {
                $join->on('wi.item_id', '=', 'i.id');
            })
            ->leftJoinSub($soldLastSub, 'sold_last', function ($join) {
                $join->on('sold_last.item_id', '=', 'i.id');
            })
            ->leftJoinSub($lastLocationSub, 'llh', function ($join) {
                $join->on('llh.item_id', '=', 'i.id');
            })
            ->leftJoinSub($latestStatusSub, 'ls', function ($join) {
                $join->on('ls.item_id', '=', 'i.id');
            })
            ->leftJoin('locations as cl', 'cl.id', '=', 'i.current_location_id')
            ->leftJoin('locations as clp', 'clp.id', '=', 'cl.location_parent_id')
            ->leftJoin('locations as lhl', 'lhl.id', '=', 'llh.location_id')
            ->leftJoin('locations as lhlp', 'lhlp.id', '=', 'lhl.location_parent_id')
            ->where('i.product_id', $productId);

        if ($itemId) {
            $query->where(function ($q) use ($itemId) {
                $q->whereRaw('CAST(i.id AS TEXT) ILIKE ?', ["%{$itemId}%"])
                    ->orWhere('rt.value', 'ilike', "%{$itemId}%");
            });
        }

        $rows = $query
            ->selectRaw("
                i.id as item_id,
                COALESCE(rt.value, CAST(i.id AS TEXT)) as display_item_id,
                i.status::text as item_status,
                ic.name as current_condition,
                rt.value as rfid_value,
                wi.warehouse_id,
                si.store_id,
                sold_last.cart_id,
                sold_last.product_price_id,
                sold_last.sold_at,
                sold_last.customer_name,
                sold_last.customer_phone,
                sold_last.customer_email,

                i.current_location_id,
                cl.name as current_location_name,
                cl.type::text as current_location_type,
                cl.code as current_location_code,
                clp.name as current_parent_location,

                llh.location_id as last_history_location_id,
                lhl.name as last_history_location_name,
                lhl.type::text as last_history_location_type,
                lhl.code as last_history_location_code,
                lhlp.name as last_history_parent_location,
                llh.moved_at as last_moved_at,

                ls.event_code as tracking_status_code,
                ls.tracking_status_label as tracking_status
            ")
            ->orderBy('i.created_at')
            ->paginate($perPage)
            ->withQueryString();

        $itemIds = collect($rows->items())->pluck('item_id')->filter()->values();
        $timelines = collect();

        if ($itemIds->isNotEmpty()) {
            $timelineRows = DB::table('warehouse_inbound_details as wid')
                ->join('items as i2', 'i2.warehouse_inbound_detail_id', '=', 'wid.id')
                ->whereIn('i2.id', $itemIds)
                ->selectRaw("
                    i2.id as item_id,
                    'Inbound Warehouse' as event_name,
                    wid.created_at as event_time,
                    wid.warehouse_inbound_id::text as reference_info,
                    CONCAT(
                        'qty=', COALESCE(wid.quantity::text, ''),
                        ', note=', COALESCE(wid.note, '')
                    ) as detail_info
                ")
                ->unionAll(
                    DB::table('warehouse_qc as wq')
                        ->whereIn('wq.item_id', $itemIds)
                        ->selectRaw("
                            wq.item_id,
                            'In Warehouse' as event_name,
                            COALESCE(wq.performed_at, wq.created_at) as event_time,
                            wq.id::text as reference_info,
                            CONCAT(
                                'warehouse_id=', COALESCE(wq.warehouse_id::text, ''),
                                ', status=', COALESCE(wq.status::text, ''),
                                ', note=', COALESCE(wq.note, '')
                            ) as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('warehouse_items as wi')
                        ->whereIn('wi.item_id', $itemIds)
                        ->selectRaw("
                            wi.item_id,
                            'Stock Warehouse' as event_name,
                            wi.created_at as event_time,
                            wi.warehouse_id::text as reference_info,
                            'item registered in warehouse stock' as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('warehouse_outbound_details as wod')
                        ->whereIn('wod.item_id', $itemIds)
                        ->selectRaw("
                            wod.item_id,
                            'Outbound Warehouse' as event_name,
                            wod.created_at as event_time,
                            wod.warehouse_outbound_id::text as reference_info,
                            'item outbound from warehouse' as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('store_inbound_details as sid')
                        ->whereIn('sid.item_id', $itemIds)
                        ->selectRaw("
                            sid.item_id,
                            'Inbound Store' as event_name,
                            sid.created_at as event_time,
                            sid.store_inbound_id::text as reference_info,
                            CONCAT('has_been_qc=', COALESCE(sid.has_been_qc::text, '')) as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('store_items as si')
                        ->whereIn('si.item_id', $itemIds)
                        ->selectRaw("
                            si.item_id,
                            'Stock Store' as event_name,
                            si.created_at as event_time,
                            si.store_id::text as reference_info,
                            CONCAT('category=', COALESCE(si.category::text, '')) as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('store_qc as sq')
                        ->whereIn('sq.item_id', $itemIds)
                        ->selectRaw("
                            sq.item_id,
                            'Display Store' as event_name,
                            COALESCE(sq.performed_at, sq.created_at) as event_time,
                            sq.id::text as reference_info,
                            CONCAT(
                                'store_id=', COALESCE(sq.store_id::text, ''),
                                ', qc_type=', COALESCE(sq.qc_type::text, '')
                            ) as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('store_outbound_details as sod')
                        ->whereIn('sod.item_id', $itemIds)
                        ->selectRaw("
                            sod.item_id,
                            'Display Store' as event_name,
                            sod.created_at as event_time,
                            sod.store_outbound_id::text as reference_info,
                            CONCAT(
                                'rfid_releaser_user_id=',
                                COALESCE(sod.rfid_releaser_user_id::text, '')
                            ) as detail_info
                        ")
                )
                ->unionAll(
                    DB::table('store_cart_details as scd')
                        ->whereIn('scd.item_id', $itemIds)
                        ->selectRaw("
                            scd.item_id,
                            'Sold' as event_name,
                            scd.created_at as event_time,
                            scd.cart_id::text as reference_info,
                            CONCAT(
                                'product_price_id=',
                                COALESCE(scd.product_price_id::text, '')
                            ) as detail_info
                        ")
                );

            $timelines = DB::query()
                ->fromSub($timelineRows, 'x')
                ->orderBy('item_id')
                ->orderBy('event_time')
                ->get()
                ->groupBy('item_id');
        }

        return view('content.monitoring.tracking-barang.detail', [
            'product' => $product,
            'rows' => $rows,
            'timelines' => $timelines,
            'perPage' => $perPage,
            'itemId' => $itemId,
        ]);
    }

    public function downloadQr(string $productId, string $itemId)
    {
        $row = DB::table('items as i')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->where('i.product_id', $productId)
            ->where('i.id', $itemId)
            ->select([
                'i.id as item_id',
                'p.id as product_id',
                'p.name as product_name',
                'p.code as product_code',
                'rt.value as rfid_value',
            ])
            ->first();

        abort_if(!$row, 404, 'Item tidak ditemukan.');
        abort_if(empty($row->rfid_value), 404, 'RFID value tidak tersedia.');

        $safeProduct = $this->safeFilename($row->product_code ?: $row->product_name ?: 'product');
        $safeRfid = $this->safeFilename($row->rfid_value);

        $filename = 'qr-rfid-' . $safeProduct . '-' . $safeRfid . '.svg';

        $svg = \QrCode::format('svg')
            ->size(600)
            ->margin(2)
            ->generate($row->rfid_value);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function safeFilename(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : 'file';
    }
}