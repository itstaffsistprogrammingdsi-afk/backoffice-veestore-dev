<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\Monitoring\ProductAgingExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductAgingMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $detailBase = $this->buildMonitoringDetailQuery($filters);

        $rows = DB::query()
            ->fromSub($detailBase, 'product_aging_detail')
            ->selectRaw("
                product_id,
                product_code,
                product_name,

                SUM(quantity)::int as quantity_total,

                SUM(CASE WHEN scope_type = 'warehouse' THEN quantity ELSE 0 END)::int as warehouse_quantity,
                SUM(CASE WHEN scope_type = 'store' THEN quantity ELSE 0 END)::int as store_quantity,

                MAX(total_aging_days)::int as max_aging_days,

                STRING_AGG(DISTINCT NULLIF(warehouse_name, ''), ', ') as warehouse_names,
                STRING_AGG(DISTINCT NULLIF(store_name, ''), ', ') as store_names,

                MAX(CASE WHEN scope_type = 'warehouse' THEN total_aging_days ELSE 0 END)::int as max_warehouse_aging_days,
                MAX(CASE WHEN scope_type = 'store' THEN total_aging_days ELSE 0 END)::int as max_store_aging_days
            ")
            ->groupBy('product_id', 'product_code', 'product_name')
            ->orderByDesc('max_aging_days')
            ->orderByDesc('quantity_total')
            ->orderBy('product_name')
            ->paginate(10)
            ->withQueryString();

        $summary = DB::query()
            ->fromSub($this->buildMonitoringDetailQuery($filters), 'x')
            ->selectRaw("
                COUNT(DISTINCT product_id)::int as total_product,
                COALESCE(SUM(quantity), 0)::int as total_qty,

                COALESCE(SUM(CASE WHEN scope_type = 'warehouse' THEN quantity ELSE 0 END), 0)::int as warehouse_qty,
                COALESCE(SUM(CASE WHEN scope_type = 'store' THEN quantity ELSE 0 END), 0)::int as store_qty,

                COALESCE(MAX(total_aging_days), 0)::int as max_aging_days,

                COALESCE(SUM(CASE WHEN total_aging_days BETWEEN 0 AND 30 THEN quantity ELSE 0 END), 0)::int as bucket_0_30,
                COALESCE(SUM(CASE WHEN total_aging_days BETWEEN 31 AND 90 THEN quantity ELSE 0 END), 0)::int as bucket_31_90,
                COALESCE(SUM(CASE WHEN total_aging_days BETWEEN 91 AND 180 THEN quantity ELSE 0 END), 0)::int as bucket_91_180,
                COALESCE(SUM(CASE WHEN total_aging_days BETWEEN 181 AND 365 THEN quantity ELSE 0 END), 0)::int as bucket_181_365,
                COALESCE(SUM(CASE WHEN total_aging_days > 365 THEN quantity ELSE 0 END), 0)::int as bucket_gt_365
            ")
            ->first();

        $topAgingStore = DB::query()
            ->fromSub($this->buildMonitoringDetailQuery($filters), 'x')
            ->where('scope_type', 'store')
            ->orderByDesc('total_aging_days')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get();

        $topAgingWarehouse = DB::query()
            ->fromSub($this->buildMonitoringDetailQuery($filters), 'x')
            ->where('scope_type', 'warehouse')
            ->orderByDesc('total_aging_days')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get();

        $topAgingOverall = DB::query()
            ->fromSub($this->buildMonitoringDetailQuery($filters), 'x')
            ->orderByDesc('total_aging_days')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        $warehouses = Auth::user()
            ->warehouses()
            ->select('warehouses.id', 'warehouses.name')
            ->orderBy('warehouses.name')
            ->get();

        $stores = Auth::user()
            ->stores()
            ->select('stores.id', 'stores.name')
            ->orderBy('stores.name')
            ->get();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Product Aging',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows'              => $rows,
            'filters'           => $filters,
            'warehouses'        => $warehouses,
            'stores'            => $stores,
            'asOfDate'          => now(),
            'lastUpdate'        => now()->format('d-m-Y H:i:s'),
            'summary'           => $summary,
            'topAgingStore'     => $topAgingStore,
            'topAgingWarehouse' => $topAgingWarehouse,
            'topAgingOverall'   => $topAgingOverall,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function detail(Request $request, string $productId)
    {
        if (!Str::isUuid($productId)) {
            return response()->json([
                'product' => null,
                'rows'    => [],
            ]);
        }

        $filters = $this->getFilters($request);

        $rows = DB::query()
            ->fromSub($this->buildMonitoringDetailQuery($filters), 'product_aging_detail')
            ->where('product_id', $productId)
            ->orderByDesc('total_aging_days')
            ->orderBy('scope_type')
            ->orderBy('warehouse_name')
            ->orderBy('store_name')
            ->orderBy('condition_status')
            ->get();

        $product = $rows->first();

        return response()->json([
            'product' => $product ? [
                'product_id'      => $product->product_id,
                'product_code'    => $product->product_code,
                'product_name'    => $product->product_name,
                'quantity_total'  => (int) $rows->sum('quantity'),
                'max_aging_days'  => (int) $rows->max('total_aging_days'),
            ] : null,

            'rows' => $rows->map(function ($row) {
                return [
                    'scope_type'                 => $row->scope_type,
                    'warehouse_name'             => $row->warehouse_name,
                    'store_name'                 => $row->store_name,
                    'condition_status'           => $row->condition_status,
                    'expired_date'               => $row->expired_date,
                    'warehouse_inbound_date'     => $row->warehouse_inbound_date,
                    'store_inbound_date'         => $row->store_inbound_date,
                    'warehouse_aging_label'      => $row->warehouse_aging_label,
                    'store_aging_label'          => $row->store_aging_label,
                    'total_aging_label'          => $row->total_aging_label,
                    'total_aging_start_display'  => $row->total_aging_start_display,
                    'as_of_now_display'          => $row->as_of_now_display,
                    'total_aging_days'           => (int) $row->total_aging_days,
                    'quantity'                   => (int) $row->quantity,
                ];
            })->values(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->buildMonitoringDetailQuery($filters)
            ->orderByDesc('total_aging_days')
            ->get();

        $filename = 'product-aging-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ProductAgingExport($rows), $filename);
    }

    private function getFilters(Request $request): array
    {
        $scope = trim((string) $request->query('scope', 'all'));

        if (!in_array($scope, ['all', 'warehouse', 'store'], true)) {
            $scope = 'all';
        }

        return [
            'search'       => trim((string) $request->query('search', '')),
            'scope'        => $scope,
            'status'       => trim((string) $request->query('status', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'store_id'     => trim((string) $request->query('store_id', '')),
            'aging_bucket' => trim((string) $request->query('aging_bucket', '')),
        ];
    }

    private function buildMonitoringDetailQuery(array $filters)
    {
        $user = Auth::user();

        $userWarehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
        $userStoreIds = $user->stores()->pluck('stores.id')->toArray();

        $warehouseInboundExpr = "COALESCE(wi.received_date, wi.created_at)";
        $storeInboundExpr = "COALESCE(sin.received_date, sin.created_at)";
        $totalAgingExpr = "COALESCE(sin.received_date, sin.created_at, wi.received_date, wi.created_at)";

        $warehouseQuery = DB::table('warehouse_items')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'warehouse_items.item_id')
                    ->where('i.status', '=', 'warehouse_stock');
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->join('warehouses as w', 'w.id', '=', 'warehouse_items.warehouse_id')
            ->leftJoin('warehouse_inbound_details as wid', 'wid.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as wi', 'wi.id', '=', 'wid.warehouse_inbound_id')
            ->whereIn('w.id', $userWarehouseIds)
            ->when($filters['warehouse_id'] !== '', function ($q) use ($filters) {
                $q->where('w.id', $filters['warehouse_id']);
            })
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($qq) use ($filters) {
                    $qq->where('p.name', 'ILIKE', '%' . $filters['search'] . '%')
                        ->orWhere('p.code', 'ILIKE', '%' . $filters['search'] . '%');
                });
            })
            ->when($filters['status'] !== '' && $filters['status'] !== 'all', function ($q) use ($filters) {
                $q->where('ic.status', $filters['status']);
            })
            ->selectRaw("
                'warehouse'::text as scope_type,
                w.id as warehouse_id,
                w.name as warehouse_name,
                NULL::uuid as store_id,
                NULL::text as store_name,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COALESCE(ic.status::text, '-') as condition_status,
                i.expired_date as expired_date,
                {$warehouseInboundExpr} as warehouse_inbound_date,
                NULL::timestamp as store_inbound_date,
                {$warehouseInboundExpr} as total_aging_date,
                COUNT(warehouse_items.id)::int as quantity
            ")
            ->groupByRaw("
                w.id, w.name,
                p.id, p.code, p.name,
                COALESCE(ic.status::text, '-'),
                i.expired_date,
                {$warehouseInboundExpr}
            ");

        $storeQuery = DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->where('i.status', '=', 'store_stock');
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->leftJoin('store_inbound_details as sid', 'sid.item_id', '=', 'si.item_id')
            ->leftJoin('store_inbounds as sin', 'sin.id', '=', 'sid.store_inbound_id')
            ->leftJoin('warehouse_inbound_details as wid', 'wid.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as wi', 'wi.id', '=', 'wid.warehouse_inbound_id')
            ->where('si.category', 'inventory')
            ->where('i.status', 'store_stock')
            ->whereIn('s.id', $userStoreIds)
            ->when($filters['store_id'] !== '', function ($q) use ($filters) {
                $q->where('s.id', $filters['store_id']);
            })
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($qq) use ($filters) {
                    $qq->where('p.name', 'ILIKE', '%' . $filters['search'] . '%')
                        ->orWhere('p.code', 'ILIKE', '%' . $filters['search'] . '%');
                });
            })
            ->when($filters['status'] !== '' && $filters['status'] !== 'all', function ($q) use ($filters) {
                $q->where('ic.status', $filters['status']);
            })
            ->selectRaw("
                'store'::text as scope_type,
                NULL::uuid as warehouse_id,
                NULL::text as warehouse_name,
                s.id as store_id,
                s.name as store_name,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COALESCE(ic.status::text, '-') as condition_status,
                i.expired_date as expired_date,
                {$warehouseInboundExpr} as warehouse_inbound_date,
                {$storeInboundExpr} as store_inbound_date,
                {$totalAgingExpr} as total_aging_date,
                COUNT(si.id)::int as quantity
            ")
            ->groupByRaw("
                s.id, s.name,
                p.id, p.code, p.name,
                COALESCE(ic.status::text, '-'),
                i.expired_date,
                {$warehouseInboundExpr},
                {$storeInboundExpr},
                {$totalAgingExpr}
            ");

        $baseUnion = match ($filters['scope']) {
            'warehouse' => $warehouseQuery,
            'store' => $storeQuery,
            default => $warehouseQuery->unionAll($storeQuery),
        };

        $finalQuery = DB::query()
            ->fromSub($baseUnion, 'aging_monitor')
            ->selectRaw("
                scope_type,
                warehouse_id,
                warehouse_name,
                store_id,
                store_name,
                product_id,
                product_code,
                product_name,
                condition_status,
                expired_date,
                warehouse_inbound_date,
                store_inbound_date,
                total_aging_date,
                quantity,

                CASE
                    WHEN warehouse_inbound_date IS NULL THEN '-'
                    WHEN AGE(NOW(), warehouse_inbound_date) < INTERVAL '1 month'
                        THEN DATE_PART('day', AGE(NOW(), warehouse_inbound_date)) || ' hari'
                    WHEN AGE(NOW(), warehouse_inbound_date) < INTERVAL '1 year'
                        THEN DATE_PART('month', AGE(NOW(), warehouse_inbound_date)) || ' bulan ' ||
                             DATE_PART('day', AGE(NOW(), warehouse_inbound_date)) || ' hari'
                    ELSE
                        DATE_PART('year', AGE(NOW(), warehouse_inbound_date)) || ' tahun ' ||
                        DATE_PART('month', AGE(NOW(), warehouse_inbound_date)) || ' bulan ' ||
                        DATE_PART('day', AGE(NOW(), warehouse_inbound_date)) || ' hari'
                END as warehouse_aging_label,

                CASE
                    WHEN store_inbound_date IS NULL THEN '-'
                    WHEN AGE(NOW(), store_inbound_date) < INTERVAL '1 month'
                        THEN DATE_PART('day', AGE(NOW(), store_inbound_date)) || ' hari'
                    WHEN AGE(NOW(), store_inbound_date) < INTERVAL '1 year'
                        THEN DATE_PART('month', AGE(NOW(), store_inbound_date)) || ' bulan ' ||
                             DATE_PART('day', AGE(NOW(), store_inbound_date)) || ' hari'
                    ELSE
                        DATE_PART('year', AGE(NOW(), store_inbound_date)) || ' tahun ' ||
                        DATE_PART('month', AGE(NOW(), store_inbound_date)) || ' bulan ' ||
                        DATE_PART('day', AGE(NOW(), store_inbound_date)) || ' hari'
                END as store_aging_label,

                CASE
                    WHEN total_aging_date IS NULL THEN '-'
                    WHEN AGE(NOW(), total_aging_date) < INTERVAL '1 month'
                        THEN DATE_PART('day', AGE(NOW(), total_aging_date)) || ' hari'
                    WHEN AGE(NOW(), total_aging_date) < INTERVAL '1 year'
                        THEN DATE_PART('month', AGE(NOW(), total_aging_date)) || ' bulan ' ||
                             DATE_PART('day', AGE(NOW(), total_aging_date)) || ' hari'
                    ELSE
                        DATE_PART('year', AGE(NOW(), total_aging_date)) || ' tahun ' ||
                        DATE_PART('month', AGE(NOW(), total_aging_date)) || ' bulan ' ||
                        DATE_PART('day', AGE(NOW(), total_aging_date)) || ' hari'
                END as total_aging_label,

                TO_CHAR(total_aging_date, 'DD Mon YYYY HH24:MI') as total_aging_start_display,
                TO_CHAR(NOW(), 'DD Mon YYYY HH24:MI') as as_of_now_display,
                COALESCE(DATE_PART('day', NOW() - total_aging_date), 0)::int as total_aging_days
            ");

        if ($filters['aging_bucket'] !== '') {
            $finalQuery->where(function ($q) use ($filters) {
                switch ($filters['aging_bucket']) {
                    case '0_30':
                        $q->whereRaw("COALESCE(DATE_PART('day', NOW() - total_aging_date), 0) BETWEEN 0 AND 30");
                        break;

                    case '31_90':
                        $q->whereRaw("COALESCE(DATE_PART('day', NOW() - total_aging_date), 0) BETWEEN 31 AND 90");
                        break;

                    case '91_180':
                        $q->whereRaw("COALESCE(DATE_PART('day', NOW() - total_aging_date), 0) BETWEEN 91 AND 180");
                        break;

                    case '181_365':
                        $q->whereRaw("COALESCE(DATE_PART('day', NOW() - total_aging_date), 0) BETWEEN 181 AND 365");
                        break;

                    case 'gt_365':
                        $q->whereRaw("COALESCE(DATE_PART('day', NOW() - total_aging_date), 0) > 365");
                        break;
                }
            });
        }

        return $finalQuery;
    }
}