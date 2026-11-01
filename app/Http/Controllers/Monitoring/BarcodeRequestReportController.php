<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\BarcodeRequestReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class BarcodeRequestReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->buildRowsQuery($filters)
            ->orderByDesc('slpr.created_at')
            ->orderBy('slpr.id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = $this->getSummary($filters);
        $frequentRequesters = $this->getFrequentRequesters($filters);
        $frequentReprinters = $this->getFrequentReprinters($filters);

        $stores = DB::table('stores')
            ->when(Schema::hasColumn('stores', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = DB::table('users')
            ->when(Schema::hasColumn('users', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = DB::table('store_label_print_requests')
            ->select(DB::raw('status::text as status'))
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('content.monitoring.barcode-request-report.index', [
            'rows' => $rows,
            'summary' => $summary,
            'frequentRequesters' => $frequentRequesters,
            'frequentReprinters' => $frequentReprinters,

            'q' => $filters['q'],
            'status' => $filters['status'],
            'storeId' => $filters['store_id'],
            'requestedBy' => $filters['requested_by'],
            'printedBy' => $filters['printed_by'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
            'lastUpdate' => now()->format('d-m-Y H:i:s'),

            'stores' => $stores,
            'users' => $users,
            'statuses' => $statuses,
        ]);
    }

    public function export(Request $request)
    {
        $filename = 'monitoring-request-cetak-label-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new BarcodeRequestReportExport($request), $filename);
    }

    private function getFilters(Request $request): array
    {
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $this->normalizeNullableString($request->query('status')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'requested_by' => $this->normalizeNullableString($request->query('requested_by')),
            'printed_by' => $this->normalizeNullableString($request->query('printed_by')),
            'from' => $this->normalizeDate($request->query('from')) ?: now()->startOfMonth()->toDateString(),
            'to' => $this->normalizeDate($request->query('to')) ?: now()->toDateString(),
            'per_page' => $perPage,
        ];
    }

    private function buildRowsQuery(array $filters)
    {
        $printCounts = DB::table('store_label_print_histories')
            ->select('label_print_request_id', 'item_id')
            ->selectRaw('MAX(COALESCE(print_index, 0))::int as total_print_count')
            ->groupBy('label_print_request_id', 'item_id');

        $query = DB::table('store_label_print_requests as slpr')
            ->leftJoin('stores as s', 's.id', '=', 'slpr.store_id')
            ->leftJoin('users as req_user', 'req_user.id', '=', 'slpr.requested_by')
            ->leftJoin('users as app_user', 'app_user.id', '=', 'slpr.approved_by')
            ->leftJoin('store_label_print_request_items as slpri', 'slpri.label_print_request_id', '=', 'slpr.id')
            ->leftJoin('items as i', 'i.id', '=', 'slpri.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('store_label_print_histories as slph', function ($join) {
                $join->on('slph.label_print_request_id', '=', 'slpr.id')
                    ->on('slph.item_id', '=', 'slpri.item_id');
            })
            ->leftJoinSub($printCounts, 'print_counts', function ($join) {
                $join->on('print_counts.label_print_request_id', '=', 'slpr.id')
                    ->on('print_counts.item_id', '=', 'slpri.item_id');
            })
            ->leftJoin('users as print_user', 'print_user.id', '=', 'slph.printed_by')
            ->select([
                'slpr.id as request_id',
                'slpr.store_id',
                'slpr.requested_by',
                'slpr.status',
                'slpr.reason',
                'slpr.approved_by',
                'slpr.approved_at',
                'slpr.created_at as request_created_at',
                'slpr.updated_at as request_updated_at',

                's.name as store_name',

                'req_user.name as requested_by_name',
                'app_user.name as approved_by_name',
                'print_user.name as printed_by_name',

                'slpri.id as request_item_id',
                'slpri.item_id',
                'slpri.created_at as request_item_created_at',

                'i.product_id',
                'i.status as item_status',

                'p.name as product_name',
                'p.code as product_code',

                'slph.id as history_id',
                'slph.printed_by',
                'slph.print_index',
                'slph.printed_at',
                'slph.created_at as history_created_at',
                DB::raw('COALESCE(print_counts.total_print_count, 0)::int as total_print_count'),

                DB::raw("
                    CASE slpr.status::text
                        WHEN 'pending' THEN 'Pending'
                        WHEN 'approved' THEN 'Approved'
                        WHEN 'rejected' THEN 'Rejected'
                        WHEN 'printed' THEN 'Printed'
                        ELSE COALESCE(slpr.status::text, '-')
                    END as status_label
                "),
            ]);

        $this->applyDeletedAtFilter($query, 'store_label_print_requests', 'slpr');

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function getSummary(array $filters)
    {
        $detailRows = $this->buildRowsQuery($filters);

        $labelRows = DB::query()
            ->fromSub($detailRows, 'x')
            ->selectRaw('x.request_id, x.status, x.request_item_id')
            ->selectRaw('MAX(COALESCE(x.print_index, 0))::int as total_print_count')
            ->groupBy('x.request_id', 'x.status', 'x.request_item_id');

        $totalPrinter = DB::query()
            ->fromSub($this->buildRowsQuery($filters), 'x')
            ->whereNotNull('x.printed_by')
            ->distinct('x.printed_by')
            ->count('x.printed_by');

        $summary = DB::query()
            ->fromSub($labelRows, 'x')
            ->selectRaw("
                COUNT(*)::int as total_rows,

                COUNT(DISTINCT x.request_id)::int as total_request,

                COUNT(DISTINCT CASE
                    WHEN x.status::text = 'pending'
                    THEN x.request_id
                END)::int as total_pending,

                COUNT(DISTINCT CASE
                    WHEN x.status::text = 'approved'
                    THEN x.request_id
                END)::int as total_approved,

                COUNT(DISTINCT CASE
                    WHEN x.status::text = 'rejected'
                    THEN x.request_id
                END)::int as total_rejected,

                COUNT(DISTINCT CASE
                    WHEN x.request_item_id IS NOT NULL
                    THEN x.request_item_id
                END)::int as total_item,

                COALESCE(SUM(x.total_print_count), 0)::int as total_printed,

                COALESCE(SUM(GREATEST(x.total_print_count - 1, 0)), 0)::int as total_reprinted
            ")
            ->first();

        $summary->total_printer = $totalPrinter;

        return $summary;
    }

    private function getFrequentRequesters(array $filters)
    {
        return DB::query()
            ->fromSub($this->buildRowsQuery($filters), 'x')
            ->whereNotNull('x.requested_by')
            ->selectRaw('x.requested_by, MAX(x.requested_by_name) as user_name')
            ->selectRaw('COUNT(DISTINCT x.request_id)::int as request_count')
            ->selectRaw('COUNT(DISTINCT x.request_item_id)::int as item_count')
            ->groupBy('x.requested_by')
            ->orderByDesc('request_count')
            ->orderByDesc('item_count')
            ->limit(5)
            ->get();
    }

    private function getFrequentReprinters(array $filters)
    {
        return DB::query()
            ->fromSub($this->buildRowsQuery($filters), 'x')
            ->whereNotNull('x.printed_by')
            ->where('x.print_index', '>', 1)
            ->selectRaw('x.printed_by, MAX(x.printed_by_name) as user_name')
            ->selectRaw('COUNT(DISTINCT x.history_id)::int as reprint_event_count')
            ->selectRaw('COUNT(DISTINCT x.request_item_id)::int as reprinted_item_count')
            ->groupBy('x.printed_by')
            ->orderByDesc('reprint_event_count')
            ->orderByDesc('reprinted_item_count')
            ->limit(5)
            ->get();
    }

    private function applyFilters($query, array $filters): void
    {
        $q = $filters['q'] ?? '';
        $status = $filters['status'] ?? null;
        $storeId = $filters['store_id'] ?? null;
        $requestedBy = $filters['requested_by'] ?? null;
        $printedBy = $filters['printed_by'] ?? null;
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('s.name', 'ILIKE', $like)
                    ->orWhere('req_user.name', 'ILIKE', $like)
                    ->orWhere('app_user.name', 'ILIKE', $like)
                    ->orWhere('print_user.name', 'ILIKE', $like)
                    ->orWhere('p.name', 'ILIKE', $like)
                    ->orWhere('p.code', 'ILIKE', $like)
                    ->orWhere(DB::raw('slpr.status::text'), 'ILIKE', $like)
                    ->orWhere('slpr.reason', 'ILIKE', $like)
                    ->orWhere(DB::raw('CAST(slpr.id AS TEXT)'), 'ILIKE', $like)
                    ->orWhere(DB::raw('CAST(slpri.item_id AS TEXT)'), 'ILIKE', $like);
            });
        }

        if ($status) {
            $query->where('slpr.status', $status);
        }

        if ($storeId) {
            $query->where('slpr.store_id', $storeId);
        }

        if ($requestedBy) {
            $query->where('slpr.requested_by', $requestedBy);
        }

        if ($printedBy) {
            $query->where('slph.printed_by', $printedBy);
        }

        $query->whereBetween(DB::raw('slpr.created_at::date'), [$from, $to]);
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
