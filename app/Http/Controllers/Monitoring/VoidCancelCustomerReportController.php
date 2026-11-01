<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\VoidCancelCustomerReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class VoidCancelCustomerReportController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $requestedBy = $request->query('requested_by');
        $authorizedBy = $request->query('authorized_by');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        // Show dihilangkan dari tampilan.
        // Pagination tetap 10 data per halaman.
        $perPage = 10;

        $query = $this->buildBaseQuery(
            q: $q,
            status: $status,
            storeId: $storeId,
            requestedBy: $requestedBy,
            authorizedBy: $authorizedBy,
            from: $from,
            to: $to
        )
            ->orderByDesc('vt.requested_at')
            ->orderByDesc('vt.id');

        $rows = $query->paginate($perPage)->withQueryString();

        $voidIds = $rows->pluck('id')->filter()->values();

        $detailItems = collect();

        if ($voidIds->isNotEmpty() && Schema::hasTable('void_transaction_items')) {
            if (Schema::hasColumn('void_transaction_items', 'item_id')) {
                $detailItems = DB::table('void_transaction_items as vti')
                    ->leftJoin('items as i', 'i.id', '=', 'vti.item_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
                    ->whereIn('vti.void_transaction_id', $voidIds)
                    ->select([
                        'vti.void_transaction_id',
                        'vti.item_id',
                        'pr.code as product_code',
                        'pr.name as product_name',
                        'i.status as item_status',
                    ])
                    ->orderBy('vti.void_transaction_id')
                    ->orderBy('vti.item_id')
                    ->get()
                    ->groupBy('void_transaction_id');
            } elseif (Schema::hasColumn('void_transaction_items', 'cart_detail_id')) {
                $detailItems = DB::table('void_transaction_items as vti')
                    ->leftJoin('store_cart_details as scd', 'scd.id', '=', 'vti.cart_detail_id')
                    ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
                    ->whereIn('vti.void_transaction_id', $voidIds)
                    ->select([
                        'vti.void_transaction_id',
                        'scd.item_id',
                        'pr.code as product_code',
                        'pr.name as product_name',
                        'i.status as item_status',
                    ])
                    ->orderBy('vti.void_transaction_id')
                    ->orderBy('scd.item_id')
                    ->get()
                    ->groupBy('void_transaction_id');
            } elseif (Schema::hasColumn('void_transaction_items', 'store_cart_detail_id')) {
                $detailItems = DB::table('void_transaction_items as vti')
                    ->leftJoin('store_cart_details as scd', 'scd.id', '=', 'vti.store_cart_detail_id')
                    ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
                    ->whereIn('vti.void_transaction_id', $voidIds)
                    ->select([
                        'vti.void_transaction_id',
                        'scd.item_id',
                        'pr.code as product_code',
                        'pr.name as product_name',
                        'i.status as item_status',
                    ])
                    ->orderBy('vti.void_transaction_id')
                    ->orderBy('scd.item_id')
                    ->get()
                    ->groupBy('void_transaction_id');
            }
        }

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = DB::table('void_transactions as vt')
            ->selectRaw('DISTINCT vt.status::text as status')
            ->whereNotNull('vt.status')
            ->orderBy('status')
            ->pluck('status');

        $summary = DB::query()
            ->fromSub(
                $this->buildDashboardBaseQuery(
                    q: $q,
                    status: $status,
                    storeId: $storeId,
                    requestedBy: $requestedBy,
                    authorizedBy: $authorizedBy,
                    from: $from,
                    to: $to
                ),
                'x'
            )
            ->selectRaw("
                COUNT(*) as total_void_request,

                SUM(
                    CASE
                        WHEN x.status::text IN ('approved', 'authorized') THEN 1
                        ELSE 0
                    END
                ) as total_approved,

                SUM(
                    CASE
                        WHEN x.status::text = 'rejected' THEN 1
                        ELSE 0
                    END
                ) as total_rejected,

                SUM(
                    CASE
                        WHEN x.status::text = 'requested' THEN 1
                        ELSE 0
                    END
                ) as total_requested,

                SUM(
                    CASE
                        WHEN x.status::text = 'cancelled' THEN 1
                        ELSE 0
                    END
                ) as total_cancelled,

                COALESCE(SUM(x.voided_amount), 0) as total_voided_amount,
                COALESCE(SUM(x.items_restored_count), 0) as total_items_restored
            ")
            ->first();

        $voidDailyRows = DB::query()
            ->fromSub(
                $this->buildDashboardBaseQuery(
                    q: $q,
                    status: $status,
                    storeId: $storeId,
                    requestedBy: $requestedBy,
                    authorizedBy: $authorizedBy,
                    from: $from,
                    to: $to
                ),
                'x'
            )
            ->selectRaw("
                x.request_date,
                COUNT(*) as total_request,
                COALESCE(SUM(x.voided_amount), 0) as total_voided_amount
            ")
            ->groupBy('x.request_date')
            ->orderBy('x.request_date')
            ->get();

        $voidStatusRows = DB::query()
            ->fromSub(
                $this->buildDashboardBaseQuery(
                    q: $q,
                    status: $status,
                    storeId: $storeId,
                    requestedBy: $requestedBy,
                    authorizedBy: $authorizedBy,
                    from: $from,
                    to: $to
                ),
                'x'
            )
            ->selectRaw("
                x.status_label,
                COUNT(*) as total_request,
                COALESCE(SUM(x.voided_amount), 0) as total_voided_amount
            ")
            ->groupBy('x.status_label')
            ->orderByDesc('total_request')
            ->get();

        $voidStoreRows = DB::query()
            ->fromSub(
                $this->buildDashboardBaseQuery(
                    q: $q,
                    status: $status,
                    storeId: $storeId,
                    requestedBy: $requestedBy,
                    authorizedBy: $authorizedBy,
                    from: $from,
                    to: $to
                ),
                'x'
            )
            ->selectRaw("
                COALESCE(x.store_name, '-') as store_name,
                COUNT(*) as total_request,
                COALESCE(SUM(x.voided_amount), 0) as total_voided_amount
            ")
            ->groupBy('x.store_name')
            ->orderByDesc('total_request')
            ->limit(10)
            ->get();

        $voidCashierRows = DB::query()
            ->fromSub(
                $this->buildDashboardBaseQuery(
                    q: $q,
                    status: $status,
                    storeId: $storeId,
                    requestedBy: $requestedBy,
                    authorizedBy: $authorizedBy,
                    from: $from,
                    to: $to
                ),
                'x'
            )
            ->selectRaw("
                COALESCE(x.cashier_name, '-') as cashier_name,
                COUNT(*) as total_request,
                COALESCE(SUM(x.voided_amount), 0) as total_voided_amount
            ")
            ->groupBy('x.cashier_name')
            ->orderByDesc('total_request')
            ->limit(10)
            ->get();

        $voidDailyLabels = $voidDailyRows->map(function ($row) {
            return \Carbon\Carbon::parse($row->request_date)->format('d M');
        })->values();

        $voidDailyCounts = $voidDailyRows->map(function ($row) {
            return (int) ($row->total_request ?? 0);
        })->values();

        $voidDailyAmounts = $voidDailyRows->map(function ($row) {
            return (float) ($row->total_voided_amount ?? 0);
        })->values();

        $voidStatusLabels = $voidStatusRows->map(function ($row) {
            return $row->status_label ?? '-';
        })->values();

        $voidStatusCounts = $voidStatusRows->map(function ($row) {
            return (int) ($row->total_request ?? 0);
        })->values();

        $voidStatusAmounts = $voidStatusRows->map(function ($row) {
            return (float) ($row->total_voided_amount ?? 0);
        })->values();

        $voidStoreLabels = $voidStoreRows->map(function ($row) {
            return $row->store_name ?? '-';
        })->values();

        $voidStoreCounts = $voidStoreRows->map(function ($row) {
            return (int) ($row->total_request ?? 0);
        })->values();

        $voidStoreAmounts = $voidStoreRows->map(function ($row) {
            return (float) ($row->total_voided_amount ?? 0);
        })->values();

        $voidCashierLabels = $voidCashierRows->map(function ($row) {
            return $row->cashier_name ?? '-';
        })->values();

        $voidCashierCounts = $voidCashierRows->map(function ($row) {
            return (int) ($row->total_request ?? 0);
        })->values();

        $voidCashierAmounts = $voidCashierRows->map(function ($row) {
            return (float) ($row->total_voided_amount ?? 0);
        })->values();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Void Cancel Customer',
            'description' => 'Laporan monitoring.',
            'data' => compact(
            'rows',
            'detailItems',
            'q',
            'status',
            'storeId',
            'requestedBy',
            'authorizedBy',
            'from',
            'to',
            'stores',
            'users',
            'statuses',
            'summary',
            'voidDailyRows',
            'voidStatusRows',
            'voidStoreRows',
            'voidCashierRows',
            'voidDailyLabels',
            'voidDailyCounts',
            'voidDailyAmounts',
            'voidStatusLabels',
            'voidStatusCounts',
            'voidStatusAmounts',
            'voidStoreLabels',
            'voidStoreCounts',
            'voidStoreAmounts',
            'voidCashierLabels',
            'voidCashierCounts',
            'voidCashierAmounts'
        ),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function buildBaseQuery(
        string $q = '',
        ?string $status = null,
        ?string $storeId = null,
        ?string $requestedBy = null,
        ?string $authorizedBy = null,
        ?string $from = null,
        ?string $to = null
    ) {
        return DB::table('void_transactions as vt')
            ->leftJoin('stores as s', 's.id', '=', 'vt.store_id')
            ->leftJoin('users as cashier', 'cashier.id', '=', 'vt.cashier_user_id')
            ->leftJoin('users as authorizer', 'authorizer.id', '=', 'vt.authorized_by')
            ->leftJoin('cashier_sessions as cs', 'cs.id', '=', 'vt.cashier_session_id')
            ->leftJoin('payments as p', 'p.id', '=', 'vt.payment_id')
            ->leftJoin('store_customer_visits as scv', 'scv.id', '=', 'vt.visit_id')
            ->select([
                'vt.id',
                'vt.payment_id',
                'vt.visit_id',
                'vt.store_id',
                'vt.cashier_user_id',
                'vt.authorized_by',
                'vt.cashier_session_id',
                'vt.reason',
                'vt.status',
                'vt.voided_amount',
                'vt.points_reversed',
                'vt.items_restored_count',
                'vt.requested_at',
                'vt.authorized_at',
                'vt.rejected_at',
                'vt.rejection_reason',
                'vt.created_at',

                's.name as store_name',
                'cashier.name as cashier_name',
                'authorizer.name as authorized_by_name',
                'cs.session_number',

                'p.transaction_number',
                'p.total_amount as payment_total_amount',
                'p.paid_amount as payment_paid_amount',
                'p.change_amount as payment_change_amount',
                'p.status as payment_status',
                'p.completed_at as payment_completed_at',

                'scv.visit_code',
                'scv.check_in_at',
                'scv.check_out_at',
                'scv.status as visit_status',
                'scv.payment_status as visit_payment_status',
                'scv.visit_type',

                DB::raw("
                    CASE
                        WHEN vt.status::text = 'approved' THEN 'Void Approved'
                        WHEN vt.status::text = 'authorized' THEN 'Void Approved'
                        WHEN vt.status::text = 'rejected' THEN 'Void Rejected'
                        WHEN vt.status::text = 'requested' THEN 'Void Requested'
                        WHEN vt.status::text = 'cancelled' THEN 'Cancel'
                        ELSE COALESCE(vt.status::text, '-')
                    END as status_label
                "),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";

                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('cashier.name', 'ILIKE', $like)
                        ->orWhere('authorizer.name', 'ILIKE', $like)
                        ->orWhere('vt.reason', 'ILIKE', $like)
                        ->orWhere('vt.rejection_reason', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.payment_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere('p.transaction_number', 'ILIKE', $like)
                        ->orWhere('scv.visit_code', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.visit_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.voided_amount AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.items_restored_count AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('vt.status::text'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where(DB::raw('vt.status::text'), $status);
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->where('vt.store_id', $storeId);
            })
            ->when($requestedBy, function ($qBuilder) use ($requestedBy) {
                $qBuilder->where('vt.cashier_user_id', $requestedBy);
            })
            ->when($authorizedBy, function ($qBuilder) use ($authorizedBy) {
                $qBuilder->where('vt.authorized_by', $authorizedBy);
            })
            ->whereNotNull('vt.requested_at')
            ->whereBetween(DB::raw('vt.requested_at::date'), [$from, $to]);
    }

    private function buildDashboardBaseQuery(
        string $q = '',
        ?string $status = null,
        ?string $storeId = null,
        ?string $requestedBy = null,
        ?string $authorizedBy = null,
        ?string $from = null,
        ?string $to = null
    ) {
        return DB::table('void_transactions as vt')
            ->leftJoin('stores as s', 's.id', '=', 'vt.store_id')
            ->leftJoin('users as cashier', 'cashier.id', '=', 'vt.cashier_user_id')
            ->leftJoin('users as authorizer', 'authorizer.id', '=', 'vt.authorized_by')
            ->leftJoin('payments as p', 'p.id', '=', 'vt.payment_id')
            ->leftJoin('store_customer_visits as scv', 'scv.id', '=', 'vt.visit_id')
            ->selectRaw("
                vt.id,
                vt.store_id,
                vt.cashier_user_id,
                vt.authorized_by,
                vt.status,
                vt.voided_amount,
                vt.items_restored_count,
                vt.requested_at,
                vt.requested_at::date as request_date,

                s.name as store_name,
                cashier.name as cashier_name,
                authorizer.name as authorized_by_name,

                p.transaction_number,
                scv.visit_code,

                CASE
                    WHEN vt.status::text = 'approved' THEN 'Void Approved'
                    WHEN vt.status::text = 'authorized' THEN 'Void Approved'
                    WHEN vt.status::text = 'rejected' THEN 'Void Rejected'
                    WHEN vt.status::text = 'requested' THEN 'Void Requested'
                    WHEN vt.status::text = 'cancelled' THEN 'Cancel'
                    ELSE COALESCE(vt.status::text, '-')
                END as status_label
            ")
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";

                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('cashier.name', 'ILIKE', $like)
                        ->orWhere('authorizer.name', 'ILIKE', $like)
                        ->orWhere('vt.reason', 'ILIKE', $like)
                        ->orWhere('vt.rejection_reason', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.payment_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere('p.transaction_number', 'ILIKE', $like)
                        ->orWhere('scv.visit_code', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.visit_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.voided_amount AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.items_restored_count AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('vt.status::text'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where(DB::raw('vt.status::text'), $status);
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->where('vt.store_id', $storeId);
            })
            ->when($requestedBy, function ($qBuilder) use ($requestedBy) {
                $qBuilder->where('vt.cashier_user_id', $requestedBy);
            })
            ->when($authorizedBy, function ($qBuilder) use ($authorizedBy) {
                $qBuilder->where('vt.authorized_by', $authorizedBy);
            })
            ->whereNotNull('vt.requested_at')
            ->whereBetween(DB::raw('vt.requested_at::date'), [$from, $to]);
    }

    public function export(Request $request)
    {
        $filename = 'void-cancel-customer-report-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new VoidCancelCustomerReportExport($request),
            $filename
        );
    }
}