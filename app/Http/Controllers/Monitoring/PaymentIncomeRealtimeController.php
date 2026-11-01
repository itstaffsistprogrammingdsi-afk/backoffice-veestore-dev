<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Exports\PaymentIncomeRealtimeExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PaymentIncomeRealtimeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $baseQuery = $this->baseIncomeQuery($filters);

        $rows = (clone $baseQuery)
            ->orderByDesc('x.income_at')
            ->orderByDesc('x.row_uid')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = $this->getSummary($filters);
        $methodSummaries = $this->getPaymentMethodSummaries($filters);
        $hourlyRows = $this->getHourlyRows($filters);

        $latestRows = (clone $baseQuery)
            ->orderByDesc('x.income_at')
            ->orderByDesc('x.row_uid')
            ->limit(10)
            ->get();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = DB::table('payment_methods')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $methodLabels = $methodSummaries
            ->map(fn ($row) => $row->payment_method_name ?? '-')
            ->values();

        $methodAmounts = $methodSummaries
            ->map(fn ($row) => (float) ($row->total_amount ?? 0))
            ->values();

        $methodCounts = $methodSummaries
            ->map(fn ($row) => (int) ($row->total_transactions ?? 0))
            ->values();

        $hourLabels = $hourlyRows
            ->map(fn ($row) => $row->hour_label ?? '-')
            ->values();

        $hourAmounts = $hourlyRows
            ->map(fn ($row) => (float) ($row->total_amount ?? 0))
            ->values();

        $hourCounts = $hourlyRows
            ->map(fn ($row) => (int) ($row->total_transactions ?? 0))
            ->values();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Payment Income Realtime',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows' => $rows,
            'summary' => $summary,
            'methodSummaries' => $methodSummaries,
            'hourlyRows' => $hourlyRows,
            'latestRows' => $latestRows,

            'stores' => $stores,
            'paymentMethods' => $paymentMethods,

            'methodLabels' => $methodLabels,
            'methodAmounts' => $methodAmounts,
            'methodCounts' => $methodCounts,

            'hourLabels' => $hourLabels,
            'hourAmounts' => $hourAmounts,
            'hourCounts' => $hourCounts,

            'filters' => $filters,
            'storeId' => $filters['store_id'],
            'paymentMethodId' => $filters['payment_method_id'],
            'paymentStatus' => $filters['payment_status'],
            'keyword' => $filters['keyword'],
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'perPage' => $filters['per_page'],
            'autoRefresh' => $filters['auto_refresh'],
            'today' => now()->toDateString(),
            'lastUpdate' => now()->format('d-m-Y H:i:s'),
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);

        $rows = $this->baseIncomeQuery($filters)
            ->orderByDesc('x.income_at')
            ->orderByDesc('x.row_uid')
            ->get();

        $summary = $this->getSummary($filters);
        $methodSummaries = $this->getPaymentMethodSummaries($filters);

        $storeName = $filters['store_id']
            ? DB::table('stores')->where('id', $filters['store_id'])->value('name')
            : null;

        $paymentMethodName = $filters['payment_method_id']
            ? DB::table('payment_methods')->where('id', $filters['payment_method_id'])->value('name')
            : null;

        return Excel::download(
            new PaymentIncomeRealtimeExport(
                $rows,
                $summary,
                $methodSummaries,
                [
                    ...$filters,
                    'store_name' => $storeName ?: 'Semua Store',
                    'payment_method_name' => $paymentMethodName ?: 'Semua Metode',
                ]
            ),
            'pemasukan-realtime-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function getFilters(Request $request): array
    {
        $today = now()->toDateString();

        $startDate = $this->normalizeDate($request->query('start_date')) ?: $today;
        $endDate = $this->normalizeDate($request->query('end_date')) ?: $today;

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $paymentStatus = $this->normalizePaymentStatus($request->query('payment_status'));

        /*
        |--------------------------------------------------------------------------
        | Default status income
        |--------------------------------------------------------------------------
        | Jika user belum pilih status, halaman otomatis menampilkan income yang
        | sudah masuk. Status "paid" akan mencakup:
        | paid, settlement, capture, success, completed.
        */
        if (! $request->has('payment_status')) {
            $paymentStatus = 'paid';
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'keyword' => $this->normalizeNullableString($request->query('keyword')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'payment_method_id' => $this->normalizeNullableString($request->query('payment_method_id')),
            'payment_status' => $paymentStatus,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'per_page' => $perPage,
            'auto_refresh' => $request->boolean('auto_refresh'),
        ];
    }

    private function baseIncomeQuery(array $filters): Builder
    {
        $storeQuery = $this->storeIncomeQuery($filters);

        /*
        |--------------------------------------------------------------------------
        | Jika filter store dipilih
        |--------------------------------------------------------------------------
        | Ecommerce tidak punya store_id, jadi saat user filter store tertentu,
        | data yang tampil hanya transaksi store.
        */
        if ($filters['store_id']) {
            $unionQuery = $storeQuery;
        } else {
            $ecommerceQuery = $this->ecommerceIncomeQuery($filters);
            $unionQuery = $storeQuery->unionAll($ecommerceQuery);
        }

        $query = DB::query()->fromSub($unionQuery, 'x');

        $this->applyKeywordFilter($query, $filters['keyword']);

        return $query;
    }

    private function storeIncomeQuery(array $filters): Builder
    {
        $start = Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay();

        $paymentMethodAgg = $this->paymentMethodAggregate();

        $query = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'scv.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'svp.payment_id')
            ->leftJoinSub($paymentMethodAgg, 'pma', function ($join) {
                $join->on('pma.payment_id', '=', 'p.id');
            })
            ->whereNull('s.deleted_at')
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, scv.check_in_at)'), [$start, $end]);

        $this->applyPaymentFilters($query, $filters, 'p', 'svp.payment_id');

        return $query->selectRaw("
            CONCAT('STORE-', svp.id::text) as row_uid,
            'STORE' as income_source,
            'Transaksi Store' as income_source_label,

            svp.id as source_id,
            svp.payment_id as payment_id,
            svp.source as payment_source,
            svp.note as payment_note,

            scv.id as reference_id,
            scv.visit_code as reference_code,
            scv.visit_code as visit_code,

            s.id as store_id,
            s.name as store_name,
            s.code as store_code,

            c.id as customer_id,
            COALESCE(c.name, 'Guest / Non Member') as customer_name,

            p.transaction_number,
            COALESCE(p.total_amount, 0)::numeric as total_amount,
            COALESCE(p.tax_amount, 0)::numeric as tax_amount,
            COALESCE(p.paid_amount, 0)::numeric as paid_amount,
            COALESCE(p.change_amount, 0)::numeric as change_amount,

            CASE
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('paid', 'settlement', 'capture', 'success', 'completed')
                    THEN 'paid'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('pending', 'waiting', 'challenge')
                    THEN 'pending'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('failed', 'failure', 'deny', 'denied', 'expired')
                    THEN 'failed'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('cancel', 'cancelled')
                    THEN 'cancelled'
                ELSE COALESCE(p.status::text, '-')
            END as payment_status,

            COALESCE(p.status::text, '-') as raw_payment_status,
            p.completed_at,
            p.created_at as payment_created_at,

            COALESCE(pma.payment_methods, '-') as payment_methods,
            COALESCE(pma.payment_method_descriptions, COALESCE(pma.payment_methods, '-')) as payment_method_descriptions,
            COALESCE(pma.midtrans_payment_types, '-') as midtrans_payment_types,
            COALESCE(pma.transaction_refs, '-') as transaction_refs,
            COALESCE(pma.payment_detail_statuses, '-') as payment_detail_statuses,
            COALESCE(pma.payment_method_amount, 0)::numeric as payment_method_amount,

            COALESCE(p.completed_at, p.created_at, scv.check_in_at) as income_at
        ");
    }

    private function ecommerceIncomeQuery(array $filters): Builder
    {
        $start = Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay();

        $paymentMethodAgg = $this->paymentMethodAggregate();

        $query = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoinSub($paymentMethodAgg, 'pma', function ($join) {
                $join->on('pma.payment_id', '=', 'p.id');
            })
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, eo.created_at)'), [$start, $end]);

        $this->applyPaymentFilters($query, $filters, 'p', 'eo.payment_id');

        return $query->selectRaw("
            CONCAT('ECOMMERCE-', eo.id::text) as row_uid,
            'ECOMMERCE' as income_source,
            'Ecommerce / Payment Gateway' as income_source_label,

            eo.id as source_id,
            eo.payment_id as payment_id,
            'ecommerce' as payment_source,
            p.note as payment_note,

            eo.id as reference_id,
            eo.order_number as reference_code,
            eo.order_number as visit_code,

            NULL::uuid as store_id,
            'Ecommerce' as store_name,
            'ECOM' as store_code,

            c.id as customer_id,
            COALESCE(c.name, 'Guest / Non Member') as customer_name,

            p.transaction_number,
            COALESCE(p.total_amount, eo.grand_total, 0)::numeric as total_amount,
            COALESCE(p.tax_amount, 0)::numeric as tax_amount,
            COALESCE(p.paid_amount, 0)::numeric as paid_amount,
            COALESCE(p.change_amount, 0)::numeric as change_amount,

            CASE
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('paid', 'settlement', 'capture', 'success', 'completed')
                    THEN 'paid'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('pending', 'waiting', 'challenge')
                    THEN 'pending'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('failed', 'failure', 'deny', 'denied', 'expired')
                    THEN 'failed'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('cancel', 'cancelled')
                    THEN 'cancelled'
                ELSE COALESCE(p.status::text, '-')
            END as payment_status,

            COALESCE(p.status::text, '-') as raw_payment_status,
            p.completed_at,
            p.created_at as payment_created_at,

            COALESCE(pma.payment_methods, '-') as payment_methods,
            COALESCE(pma.payment_method_descriptions, COALESCE(pma.payment_methods, '-')) as payment_method_descriptions,
            COALESCE(pma.midtrans_payment_types, '-') as midtrans_payment_types,
            COALESCE(pma.transaction_refs, '-') as transaction_refs,
            COALESCE(pma.payment_detail_statuses, '-') as payment_detail_statuses,
            COALESCE(pma.payment_method_amount, 0)::numeric as payment_method_amount,

            COALESCE(p.completed_at, p.created_at, eo.created_at) as income_at
        ");
    }

    private function getSummary(array $filters)
    {
        $query = $this->baseIncomeQuery($filters);

        return DB::query()
            ->fromSub($query, 'y')
            ->selectRaw("
                COUNT(DISTINCT y.row_uid)::int as total_transactions,
                COUNT(DISTINCT y.store_id)::int as total_stores,
                COUNT(DISTINCT y.customer_id)::int as total_customers,
                COALESCE(SUM(y.total_amount), 0)::numeric as total_amount,
                COALESCE(SUM(y.paid_amount), 0)::numeric as total_paid,
                COALESCE(SUM(y.change_amount), 0)::numeric as total_change,

                COUNT(DISTINCT CASE WHEN y.income_source = 'STORE' THEN y.row_uid END)::int as total_store_transactions,
                COUNT(DISTINCT CASE WHEN y.income_source = 'ECOMMERCE' THEN y.row_uid END)::int as total_ecommerce_transactions,

                COALESCE(SUM(CASE WHEN y.income_source = 'STORE' THEN y.total_amount ELSE 0 END), 0)::numeric as total_store_amount,
                COALESCE(SUM(CASE WHEN y.income_source = 'ECOMMERCE' THEN y.total_amount ELSE 0 END), 0)::numeric as total_ecommerce_amount
            ")
            ->first();
    }

    private function getPaymentMethodSummaries(array $filters)
    {
        $methodQuery = $this->basePaymentMethodDetailQuery($filters);

        return DB::query()
            ->fromSub($methodQuery, 'm')
            ->selectRaw("
                m.payment_method_id,
                m.payment_method_name,
                COUNT(DISTINCT m.row_uid)::int as total_transactions,
                COALESCE(SUM(m.payment_method_amount), 0)::numeric as total_amount,
                MIN(m.income_at) as first_used_at,
                MAX(m.income_at) as last_used_at,

                COUNT(DISTINCT CASE WHEN m.income_source = 'STORE' THEN m.row_uid END)::int as total_store_transactions,
                COUNT(DISTINCT CASE WHEN m.income_source = 'ECOMMERCE' THEN m.row_uid END)::int as total_ecommerce_transactions,

                COALESCE(SUM(CASE WHEN m.income_source = 'STORE' THEN m.payment_method_amount ELSE 0 END), 0)::numeric as total_store_amount,
                COALESCE(SUM(CASE WHEN m.income_source = 'ECOMMERCE' THEN m.payment_method_amount ELSE 0 END), 0)::numeric as total_ecommerce_amount
            ")
            ->groupBy('m.payment_method_id', 'm.payment_method_name')
            ->orderByDesc('total_amount')
            ->orderBy('m.payment_method_name')
            ->get();
    }

    private function getHourlyRows(array $filters)
    {
        $query = $this->baseIncomeQuery($filters);

        return DB::query()
            ->fromSub($query, 'h')
            ->selectRaw("
                DATE_TRUNC('hour', h.income_at) as income_hour,
                TO_CHAR(DATE_TRUNC('hour', h.income_at), 'HH24:00') as hour_label,
                COUNT(DISTINCT h.row_uid)::int as total_transactions,
                COALESCE(SUM(h.total_amount), 0)::numeric as total_amount
            ")
            ->groupByRaw("DATE_TRUNC('hour', h.income_at)")
            ->orderBy('income_hour')
            ->get();
    }

    private function basePaymentMethodDetailQuery(array $filters): Builder
    {
        $storeMethodQuery = $this->storePaymentMethodDetailQuery($filters);

        if ($filters['store_id']) {
            $unionQuery = $storeMethodQuery;
        } else {
            $ecommerceMethodQuery = $this->ecommercePaymentMethodDetailQuery($filters);
            $unionQuery = $storeMethodQuery->unionAll($ecommerceMethodQuery);
        }

        $query = DB::query()->fromSub($unionQuery, 'm');

        $this->applyPaymentMethodKeywordFilter($query, $filters['keyword']);

        return $query;
    }

    private function storePaymentMethodDetailQuery(array $filters): Builder
    {
        $start = Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay();

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

        $this->applyPaymentFilters($query, $filters, 'p', 'svp.payment_id', 'pd');

        return $query->selectRaw("
            CONCAT('STORE-', svp.id::text) as row_uid,
            'STORE' as income_source,
            'Transaksi Store' as income_source_label,

            p.id as payment_id,
            pm.id as payment_method_id,
            pm.name as payment_method_name,
            COALESCE(pd.amount, 0)::numeric as payment_method_amount,

            scv.store_id as store_id,
            s.name as store_name,
            s.code as store_code,

            c.id as customer_id,
            COALESCE(c.name, 'Guest / Non Member') as customer_name,

            scv.visit_code as reference_code,
            p.transaction_number,

            CASE
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('paid', 'settlement', 'capture', 'success', 'completed')
                    THEN 'paid'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('pending', 'waiting', 'challenge')
                    THEN 'pending'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('failed', 'failure', 'deny', 'denied', 'expired')
                    THEN 'failed'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('cancel', 'cancelled')
                    THEN 'cancelled'
                ELSE COALESCE(p.status::text, '-')
            END as payment_status,

            COALESCE(p.completed_at, p.created_at, scv.check_in_at) as income_at
        ");
    }

    private function ecommercePaymentMethodDetailQuery(array $filters): Builder
    {
        $start = Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay();

        $query = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->join('payment_details as pd', 'pd.payment_id', '=', 'p.id')
            ->join('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->whereNotNull('p.id')
            ->whereBetween(DB::raw('COALESCE(p.completed_at, p.created_at, eo.created_at)'), [$start, $end]);

        $this->applyPaymentFilters($query, $filters, 'p', 'eo.payment_id', 'pd');

        return $query->selectRaw("
            CONCAT('ECOMMERCE-', eo.id::text) as row_uid,
            'ECOMMERCE' as income_source,
            'Ecommerce / Payment Gateway' as income_source_label,

            p.id as payment_id,
            pm.id as payment_method_id,

            CASE
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'qris'
                    THEN 'Payment Gateway Midtrans - QRIS'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bank_transfer'
                    THEN 'Payment Gateway Midtrans - Bank Transfer / Virtual Account'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'echannel'
                    THEN 'Payment Gateway Midtrans - Mandiri Bill Payment / E-Channel'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'gopay'
                    THEN 'Payment Gateway Midtrans - GoPay'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'shopeepay'
                    THEN 'Payment Gateway Midtrans - ShopeePay'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'cstore'
                    THEN 'Payment Gateway Midtrans - Convenience Store'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'credit_card'
                    THEN 'Payment Gateway Midtrans - Credit Card'
                WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] IS NOT NULL
                    THEN CONCAT(
                        'Payment Gateway Midtrans - ',
                        UPPER(REPLACE((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], '_', ' '))
                    )
                WHEN LOWER(COALESCE(pm.name, '')) LIKE '%midtrans%'
                    THEN 'Payment Gateway Midtrans - Method tidak tersedia di detail'
                ELSE COALESCE(pm.name, '-')
            END as payment_method_name,

            COALESCE(pd.amount, 0)::numeric as payment_method_amount,

            NULL::uuid as store_id,
            'Ecommerce' as store_name,
            'ECOM' as store_code,

            c.id as customer_id,
            COALESCE(c.name, 'Guest / Non Member') as customer_name,

            eo.order_number as reference_code,
            p.transaction_number,

            CASE
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('paid', 'settlement', 'capture', 'success', 'completed')
                    THEN 'paid'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('pending', 'waiting', 'challenge')
                    THEN 'pending'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('failed', 'failure', 'deny', 'denied', 'expired')
                    THEN 'failed'
                WHEN LOWER(COALESCE(p.status::text, '')) IN ('cancel', 'cancelled')
                    THEN 'cancelled'
                ELSE COALESCE(p.status::text, '-')
            END as payment_status,

            COALESCE(p.completed_at, p.created_at, eo.created_at) as income_at
        ");
    }

    private function paymentMethodAggregate(): Builder
    {
        return DB::table('payment_details as pd')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->selectRaw("
                pd.payment_id,

                COALESCE(SUM(pd.amount), 0)::numeric as payment_method_amount,

                STRING_AGG(
                    DISTINCT COALESCE(pm.name, '-'),
                    ', '
                ) as payment_methods,

                STRING_AGG(
                    DISTINCT COALESCE(pd.transaction_ref, '-'),
                    ', '
                ) as transaction_refs,

                STRING_AGG(
                    DISTINCT COALESCE(pd.status::text, '-'),
                    ', '
                ) as payment_detail_statuses,

                STRING_AGG(
                    DISTINCT COALESCE(
                        NULLIF((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], ''),
                        '-'
                    ),
                    ', '
                ) as midtrans_payment_types,

                STRING_AGG(
                    DISTINCT
                    CASE
                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'qris'
                            THEN 'Payment Gateway Midtrans - QRIS'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bank_transfer'
                            THEN 'Payment Gateway Midtrans - Bank Transfer / Virtual Account'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'echannel'
                            THEN 'Payment Gateway Midtrans - Mandiri Bill Payment / E-Channel'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'gopay'
                            THEN 'Payment Gateway Midtrans - GoPay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'shopeepay'
                            THEN 'Payment Gateway Midtrans - ShopeePay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'cstore'
                            THEN 'Payment Gateway Midtrans - Convenience Store'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'credit_card'
                            THEN 'Payment Gateway Midtrans - Credit Card'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'akulaku'
                            THEN 'Payment Gateway Midtrans - Akulaku'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'kredivo'
                            THEN 'Payment Gateway Midtrans - Kredivo'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] IS NOT NULL
                            THEN CONCAT(
                                'Payment Gateway Midtrans - ',
                                UPPER(REPLACE((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], '_', ' '))
                            )

                        WHEN LOWER(COALESCE(pm.name, '')) LIKE '%midtrans%'
                            THEN 'Payment Gateway Midtrans - Method tidak tersedia di detail'

                        ELSE COALESCE(pm.name, '-')
                    END,
                    ', '
                ) as payment_method_descriptions
            ")
            ->groupBy('pd.payment_id');
    }

    private function applyPaymentFilters(
        Builder $query,
        array $filters,
        string $paymentAlias,
        string $paymentIdColumn,
        string $paymentDetailAlias = 'pd_filter'
    ): void {
        if ($filters['store_id']) {
            $query->where('scv.store_id', $filters['store_id']);
        }

        if ($filters['payment_status']) {
            $this->applyPaymentStatusFilter($query, $paymentAlias, $filters['payment_status']);
        }

        if ($filters['payment_method_id']) {
            if ($paymentDetailAlias === 'pd') {
                $query->where('pd.payment_method_id', $filters['payment_method_id']);
            } else {
                $query->whereExists(function ($sub) use ($paymentIdColumn, $filters) {
                    $sub->select(DB::raw(1))
                        ->from('payment_details as pd_filter')
                        ->whereColumn('pd_filter.payment_id', $paymentIdColumn)
                        ->where('pd_filter.payment_method_id', $filters['payment_method_id']);
                });
            }
        }
    }

    private function applyPaymentStatusFilter(Builder $query, string $paymentAlias, string $status): void
    {
        $status = strtolower($status);

        if ($status === 'paid') {
            $query->whereIn(DB::raw("LOWER(COALESCE({$paymentAlias}.status::text, ''))"), [
                'paid',
                'settlement',
                'capture',
                'success',
                'completed',
            ]);

            return;
        }

        if ($status === 'pending') {
            $query->whereIn(DB::raw("LOWER(COALESCE({$paymentAlias}.status::text, ''))"), [
                'pending',
                'waiting',
                'challenge',
            ]);

            return;
        }

        if ($status === 'failed') {
            $query->whereIn(DB::raw("LOWER(COALESCE({$paymentAlias}.status::text, ''))"), [
                'failed',
                'failure',
                'deny',
                'denied',
                'expired',
            ]);

            return;
        }

        if ($status === 'cancelled') {
            $query->whereIn(DB::raw("LOWER(COALESCE({$paymentAlias}.status::text, ''))"), [
                'cancel',
                'cancelled',
            ]);
        }
    }

    private function applyKeywordFilter(Builder $query, ?string $keyword): void
    {
        if (! $keyword) {
            return;
        }

        $query->where(function ($q) use ($keyword) {
            $q->where('x.reference_code', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.store_name', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.store_code', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.customer_name', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.transaction_number', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.payment_source', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.income_source_label', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.payment_methods', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.payment_method_descriptions', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.midtrans_payment_types', 'ILIKE', "%{$keyword}%")
                ->orWhere('x.transaction_refs', 'ILIKE', "%{$keyword}%");
        });
    }

    private function applyPaymentMethodKeywordFilter(Builder $query, ?string $keyword): void
    {
        if (! $keyword) {
            return;
        }

        $query->where(function ($q) use ($keyword) {
            $q->where('m.reference_code', 'ILIKE', "%{$keyword}%")
                ->orWhere('m.store_name', 'ILIKE', "%{$keyword}%")
                ->orWhere('m.store_code', 'ILIKE', "%{$keyword}%")
                ->orWhere('m.customer_name', 'ILIKE', "%{$keyword}%")
                ->orWhere('m.transaction_number', 'ILIKE', "%{$keyword}%")
                ->orWhere('m.income_source_label', 'ILIKE', "%{$keyword}%")
                ->orWhere('m.payment_method_name', 'ILIKE', "%{$keyword}%");
        });
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

    private function normalizePaymentStatus($value): ?string
    {
        $value = $this->normalizeNullableString($value);

        if ($value === null) {
            return null;
        }

        $value = strtolower($value);

        return match ($value) {
            'paid', 'settlement', 'capture', 'success', 'completed' => 'paid',
            'pending', 'waiting', 'challenge', 'unpaid', 'partial' => 'pending',
            'failed', 'failure', 'deny', 'denied', 'expired' => 'failed',
            'cancel', 'cancelled' => 'cancelled',
            default => null,
        };
    }
}
