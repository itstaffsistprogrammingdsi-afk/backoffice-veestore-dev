<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\MonitoringVoucherRequestExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringVoucherRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $rowsQuery = $this->baseQuery($filters);

        $summary = DB::query()
            ->fromSub(clone $rowsQuery, 'x')
            ->selectRaw("
                COUNT(*)::int AS total_request,
                COUNT(*) FILTER (WHERE x.status = 'pending')::int AS total_pending,
                COUNT(*) FILTER (WHERE x.status = 'approved')::int AS total_approved,
                COUNT(*) FILTER (WHERE x.status = 'rejected')::int AS total_rejected,
                COALESCE(SUM(x.total_items), 0)::int AS total_items,
                COUNT(DISTINCT x.store_id) FILTER (WHERE x.store_id IS NOT NULL)::int AS total_store,
                COUNT(*) FILTER (WHERE x.voucher_id IS NOT NULL)::int AS total_voucher_created,
                COALESCE(SUM(x.usage_count), 0)::int AS total_usage
            ")
            ->first();

        $statusChartRows = DB::query()
            ->fromSub(clone $rowsQuery, 'x')
            ->selectRaw("
                x.status_label AS label,
                COUNT(*)::int AS total
            ")
            ->groupBy('x.status_label')
            ->orderByDesc('total')
            ->get();

        $storeChartRows = DB::query()
            ->fromSub(clone $rowsQuery, 'x')
            ->selectRaw("
                COALESCE(x.store_name, 'Store Tidak Diketahui') AS label,
                COUNT(*)::int AS total_request,
                COUNT(*) FILTER (WHERE x.status = 'pending')::int AS pending_request,
                COUNT(*) FILTER (WHERE x.status = 'approved')::int AS approved_request,
                COUNT(*) FILTER (WHERE x.status = 'rejected')::int AS rejected_request,
                COALESCE(SUM(x.total_items), 0)::int AS total_items
            ")
            ->groupBy('label')
            ->orderByDesc('total_request')
            ->limit(15)
            ->get();

        $dailyRows = DB::query()
            ->fromSub(clone $rowsQuery, 'x')
            ->selectRaw("
                DATE(x.request_date) AS tanggal,
                TO_CHAR(DATE(x.request_date), 'DD Mon') AS label,
                COUNT(*)::int AS total_request,
                COUNT(*) FILTER (WHERE x.status = 'pending')::int AS pending_request,
                COUNT(*) FILTER (WHERE x.status = 'approved')::int AS approved_request,
                COUNT(*) FILTER (WHERE x.status = 'rejected')::int AS rejected_request
            ")
            ->groupByRaw('DATE(x.request_date)')
            ->orderBy('tanggal')
            ->get();

        $rows = $rowsQuery
            ->orderByDesc('request_date')
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $requestIds = $rows->getCollection()
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $detailItems = $this->detailItems($requestIds);

        $stores = DB::table('stores')
            ->when(Schema::hasColumn('stores', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = collect([
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ]);

        return view('content.monitoring.request-voucher.index', [
            'rows' => $rows,
            'detailItems' => $detailItems,
            'summary' => $summary,
            'statusChartRows' => $statusChartRows,
            'storeChartRows' => $storeChartRows,
            'dailyRows' => $dailyRows,
            'stores' => $stores,
            'statuses' => $statuses,
            'filters' => $filters,

            'q' => $filters['q'],
            'status' => $filters['status'],
            'storeId' => $filters['store_id'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
            'autoRefresh' => $filters['auto_refresh'],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->baseQuery($this->filters($request))
            ->orderByDesc('request_date')
            ->orderByDesc('created_at')
            ->get();

        return Excel::download(
            new MonitoringVoucherRequestExport($rows),
            'monitoring-request-voucher-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $status = $request->query('status');

        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = null;
        }

        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => $status,
            'store_id' => $request->query('store_id'),
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
            'auto_refresh' => $request->boolean('auto_refresh'),
        ];
    }

    private function baseQuery(array $filters)
    {
        $q = $filters['q'];

        $detailCount = DB::table('store_voucher_request_details as d')
            ->selectRaw('d.store_voucher_request_id, COUNT(DISTINCT d.id)::int AS total_items')
            ->groupBy('d.store_voucher_request_id');

        $query = DB::table('store_voucher_requests as svr')
            ->leftJoin('stores as s', 's.id', '=', 'svr.store_id')
            ->leftJoin('users as req_user', 'req_user.id', '=', 'svr.created_by')
            ->leftJoin('users as app_user', 'app_user.id', '=', 'svr.approved_by')
            ->leftJoinSub($detailCount, 'dc', function ($join) {
                $join->on('dc.store_voucher_request_id', '=', 'svr.id');
            })
            ->leftJoin('vouchers as v', 'v.store_voucher_request_id', '=', 'svr.id')
            ->selectRaw("
                svr.id,
                svr.request_number,
                svr.store_id,
                svr.request_date,
                LOWER((svr.status)::text) AS status,
                svr.approved_by,
                svr.approved_at,
                svr.notes,
                svr.customer_id,
                svr.store_visit_payment_id,
                svr.created_by,
                svr.created_at,
                svr.updated_at,

                s.name AS store_name,
                req_user.name AS requested_by_name,
                app_user.name AS approved_by_name,

                v.id AS voucher_id,
                v.code AS voucher_code,
                v.name AS voucher_name,
                v.description AS voucher_description,
                (v.type)::text AS voucher_type,
                COALESCE(v.value, 0) AS voucher_value,
                COALESCE(v.max_discount, 0) AS voucher_max_discount,
                COALESCE(v.min_purchase, 0) AS voucher_min_purchase,
                v.valid_from,
                v.valid_until,
                COALESCE(v.usage_limit, 0)::int AS usage_limit,
                COALESCE(v.usage_count, 0)::int AS usage_count,
                v.is_active AS voucher_is_active,
                (v.context)::text AS voucher_context,

                COALESCE(dc.total_items, 0)::int AS total_items,

                CASE LOWER((svr.status)::text)
                    WHEN 'pending' THEN 'Pending'
                    WHEN 'approved' THEN 'Approved'
                    WHEN 'rejected' THEN 'Rejected'
                    ELSE COALESCE((svr.status)::text, '-')
                END AS status_label
            ")
            ->when($q !== '', function ($builder) use ($q) {
                $like = "%{$q}%";

                $builder->where(function ($sub) use ($like) {
                    $sub->where('svr.request_number', 'ILIKE', $like)
                        ->orWhere('s.name', 'ILIKE', $like)
                        ->orWhere('req_user.name', 'ILIKE', $like)
                        ->orWhere('app_user.name', 'ILIKE', $like)
                        ->orWhere('v.code', 'ILIKE', $like)
                        ->orWhere('v.name', 'ILIKE', $like)
                        ->orWhere('svr.notes', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(svr.id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($filters['status'], fn ($builder) => $builder->where('svr.status', $filters['status']))
            ->when($filters['store_id'], fn ($builder) => $builder->where('svr.store_id', $filters['store_id']))
            ->whereBetween(DB::raw('svr.request_date::date'), [$filters['from'], $filters['to']]);

        return $query;
    }

    private function detailItems(array $requestIds)
    {
        if (empty($requestIds)) {
            return collect();
        }

        return DB::table('store_voucher_request_details as svrd')
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
}
