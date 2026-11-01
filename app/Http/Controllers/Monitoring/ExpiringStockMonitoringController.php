<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpiringStockMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $baseQuery = $this->buildDetailQuery($filters);

        $rows = DB::query()
            ->fromSub($baseQuery, 'expiring_stock')
            ->selectRaw("*, (expired_date::date - CURRENT_DATE)::int as days_to_expiry")
            ->orderBy('expired_date')
            ->orderByDesc('quantity')
            ->paginate(25)
            ->withQueryString();

        $summary = DB::query()
            ->fromSub($this->buildDetailQuery($filters), 'expiring_stock')
            ->selectRaw("
                COUNT(DISTINCT product_id)::int as total_product,
                COUNT(DISTINCT CONCAT(scope_type, '-', location_id))::int as total_location,
                COALESCE(SUM(quantity), 0)::int as total_quantity,
                COALESCE(SUM(CASE WHEN expired_date::date < CURRENT_DATE THEN quantity ELSE 0 END), 0)::int as expired_quantity,
                COALESCE(SUM(CASE WHEN expired_date::date BETWEEN CURRENT_DATE AND CURRENT_DATE + 7 THEN quantity ELSE 0 END), 0)::int as urgent_quantity,
                COALESCE(SUM(CASE WHEN expired_date::date BETWEEN CURRENT_DATE + 8 AND CURRENT_DATE + 30 THEN quantity ELSE 0 END), 0)::int as upcoming_quantity
            ")
            ->first();

        $storeChart = $this->buildProductChart($filters, 'store');
        $warehouseChart = $this->buildProductChart($filters, 'warehouse');

        $user = Auth::user();

        return view('content.monitoring.expiring-stock.index', [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'stores' => $user->stores()
                ->select('stores.id', 'stores.name')
                ->orderBy('stores.name')
                ->get(),
            'warehouses' => $user->warehouses()
                ->select('warehouses.id', 'warehouses.name')
                ->orderBy('warehouses.name')
                ->get(),
            'storeChart' => $storeChart,
            'warehouseChart' => $warehouseChart,
        ]);
    }

    private function filters(Request $request): array
    {
        $scope = (string) $request->query('scope', 'all');

        if (!in_array($scope, ['all', 'store', 'warehouse'], true)) {
            $scope = 'all';
        }

        $fromDate = $this->dateOrDefault($request->query('from_date'), now());
        $toDate = $this->dateOrDefault($request->query('to_date'), now()->addMonths(6));

        if ($toDate->lt($fromDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [
            'scope' => $scope,
            'search' => trim((string) $request->query('search', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
        ];
    }

    private function dateOrDefault(?string $value, Carbon $default): Carbon
    {
        if (!$value) {
            return $default->copy()->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return $default->copy()->startOfDay();
        }
    }

    private function buildDetailQuery(array $filters)
    {
        $user = Auth::user();
        $warehouseIds = $user->warehouses()->pluck('warehouses.id')->all();
        $storeIds = $user->stores()->pluck('stores.id')->all();

        $applyCommonFilters = function ($query, string $locationTable, string $locationColumn) use ($filters) {
            $query
                ->whereNotNull('i.expired_date')
                ->whereRaw('i.expired_date::date BETWEEN ? AND ?', [$filters['from_date'], $filters['to_date']])
                ->when($filters['search'] !== '', function ($q) use ($filters) {
                    $q->where(function ($nested) use ($filters) {
                        $nested->where('p.name', 'ILIKE', '%' . $filters['search'] . '%')
                            ->orWhere('p.code', 'ILIKE', '%' . $filters['search'] . '%');
                    });
                })
                ->when($locationColumn === 'warehouse_id' && $filters['warehouse_id'] !== '', function ($q) use ($filters, $locationTable) {
                    $q->where($locationTable . '.id', $filters['warehouse_id']);
                })
                ->when($locationColumn === 'store_id' && $filters['store_id'] !== '', function ($q) use ($filters, $locationTable) {
                    $q->where($locationTable . '.id', $filters['store_id']);
                });
        };

        $warehouseQuery = DB::table('warehouse_items')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'warehouse_items.item_id')
                    ->where('i.status', '=', 'warehouse_stock');
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('warehouses as w', 'w.id', '=', 'warehouse_items.warehouse_id')
            ->whereIn('w.id', $warehouseIds);

        $applyCommonFilters($warehouseQuery, 'w', 'warehouse_id');

        $warehouseQuery
            ->selectRaw("
                'warehouse'::text as scope_type,
                w.id as location_id,
                w.name as location_name,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                i.expired_date::date as expired_date,
                COUNT(DISTINCT warehouse_items.id)::int as quantity
            ")
            ->groupByRaw('w.id, w.name, p.id, p.code, p.name, i.expired_date::date');

        $storeQuery = DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->where('i.status', '=', 'store_stock');
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->where('si.category', 'inventory')
            ->whereIn('s.id', $storeIds);

        $applyCommonFilters($storeQuery, 's', 'store_id');

        $storeQuery
            ->selectRaw("
                'store'::text as scope_type,
                s.id as location_id,
                s.name as location_name,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                i.expired_date::date as expired_date,
                COUNT(DISTINCT si.id)::int as quantity
            ")
            ->groupByRaw('s.id, s.name, p.id, p.code, p.name, i.expired_date::date');

        return match ($filters['scope']) {
            'store' => $storeQuery,
            'warehouse' => $warehouseQuery,
            default => $warehouseQuery->unionAll($storeQuery),
        };
    }

    private function buildProductChart(array $filters, string $scope)
    {
        return DB::query()
            ->fromSub($this->buildDetailQuery($filters), 'expiring_stock')
            ->where('scope_type', $scope)
            ->selectRaw('product_id, product_code, product_name, MIN(expired_date)::date as nearest_expired_date, SUM(quantity)::int as quantity')
            ->groupBy('product_id', 'product_code', 'product_name')
            ->orderByDesc('quantity')
            ->orderBy('nearest_expired_date')
            ->limit(10)
            ->get();
    }
}
