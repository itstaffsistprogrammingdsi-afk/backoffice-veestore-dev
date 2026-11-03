<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\VoucherRejectReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class VoucherRejectReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->buildRowsQuery($filters)
            ->orderByDesc('svr.request_date')
            ->orderByDesc('svr.created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $requestIds = $rows->getCollection()
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $detailItems = collect();

        if (!empty($requestIds)) {
            $detailItems = DB::table('store_voucher_request_details as svrd')
                ->leftJoin('products as p', 'p.id', '=', 'svrd.product_id')
                ->leftJoin('items as i', 'i.id', '=', 'svrd.item_id')
                ->whereIn('svrd.store_voucher_request_id', $requestIds)
                ->select([
                    'svrd.id',
                    'svrd.store_voucher_request_id',
                    'svrd.product_id',
                    'svrd.item_id',
                    'svrd.created_at',
                    'p.code as product_code',
                    'p.name as product_name',
                    'i.status as item_status',
                ])
                ->orderBy('svrd.created_at')
                ->get()
                ->groupBy('store_voucher_request_id');
        }

        $summary = $this->getSummary($filters);

        $stores = DB::table('stores')
            ->when(Schema::hasColumn('stores', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = collect(['pending', 'approved', 'rejected']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Voucher Reject Report',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows' => $rows,
            'detailItems' => $detailItems,
            'summary' => $summary,

            'q' => $filters['q'],
            'storeId' => $filters['store_id'],
            'status' => $filters['status'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
            'lastUpdate' => now()->format('d-m-Y H:i:s'),

            'stores' => $stores,
            'statuses' => $statuses,
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.voucher-reject-report.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'voucher-reject-report-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new VoucherRejectReportExport($request), $filename);
    }

    private function getFilters(Request $request): array
    {
        $status = $this->normalizeNullableString($request->query('status'));
        $allowedStatuses = ['pending', 'approved', 'rejected'];

        if ($status && !in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'status' => $status,
            'from' => $this->normalizeDate($request->query('from')) ?: now()->startOfMonth()->toDateString(),
            'to' => $this->normalizeDate($request->query('to')) ?: now()->toDateString(),
            'per_page' => $perPage,
        ];
    }

    private function buildRowsQuery(array $filters)
    {
        $query = DB::table('store_voucher_requests as svr')
            ->leftJoin('stores as s', 's.id', '=', 'svr.store_id')
            ->leftJoin('vouchers as v', 'v.store_voucher_request_id', '=', 'svr.id')
            ->select([
                'svr.id',
                'svr.request_number',
                'svr.store_id',
                'svr.request_date',
                'svr.status',
                'svr.approved_by',
                'svr.approved_at',
                'svr.notes',
                'svr.customer_id',
                'svr.store_visit_payment_id',
                'svr.created_by',
                'svr.created_at',
                'svr.updated_at',

                's.name as store_name',

                'v.id as voucher_id',
                'v.code as voucher_code',
                'v.name as voucher_name',
                'v.description as voucher_description',
                'v.type as voucher_type',
                'v.value as voucher_value',
                'v.max_discount as voucher_max_discount',
                'v.min_purchase as voucher_min_purchase',
                'v.valid_from',
                'v.valid_until',
                'v.usage_limit',
                'v.usage_count',
                'v.is_active',
                'v.context',

                DB::raw("
                    (
                        SELECT COUNT(*)
                        FROM store_voucher_request_details svrd_count
                        WHERE svrd_count.store_voucher_request_id = svr.id
                    )::int as total_items
                "),

                DB::raw("
                    CASE svr.status::text
                        WHEN 'pending' THEN 'Pending'
                        WHEN 'approved' THEN 'Approved'
                        WHEN 'rejected' THEN 'Rejected'
                        ELSE COALESCE(svr.status::text, '-')
                    END as status_label
                "),
            ]);

        $this->applyDeletedAtFilter($query, 'store_voucher_requests', 'svr');

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function getSummary(array $filters)
    {
        $query = DB::table('store_voucher_requests as svr')
            ->leftJoin('stores as s', 's.id', '=', 'svr.store_id')
            ->leftJoin('vouchers as v', 'v.store_voucher_request_id', '=', 'svr.id');

        $this->applyDeletedAtFilter($query, 'store_voucher_requests', 'svr');

        $this->applyFilters($query, $filters);

        return $query
            ->selectRaw("
                COUNT(DISTINCT svr.id)::int as total_request,

                COUNT(DISTINCT CASE
                    WHEN svr.status::text = 'pending'
                    THEN svr.id
                END)::int as total_pending,

                COUNT(DISTINCT CASE
                    WHEN svr.status::text = 'approved'
                    THEN svr.id
                END)::int as total_approved,

                COUNT(DISTINCT CASE
                    WHEN svr.status::text = 'rejected'
                    THEN svr.id
                END)::int as total_rejected,

                COUNT(DISTINCT CASE
                    WHEN v.id IS NOT NULL
                    THEN v.id
                END)::int as total_voucher,

                COALESCE(SUM(COALESCE(v.usage_count, 0)), 0)::int as total_usage_count,

                COALESCE((
                    SELECT COUNT(*)
                    FROM store_voucher_request_details svrd_summary
                    WHERE svrd_summary.store_voucher_request_id IN (
                        SELECT svr2.id
                        FROM store_voucher_requests svr2
                        LEFT JOIN stores s2 ON s2.id = svr2.store_id
                        LEFT JOIN vouchers v2 ON v2.store_voucher_request_id = svr2.id
                        WHERE 1 = 1
                    )
                ), 0)::int as total_items_dummy
            ")
            ->first();
    }

    private function applyFilters($query, array $filters): void
    {
        $q = $filters['q'] ?? '';
        $storeId = $filters['store_id'] ?? null;
        $status = $filters['status'] ?? null;
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('svr.request_number', 'ILIKE', $like)
                    ->orWhere('s.name', 'ILIKE', $like)
                    ->orWhere('svr.notes', 'ILIKE', $like)
                    ->orWhere('v.code', 'ILIKE', $like)
                    ->orWhere('v.name', 'ILIKE', $like)
                    ->orWhere(DB::raw('CAST(svr.id AS TEXT)'), 'ILIKE', $like)
                    ->orWhereExists(function ($detailQ) use ($like) {
                        $detailQ->select(DB::raw(1))
                            ->from('store_voucher_request_details as svrd_search')
                            ->leftJoin('products as p_search', 'p_search.id', '=', 'svrd_search.product_id')
                            ->whereColumn('svrd_search.store_voucher_request_id', 'svr.id')
                            ->where(function ($inner) use ($like) {
                                $inner->where('p_search.code', 'ILIKE', $like)
                                    ->orWhere('p_search.name', 'ILIKE', $like)
                                    ->orWhere(DB::raw('CAST(svrd_search.item_id AS TEXT)'), 'ILIKE', $like);
                            });
                    });
            });
        }

        if ($storeId) {
            $query->where('svr.store_id', $storeId);
        }

        if ($status) {
            $query->where('svr.status', $status);
        }

        $query->whereBetween(DB::raw('svr.request_date::date'), [$from, $to]);
    }

    private function normalizeDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim((string) $date);

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
        if (!Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        $column = $alias ? "{$alias}.deleted_at" : 'deleted_at';

        $query->whereNull($column);
    }
}
