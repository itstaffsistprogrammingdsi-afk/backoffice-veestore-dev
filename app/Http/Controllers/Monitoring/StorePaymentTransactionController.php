<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\StorePaymentTransactionExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StorePaymentTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-store-payment-transactions')
            ->only(['index', 'export']);
    }

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Default data hari ini
        |--------------------------------------------------------------------------
        | Sebelumnya default dari awal bulan.
        | Sekarang default hanya hari ini agar data yang tampil sesuai hari ini.
        */
        $today = now()->toDateString();

        $startDate = $this->normalizeDate($request->query('start_date')) ?: $today;
        $endDate = $this->normalizeDate($request->query('end_date')) ?: $today;

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $storeId = $this->normalizeNullableString($request->query('store_id'));
        $paymentMethodId = $this->normalizeNullableString($request->query('payment_method_id'));
        $paymentStatus = $this->normalizePaymentStatus($request->query('payment_status'));
        $keyword = $this->normalizeNullableString($request->query('keyword'));
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = DB::table('payment_methods')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = $this->buildRowsQuery(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        )->paginate($perPage)->withQueryString();

        $paymentMethodSummaries = $this->getPaymentMethodSummaries(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        $paymentMethodUsageDetails = $this->getPaymentMethodUsageDetails(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        $dailyPaymentRows = $this->getDailyPaymentRows(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        $summaryDashboard = $this->getDashboardSummary(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        $summaryGrandTotal = (float) $paymentMethodSummaries->sum('total_amount');
        $summaryGrandTransaction = (int) $paymentMethodSummaries->sum('total_transactions');

        $paymentMethodLabels = $paymentMethodSummaries
            ->map(fn ($row) => $row->payment_method_name ?? '-')
            ->values();

        $paymentMethodCounts = $paymentMethodSummaries
            ->map(fn ($row) => (int) ($row->total_transactions ?? 0))
            ->values();

        $paymentMethodAmounts = $paymentMethodSummaries
            ->map(fn ($row) => (float) ($row->total_amount ?? 0))
            ->values();

        $dailyLabels = $dailyPaymentRows
            ->map(fn ($row) => Carbon::parse($row->payment_date)->format('d M'))
            ->values();

        $dailyCounts = $dailyPaymentRows
            ->map(fn ($row) => (int) ($row->total_transactions ?? 0))
            ->values();

        $dailyAmounts = $dailyPaymentRows
            ->map(fn ($row) => (float) ($row->total_amount ?? 0))
            ->values();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Store Payment Transactions',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows' => $rows,
            'stores' => $stores,
            'paymentMethods' => $paymentMethods,
            'paymentMethodSummaries' => $paymentMethodSummaries,
            'paymentMethodUsageDetails' => $paymentMethodUsageDetails,
            'summaryDashboard' => $summaryDashboard,

            'summaryGrandTotal' => $summaryGrandTotal,
            'summaryGrandTransaction' => $summaryGrandTransaction,

            'paymentMethodLabels' => $paymentMethodLabels,
            'paymentMethodCounts' => $paymentMethodCounts,
            'paymentMethodAmounts' => $paymentMethodAmounts,

            'dailyLabels' => $dailyLabels,
            'dailyCounts' => $dailyCounts,
            'dailyAmounts' => $dailyAmounts,

            'storeId' => $storeId,
            'paymentMethodId' => $paymentMethodId,
            'paymentStatus' => $paymentStatus,
            'keyword' => $keyword,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'perPage' => $perPage,
            'today' => $today,
            'lastUpdate' => now()->format('d-m-Y H:i:s'),
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function export(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Export juga default hari ini
        |--------------------------------------------------------------------------
        */
        $today = now()->toDateString();

        $startDate = $this->normalizeDate($request->query('start_date')) ?: $today;
        $endDate = $this->normalizeDate($request->query('end_date')) ?: $today;

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $storeId = $this->normalizeNullableString($request->query('store_id'));
        $paymentMethodId = $this->normalizeNullableString($request->query('payment_method_id'));
        $paymentStatus = $this->normalizePaymentStatus($request->query('payment_status'));
        $keyword = $this->normalizeNullableString($request->query('keyword'));

        $rows = $this->buildRowsQuery(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        )->get();

        $paymentMethodSummaries = $this->getPaymentMethodSummaries(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        $storeName = '-';
        if ($storeId) {
            $storeName = DB::table('stores')
                ->where('id', $storeId)
                ->value('name') ?? '-';
        }

        $paymentMethodName = '-';
        if ($paymentMethodId) {
            $paymentMethodName = DB::table('payment_methods')
                ->where('id', $paymentMethodId)
                ->value('name') ?? '-';
        }

        return Excel::download(
            new StorePaymentTransactionExport(
                $rows,
                [
                    'store_name' => $storeName,
                    'payment_method_name' => $paymentMethodName,
                    'payment_status' => $paymentStatus ?: '-',
                    'keyword' => $keyword ?: '-',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                $paymentMethodSummaries
            ),
            'store-payment-transactions-' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function buildRowsQuery(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null
    ): Builder {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $paymentMethodAgg = DB::table('payment_details as pd')
            ->join('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->selectRaw("
                pd.payment_id,
                COALESCE(STRING_AGG(DISTINCT pm.name, ', ' ORDER BY pm.name), '-') as payment_methods,
                COALESCE(SUM(pd.amount), 0)::numeric as payment_detail_amount
            ")
            ->groupBy('pd.payment_id');

        $cartAgg = DB::table('store_carts as sc')
            ->leftJoin('vouchers as v', 'v.id', '=', 'sc.voucher_id')
            ->leftJoin('store_cart_details as scd', 'scd.cart_id', '=', 'sc.id')
            ->leftJoin('product_prices as pp', 'pp.id', '=', 'scd.product_price_id')
            ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
            ->leftJoin('products as pr', function ($join) {
                $join->on('pr.id', '=', 'i.product_id')
                    ->orOn('pr.id', '=', 'pp.product_id');
            })
            ->selectRaw("
                sc.visit_id,
                COALESCE(MAX(v.name), '-') as voucher_name,
                COALESCE(MAX(sc.voucher_discount), 0)::numeric as voucher_discount,
                COALESCE(
                    json_agg(
                        DISTINCT jsonb_build_object(
                            'product_id', pr.id,
                            'product_name', COALESCE(pr.name, '-')
                        )
                    ) FILTER (WHERE pr.id IS NOT NULL),
                    '[]'::json
                ) as product_details,
                COUNT(DISTINCT pr.id)::int as total_products,
                COUNT(DISTINCT scd.id)::int as total_product_rows,
                '-' as used_discounts
            ")
            ->groupBy('sc.visit_id');

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'scv.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'svp.payment_id')
            ->leftJoinSub($paymentMethodAgg, 'pma', function ($join) {
                $join->on('pma.payment_id', '=', 'p.id');
            })
            ->leftJoinSub($cartAgg, 'ca', function ($join) {
                $join->on('ca.visit_id', '=', 'scv.id');
            })
            ->whereNull('s.deleted_at')
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'), [$start, $end]);

        $this->applyCommonFilters($query, $storeId, $paymentMethodId, $paymentStatus, $keyword);

        return $query
            ->select([
                'scv.id as visit_id',
                'scv.visit_code',
                'scv.check_in_at',
                'scv.check_out_at',
                'scv.status as visit_status',
                'scv.payment_status as visit_payment_status',

                's.name as store_name',
                's.code as store_code',

                'c.id as customer_id',
                DB::raw("COALESCE(c.name, 'Guest / Non Member') as customer_name"),
                DB::raw('NULL as customer_phone'),

                'svp.id as store_visit_payment_id',
                'svp.payment_id',
                'svp.source as payment_source',
                'svp.note as payment_note',
                'svp.cashier_user_id',

                'p.id as payment_id_ref',
                'p.transaction_number',
                DB::raw('COALESCE(p.total_amount, 0)::numeric as total_amount'),
                DB::raw('COALESCE(p.tax_amount, 0)::numeric as tax_amount'),
                DB::raw('COALESCE(p.paid_amount, 0)::numeric as paid_amount'),
                DB::raw('COALESCE(p.change_amount, 0)::numeric as change_amount'),
                'p.status as payment_status',
                'p.completed_at',
                'p.created_at as payment_created_at',

                DB::raw("COALESCE(p.completed_at, p.created_at, scv.check_in_at) as payment_real_date"),
                DB::raw("COALESCE(pma.payment_methods, '-') as payment_methods"),
                DB::raw('COALESCE(pma.payment_detail_amount, 0)::numeric as payment_detail_amount'),

                DB::raw("COALESCE(ca.voucher_name, '-') as voucher_name"),
                DB::raw('COALESCE(ca.voucher_discount, 0)::numeric as voucher_discount'),
                DB::raw("COALESCE(ca.product_details, '[]'::json) as product_details"),
                DB::raw('COALESCE(ca.total_products, 0)::int as total_products'),
                DB::raw('COALESCE(ca.total_product_rows, 0)::int as total_product_rows'),
                DB::raw("COALESCE(ca.used_discounts, '-') as used_discounts"),
            ])
            ->orderByDesc(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'))
            ->orderByDesc('svp.id');
    }

    private function getDashboardSummary(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null
    ) {
        $paymentQuery = $this->buildDistinctPaymentQuery(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        return DB::query()
            ->fromSub($paymentQuery, 'x')
            ->selectRaw("
                COUNT(DISTINCT x.payment_id)::int as total_transactions,
                COUNT(DISTINCT x.store_id)::int as total_stores,
                COUNT(DISTINCT x.customer_id)::int as total_customers,
                COALESCE(SUM(x.total_amount), 0)::numeric as total_amount,
                COALESCE(SUM(x.paid_amount), 0)::numeric as total_paid,
                COALESCE(SUM(x.change_amount), 0)::numeric as total_change
            ")
            ->first();
    }

    private function getPaymentMethodSummaries(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null
    ) {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'scv.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'svp.payment_id')
            ->join('payment_details as pd', 'pd.payment_id', '=', 'p.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->whereNull('s.deleted_at')
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'), [$start, $end]);

        $this->applyCommonFilters($query, $storeId, $paymentMethodId, $paymentStatus, $keyword, 'pd');

        return $query
            ->selectRaw("
                pm.id as payment_method_id,
                pm.name as payment_method_name,
                COUNT(DISTINCT p.id)::int as total_transactions,
                COALESCE(SUM(pd.amount), 0)::numeric as total_amount,
                MIN(COALESCE(p.completed_at, p.created_at, scv.check_in_at)) as first_used_at,
                MAX(COALESCE(p.completed_at, p.created_at, scv.check_in_at)) as last_used_at
            ")
            ->groupBy('pm.id', 'pm.name')
            ->orderByDesc('total_amount')
            ->orderBy('pm.name')
            ->get();
    }

    private function getPaymentMethodUsageDetails(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null
    ) {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'scv.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'svp.payment_id')
            ->join('payment_details as pd', 'pd.payment_id', '=', 'p.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->whereNull('s.deleted_at')
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'), [$start, $end]);

        $this->applyCommonFilters($query, $storeId, $paymentMethodId, $paymentStatus, $keyword, 'pd');

        return $query
            ->selectRaw("
                pm.id as payment_method_id,
                pm.name as payment_method_name,
                p.id as payment_id,
                p.transaction_number,
                p.status as payment_status,
                p.completed_at,
                p.created_at as payment_created_at,
                COALESCE(p.completed_at, p.created_at, scv.check_in_at) as payment_real_date,
                COALESCE(p.total_amount, 0)::numeric as total_amount,
                COALESCE(pd.amount, 0)::numeric as payment_method_amount,
                scv.visit_code,
                scv.check_in_at,
                s.name as store_name,
                s.code as store_code,
                COALESCE(c.name, 'Guest / Non Member') as customer_name
            ")
            ->orderBy('pm.name')
            ->orderByDesc(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'))
            ->orderByDesc('svp.id')
            ->get()
            ->groupBy('payment_method_id');
    }

    private function getDailyPaymentRows(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null
    ) {
        $paymentQuery = $this->buildDistinctPaymentQuery(
            startDate: $startDate,
            endDate: $endDate,
            storeId: $storeId,
            paymentMethodId: $paymentMethodId,
            paymentStatus: $paymentStatus,
            keyword: $keyword
        );

        return DB::query()
            ->fromSub($paymentQuery, 'x')
            ->selectRaw("
                x.payment_real_date::date as payment_date,
                COUNT(DISTINCT x.payment_id)::int as total_transactions,
                COALESCE(SUM(x.total_amount), 0)::numeric as total_amount
            ")
            ->groupBy(DB::raw('x.payment_real_date::date'))
            ->orderBy(DB::raw('x.payment_real_date::date'))
            ->get();
    }

    private function buildDistinctPaymentQuery(
        string $startDate,
        string $endDate,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null
    ): Builder {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'scv.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('s.deleted_at')
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'), [$start, $end]);

        $this->applyCommonFilters($query, $storeId, $paymentMethodId, $paymentStatus, $keyword);

        return $query
            ->selectRaw("
                p.id as payment_id,
                scv.store_id,
                c.id as customer_id,
                COALESCE(p.total_amount, 0)::numeric as total_amount,
                COALESCE(p.paid_amount, 0)::numeric as paid_amount,
                COALESCE(p.change_amount, 0)::numeric as change_amount,
                COALESCE(p.completed_at, p.created_at, scv.check_in_at) as payment_real_date
            ")
            ->groupByRaw("
                p.id,
                scv.store_id,
                c.id,
                p.total_amount,
                p.paid_amount,
                p.change_amount,
                COALESCE(p.completed_at, p.created_at, scv.check_in_at)
            ");
    }

    private function applyCommonFilters(
        Builder $query,
        ?string $storeId = null,
        ?string $paymentMethodId = null,
        ?string $paymentStatus = null,
        ?string $keyword = null,
        string $paymentDetailAlias = 'pd_filter'
    ): void {
        if ($storeId) {
            $query->where('scv.store_id', $storeId);
        }

        if ($paymentStatus) {
            $query->where('p.status', $paymentStatus);
        }

        if ($paymentMethodId) {
            if ($paymentDetailAlias === 'pd') {
                $query->where('pd.payment_method_id', $paymentMethodId);
            } else {
                $query->whereExists(function ($sub) use ($paymentMethodId) {
                    $sub->select(DB::raw(1))
                        ->from('payment_details as pd_filter')
                        ->whereColumn('pd_filter.payment_id', 'svp.payment_id')
                        ->where('pd_filter.payment_method_id', $paymentMethodId);
                });
            }
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('scv.visit_code', 'ILIKE', "%{$keyword}%")
                    ->orWhere('s.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('s.code', 'ILIKE', "%{$keyword}%")
                    ->orWhere('c.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('p.transaction_number', 'ILIKE', "%{$keyword}%")
                    ->orWhere('svp.source', 'ILIKE', "%{$keyword}%")
                    ->orWhereExists(function ($sub) use ($keyword) {
                        $sub->select(DB::raw(1))
                            ->from('store_carts as sc_k')
                            ->leftJoin('vouchers as v_k', 'v_k.id', '=', 'sc_k.voucher_id')
                            ->leftJoin('store_cart_details as scd_k', 'scd_k.cart_id', '=', 'sc_k.id')
                            ->leftJoin('product_prices as pp_k', 'pp_k.id', '=', 'scd_k.product_price_id')
                            ->leftJoin('items as i_k', 'i_k.id', '=', 'scd_k.item_id')
                            ->leftJoin('products as pr_k', function ($join) {
                                $join->on('pr_k.id', '=', 'i_k.product_id')
                                    ->orOn('pr_k.id', '=', 'pp_k.product_id');
                            })
                            ->whereColumn('sc_k.visit_id', 'scv.id')
                            ->where(function ($inner) use ($keyword) {
                                $inner->where('pr_k.name', 'ILIKE', "%{$keyword}%")
                                    ->orWhere('v_k.name', 'ILIKE', "%{$keyword}%");
                            });
                    });
            });
        }
    }

    private function normalizeDate(?string $date): ?string
    {
        if (!$date) {
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

    private function normalizePaymentStatus($value): ?string
    {
        $value = $this->normalizeNullableString($value);

        if ($value === null) {
            return null;
        }

        $value = strtolower($value);

        return match ($value) {
            'pending', 'paid', 'failed', 'cancelled' => $value,
            'unpaid', 'partial' => 'pending',
            default => null,
        };
    }
}