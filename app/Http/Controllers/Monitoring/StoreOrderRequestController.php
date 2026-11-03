<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\StoreOrderRequestExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StoreOrderRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-store-order-requests')
            ->only(['index', 'export']);
    }

    public function index(Request $request)
    {
        return Inertia::render('Monitoring/Report', [
            'title' => 'Store Order Requests',
            'description' => 'Laporan monitoring.',
            'data' => $this->report($request),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.store-order-requests.export')],
        ]);
    }

    public function report(Request $request): array
    {
        $filters = $this->getFilters($request);

        $storesQuery = DB::table('stores')
            ->select('id', 'name');

        $this->applyDeletedAtFilter($storesQuery, 'stores');

        $stores = $storesQuery
            ->orderBy('name')
            ->get();

        $warehousesQuery = DB::table('warehouses')
            ->select('id', 'name');

        $this->applyDeletedAtFilter($warehousesQuery, 'warehouses');

        $warehouses = $warehousesQuery
            ->orderBy('name')
            ->get();

        $baseQuery = $this->buildRowsQuery(
            startDate: $filters['start_date'],
            endDate: $filters['end_date'],
            storeId: $filters['store_id'],
            warehouseId: $filters['warehouse_id'],
            status: $filters['status'],
            keyword: $filters['keyword']
        );

        $summary = $this->getSummary(clone $baseQuery);

        $rows = $baseQuery
            ->paginate($filters['per_page'])
            ->withQueryString();

        $statuses = DB::table('store_orders')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return [
            'rows' => $rows,
            'summary' => $summary,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'statuses' => $statuses,

            'storeId' => $filters['store_id'],
            'warehouseId' => $filters['warehouse_id'],
            'status' => $filters['status'],
            'keyword' => $filters['keyword'],
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'perPage' => $filters['per_page'],
            'today' => $filters['today'],
            'lastUpdate' => now()->format('d-m-Y H:i:s'),
        ];
    }

    public function export(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Export default hari ini
        |--------------------------------------------------------------------------
        | Jika tanggal kosong, export mengikuti data halaman yaitu hari ini.
        */
        $filters = $this->getFilters($request);

        $rows = $this->buildRowsQuery(
            startDate: $filters['start_date'],
            endDate: $filters['end_date'],
            storeId: $filters['store_id'],
            warehouseId: $filters['warehouse_id'],
            status: $filters['status'],
            keyword: $filters['keyword']
        )->get();

        $storeName = '-';

        if ($filters['store_id']) {
            $storeName = DB::table('stores')
                ->where('id', $filters['store_id'])
                ->value('name') ?? '-';
        }

        $warehouseName = '-';

        if ($filters['warehouse_id']) {
            $warehouseName = DB::table('warehouses')
                ->where('id', $filters['warehouse_id'])
                ->value('name') ?? '-';
        }

        $filename = 'store-order-requests-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new StoreOrderRequestExport($rows, [
                'store_name' => $storeName,
                'warehouse_name' => $warehouseName,
                'status' => $filters['status'] ?: '-',
                'keyword' => $filters['keyword'] ?: '-',
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ]),
            $filename
        );
    }

    private function getFilters(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Default tanggal hari ini
        |--------------------------------------------------------------------------
        | Sebelumnya default 1 bulan terakhir.
        | Sekarang default hanya hari ini.
        */
        $today = now()->toDateString();

        $startDate = $this->normalizeDate($request->query('start_date')) ?: $today;
        $endDate = $this->normalizeDate($request->query('end_date')) ?: $today;

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'warehouse_id' => $this->normalizeNullableString($request->query('warehouse_id')),
            'status' => $this->normalizeNullableString($request->query('status')),
            'keyword' => $this->normalizeNullableString($request->query('keyword')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'per_page' => $perPage,
            'today' => $today,
        ];
    }

    private function buildRowsQuery(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $warehouseId = null,
        ?string $status = null,
        ?string $keyword = null
    ): Builder {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $query = DB::table('store_orders as so')
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'so.warehouse_id')
            ->leftJoin('store_order_details as sod', 'sod.store_order_id', '=', 'so.id')
            ->leftJoin('products as p', 'p.id', '=', 'sod.product_id')
            ->whereBetween(DB::raw('COALESCE(so.requested_at, so.created_at)'), [$start, $end])
            ->select([
                'so.id as store_order_id',
                'so.order_number',
                'so.store_id',
                'so.warehouse_id',
                'so.requested_at',
                'so.approved_at',
                'so.approved_by',
                'so.status',
                'so.created_by',
                'so.created_at',
                'so.updated_at',
                'so.date',
                'so.shipping_number',

                DB::raw("COALESCE(s.name, '-') as store_name"),
                DB::raw("COALESCE(s.code, '-') as store_code"),
                DB::raw("COALESCE(w.name, '-') as warehouse_name"),

                DB::raw("COALESCE(p.code, '-') as product_code"),
                DB::raw("COALESCE(p.name, '-') as product_name"),

                DB::raw('COALESCE(sod.product_price, 0)::numeric as product_price'),
                DB::raw('COALESCE(sod.requested_qty, 0)::int as requested_qty'),
                DB::raw('COALESCE(sod.approved_qty, 0)::int as approved_qty'),
                DB::raw('COALESCE(sod.received_qty, 0)::int as received_qty'),
                DB::raw("COALESCE(sod.note, '-') as note"),

                DB::raw('COALESCE(sod.requested_qty, 0) * COALESCE(sod.product_price, 0) as requested_total'),
                DB::raw('COALESCE(sod.approved_qty, 0) * COALESCE(sod.product_price, 0) as approved_total'),
                DB::raw('COALESCE(sod.received_qty, 0) * COALESCE(sod.product_price, 0) as received_total'),
            ])
            ->orderByDesc(DB::raw('COALESCE(so.requested_at, so.created_at)'))
            ->orderBy('so.order_number')
            ->orderBy('p.name');

        $this->applyDeletedAtFilter($query, 'stores', 's');

        if ($storeId) {
            $query->where('so.store_id', $storeId);
        }

        if ($warehouseId) {
            $query->where('so.warehouse_id', $warehouseId);
        }

        if ($status) {
            $query->where('so.status', $status);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('so.order_number', 'ILIKE', "%{$keyword}%")
                    ->orWhere('so.shipping_number', 'ILIKE', "%{$keyword}%")
                    ->orWhere('s.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('s.code', 'ILIKE', "%{$keyword}%")
                    ->orWhere('w.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('p.code', 'ILIKE', "%{$keyword}%")
                    ->orWhere('p.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('sod.note', 'ILIKE', "%{$keyword}%");
            });
        }

        return $query;
    }

    private function getSummary(Builder $query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->selectRaw('
                COUNT(DISTINCT x.store_order_id)::int as total_orders,
                COUNT(*)::int as total_rows,
                COALESCE(SUM(x.requested_qty), 0)::int as total_requested_qty,
                COALESCE(SUM(x.approved_qty), 0)::int as total_approved_qty,
                COALESCE(SUM(x.received_qty), 0)::int as total_received_qty,
                COALESCE(SUM(x.requested_total), 0)::numeric as total_requested_amount,
                COALESCE(SUM(x.approved_total), 0)::numeric as total_approved_amount,
                COALESCE(SUM(x.received_total), 0)::numeric as total_received_amount
            ')
            ->first();
    }

    private function normalizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $date = trim($date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            [$day, $month, $year] = explode('/', $date);

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function applyDeletedAtFilter($query, string $table, ?string $alias = null): void
    {
        if (! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        $column = $alias ? "{$alias}.deleted_at" : 'deleted_at';

        $query->whereNull($column);
    }
}
