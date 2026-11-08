<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\StoreReturnRequestExport;
use App\Http\Controllers\Controller;
use App\Support\Concerns\NormalizesDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StoreReturnRequestController extends Controller
{
    use NormalizesDate;

    public function index(Request $request)
    {
        return Inertia::render('Monitoring/Report', [
            'title' => 'Store Return Requests',
            'description' => 'Laporan monitoring.',
            'data' => $this->report($request),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.store-return-requests.export')],
        ]);
    }

    public function report(Request $request): array
    {
        $filters = $this->getFilters($request);

        $query = $this->buildRowsQuery($filters);

        $summary = $this->getSummary(clone $query);

        $rows = $query
            ->paginate($filters['per_page'])
            ->withQueryString();

        $storesQuery = DB::table('stores')
            ->select('id', 'name');

        $this->applyDeletedAtFilter($storesQuery, 'stores');

        $stores = $storesQuery
            ->orderBy('name')
            ->get();

        $statusList = [
            '' => 'Semua',
            'draft' => 'Draft',
            'requested' => 'Requested',
            'pending_approval' => 'Pending Approval',
            'partial_approved' => 'Partial Approved',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'packing' => 'Packing',
            'shipped' => 'Shipped',
            'received' => 'Received',
            'close' => 'Close',
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'q' => $filters['q'],
            'status' => $filters['status'],
            'storeId' => $filters['store_id'],
            'stores' => $stores,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
            'statusList' => $statusList,
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
        | Jika user export tanpa filter tanggal, maka export juga mengambil data
        | hari ini, sama seperti halaman index.
        */
        $filters = $this->getFilters($request);

        $request->merge([
            'q' => $filters['q'],
            'status' => $filters['status'],
            'store_id' => $filters['store_id'],
            'from' => $filters['from'],
            'to' => $filters['to'],
        ]);

        $filename = 'monitoring-store-return-requests-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new StoreReturnRequestExport($request), $filename);
    }

    private function getFilters(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Default data hari ini
        |--------------------------------------------------------------------------
        | Sebelumnya default dari awal bulan sampai hari ini.
        | Sekarang default hanya hari ini.
        */
        $today = now()->toDateString();

        $from = $this->normalizeDate($request->query('from')) ?: $today;
        $to = $this->normalizeDate($request->query('to')) ?: $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->has('status')
                ? $this->normalizeNullableString($request->query('status'))
                : '',
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
            'today' => $today,
        ];
    }

    private function buildRowsQuery(array $filters)
    {
        $q = $filters['q'];
        $status = $filters['status'];
        $storeId = $filters['store_id'];
        $from = $filters['from'];
        $to = $filters['to'];

        $hasWarehouseId = Schema::hasColumn('store_returns', 'warehouse_id');

        $detailAgg = DB::table('store_return_details as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->selectRaw("
                d.store_return_id,
                COUNT(*)::int as total_lines,
                COUNT(DISTINCT i.product_id)::int as total_products,
                STRING_AGG(
                    DISTINCT COALESCE(p.name, '-'),
                    CHR(10) ORDER BY COALESCE(p.name, '-')
                ) as product_names,
                COALESCE(SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END), 0)::int as qty_approved
            ")
            ->groupBy('d.store_return_id');

        $query = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->leftJoin('users as ua', 'ua.id', '=', 'r.approved_by')
            ->when($hasWarehouseId, function ($query) {
                $query->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id');
            })
            ->leftJoinSub($detailAgg, 'da', function ($join) {
                $join->on('da.store_return_id', '=', 'r.id');
            })
            ->whereBetween(DB::raw('r.created_at::date'), [$from, $to])
            ->select([
                'r.id',
                'r.return_number',
                'r.status',
                'r.created_at',
                'r.approved_at',
                'r.rejected_at',
                'r.store_id',
                DB::raw("COALESCE(s.name, '-') as store_name"),
                DB::raw("COALESCE(ua.name, '-') as approved_by_name"),
                DB::raw("COALESCE(r.note_store, '-') as note_store"),
                DB::raw("COALESCE(r.note_warehouse, '-') as note_warehouse"),
                DB::raw('COALESCE(da.total_lines, 0)::int as total_lines'),
                DB::raw('COALESCE(da.total_products, 0)::int as total_products'),
                DB::raw("COALESCE(da.product_names, '-') as product_names"),
                DB::raw('COALESCE(da.qty_approved, 0)::int as qty_approved'),
            ]);

        if ($hasWarehouseId) {
            $query->addSelect([
                'r.warehouse_id',
                DB::raw("COALESCE(w.name, '-') as warehouse_name"),
            ]);
        } else {
            $query->addSelect([
                DB::raw('NULL::uuid as warehouse_id'),
                DB::raw("'-'::text as warehouse_name"),
            ]);
        }

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($wq) use ($like, $hasWarehouseId) {
                $wq->where('r.return_number', 'ILIKE', $like)
                    ->orWhere('s.name', 'ILIKE', $like)
                    ->orWhere('r.note_store', 'ILIKE', $like)
                    ->orWhere('r.note_warehouse', 'ILIKE', $like);

                if ($hasWarehouseId) {
                    $wq->orWhere('w.name', 'ILIKE', $like);
                }
            });
        }

        if ($status !== '' && strtolower((string) $status) !== 'all') {
            $query->where('r.status', $status);
        }

        if ($storeId) {
            $query->where('r.store_id', $storeId);
        }

        $this->applyDeletedAtFilter($query, 'stores', 's');

        return $query->orderByDesc('r.created_at');
    }

    private function getSummary($query)
    {
        return DB::query()
            ->fromSub($query, 'x')
            ->selectRaw("
                COUNT(DISTINCT x.id)::int as total_returns,
                COALESCE(SUM(x.total_products), 0)::int as total_products,
                COALESCE(SUM(x.total_lines), 0)::int as total_lines,
                COALESCE(SUM(x.qty_approved), 0)::int as total_qty_approved,

                COUNT(DISTINCT CASE WHEN x.status = 'approved' THEN x.id END)::int as total_approved,
                COUNT(DISTINCT CASE WHEN x.status = 'rejected' THEN x.id END)::int as total_rejected,
                COUNT(DISTINCT CASE WHEN x.status IN ('requested', 'pending_approval') THEN x.id END)::int as total_pending,
                COUNT(DISTINCT CASE WHEN x.status IN ('received', 'close') THEN x.id END)::int as total_done
            ")
            ->first();
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? '' : $value;
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
