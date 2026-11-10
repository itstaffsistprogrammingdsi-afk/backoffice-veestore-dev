<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\StoreOperations\Support\CashierShiftResolver;
use App\Exports\CashierShiftReportDetailExport;
use App\Exports\CashierShiftReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CashierShiftReportController extends Controller
{
    public function __construct(
        private readonly CashierShiftResolver $shiftResolver
    ) {}

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $baseQuery = $this->buildSessionQuery($filters);

        $rows = (clone $baseQuery)
            ->orderByDesc('cs.opened_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Summary Hari Ini / Realtime
        |--------------------------------------------------------------------------
        | Omset hari ini tetap dihitung dari tanggal hari ini,
        | tidak terpengaruh filter tanggal.
        | Namun tetap mengikuti filter store, cashier, status, dan search.
        */
        $todayFilters = $filters;
        $todayFilters['date_from'] = now()->toDateString();
        $todayFilters['date_to'] = now()->toDateString();

        $todayQuery = $this->buildSessionQuery($todayFilters);

        $todaySummary = DB::query()
            ->fromSub(clone $todayQuery, 'x')
            ->selectRaw("
                COALESCE(SUM(x.omzet), 0)::numeric as today_omzet,
                COALESCE(SUM(x.total_transactions), 0)::int as today_transactions,
                COALESCE(SUM(x.total_cash_amount), 0)::numeric as today_cash_amount,
                COALESCE(SUM(x.total_non_cash_amount), 0)::numeric as today_non_cash_amount,
                COALESCE(SUM(x.total_cash_transactions), 0)::int as today_cash_transactions,
                COALESCE(SUM(x.total_non_cash_transactions), 0)::int as today_non_cash_transactions,
                COUNT(*)::int as today_sessions,
                COALESCE(SUM(CASE WHEN x.cashier_status = 'open' THEN 1 ELSE 0 END), 0)::int as today_open_sessions,
                COALESCE(SUM(CASE WHEN x.cashier_status = 'closed' THEN 1 ELSE 0 END), 0)::int as today_closed_sessions,
                COALESCE(SUM(x.total_receivable_amount), 0)::numeric as today_receivable_amount,
                COALESCE(SUM(x.total_change_amount), 0)::numeric as today_change_amount,
                COALESCE(SUM(x.total_discount_amount), 0)::numeric as today_total_discount,
                COALESCE(SUM(x.opening_cash), 0)::numeric as today_opening_cash,
                COALESCE(SUM(x.closing_cash_system), 0)::numeric as today_system_cash,
                COALESCE(SUM(x.closing_cash_physical), 0)::numeric as today_physical_cash,
                COALESCE(SUM(x.cash_difference), 0)::numeric as today_cash_difference
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Summary Sesuai Filter
        |--------------------------------------------------------------------------
        | Mengikuti filter periode, store, cashier, status, dan search.
        */
        $summary = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(SUM(x.omzet), 0)::numeric as total_omzet,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COALESCE(SUM(x.total_cash_amount), 0)::numeric as total_cash_amount,
                COALESCE(SUM(x.total_non_cash_amount), 0)::numeric as total_non_cash_amount,
                COALESCE(SUM(x.total_cash_transactions), 0)::int as total_cash_transactions,
                COALESCE(SUM(x.total_non_cash_transactions), 0)::int as total_non_cash_transactions,
                COUNT(*)::int as total_sessions,
                COALESCE(SUM(CASE WHEN x.cashier_status = 'open' THEN 1 ELSE 0 END), 0)::int as total_open_sessions,
                COALESCE(SUM(CASE WHEN x.cashier_status = 'closed' THEN 1 ELSE 0 END), 0)::int as total_closed_sessions,
                COALESCE(SUM(x.total_receivable_amount), 0)::numeric as total_receivable_amount,
                COALESCE(SUM(x.total_change_amount), 0)::numeric as total_change_amount,
                COALESCE(SUM(x.total_product_discount), 0)::numeric as total_product_discount,
                COALESCE(SUM(x.total_voucher_discount), 0)::numeric as total_voucher_discount,
                COALESCE(SUM(x.total_point_discount), 0)::numeric as total_point_discount,
                COALESCE(SUM(x.total_discount_amount), 0)::numeric as total_discount_amount,
                COALESCE(SUM(x.opening_cash), 0)::numeric as total_opening_cash,
                COALESCE(SUM(x.closing_cash_system), 0)::numeric as total_system_cash,
                COALESCE(SUM(x.closing_cash_physical), 0)::numeric as total_physical_cash,
                COALESCE(SUM(x.cash_difference), 0)::numeric as total_cash_difference
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Chart Omset Harian
        |--------------------------------------------------------------------------
        */
        $dailyOmzetRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                TO_CHAR(DATE(x.opened_at), 'DD Mon') as label,
                DATE(x.opened_at) as tanggal,
                COALESCE(SUM(x.omzet), 0)::numeric as total_omzet,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions
            ")
            ->groupByRaw('DATE(x.opened_at)')
            ->orderBy('tanggal')
            ->get();

        $dailyShiftRows = $this->buildDailyShiftReportQuery(clone $baseQuery)
            ->orderByDesc('report_date')
            ->orderBy('x.store_name')
            ->orderBy('x.shift_start_time')
            ->paginate(25, ['*'], 'daily_shift_page')
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Chart Omset Per Store
        |--------------------------------------------------------------------------
        */
        $storeOmzetRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(x.store_name, 'Store Tidak Diketahui') as label,
                COALESCE(SUM(x.omzet), 0)::numeric as total_omzet,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COUNT(*)::int as total_sessions
            ")
            ->groupBy('label')
            ->orderByDesc('total_omzet')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Chart Omset Per Cashier
        |--------------------------------------------------------------------------
        */
        $cashierOmzetRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->selectRaw("
                COALESCE(x.cashier_name, 'Cashier Tidak Diketahui') as label,
                COALESCE(SUM(x.omzet), 0)::numeric as total_omzet,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COUNT(*)::int as total_sessions
            ")
            ->groupBy('label')
            ->orderByDesc('total_omzet')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Latest Session
        |--------------------------------------------------------------------------
        */
        $latestRows = DB::query()
            ->fromSub(clone $baseQuery, 'x')
            ->select([
                'x.cashier_session_id',
                'x.period_label',
                'x.store_name',
                'x.cashier_name',
                'x.shift_name',
                'x.cashier_status',
                'x.opened_at',
                'x.closed_at',
                'x.total_transactions',
                'x.omzet',
                'x.cash_difference',
            ])
            ->orderByDesc('x.opened_at')
            ->limit(10)
            ->get();

        $stores = $this->accessibleStoresQuery()
            ->select('stores.id', 'stores.name')
            ->orderBy('stores.name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Cashier
        |--------------------------------------------------------------------------
        | Hanya cashier yang store_access aktif.
        */
        $cashiers = DB::table('users')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('store_access', true)
                    ->orWhere('store_access', 1)
                    ->orWhere('store_access', 't');
            })
            ->orderBy('name')
            ->get();

        $shifts = DB::table('store_attendance_shifts as sas')
            ->join('stores as s', 's.id', '=', 'sas.store_id')
            ->whereIn('sas.store_id', $stores->pluck('id'))
            ->where('sas.is_active', true)
            ->orderBy('s.name')
            ->orderBy('sas.start_time')
            ->get([
                'sas.id',
                'sas.store_id',
                'sas.name',
                'sas.code',
                'sas.start_time',
                'sas.end_time',
                's.name as store_name',
            ]);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Shift Kasir',
            'description' => 'Ringkasan sesi kasir, termasuk transaksi tunai, non-tunai, dan piutang rekanan.',
            'data' => [
                'rows' => $rows,
                'filters' => $filters,
                'stores' => $stores,
                'cashiers' => $cashiers,

                'todaySummary' => $todaySummary,
                'summary' => $summary,

                'dailyOmzetRows' => $dailyOmzetRows,
                'dailyShiftRows' => $dailyShiftRows,
                'storeOmzetRows' => $storeOmzetRows,
                'cashierOmzetRows' => $cashierOmzetRows,
                'latestRows' => $latestRows,
                'shifts' => $shifts,
                'statuses' => ['open', 'closed'],

                'autoRefresh' => $filters['auto_refresh'],
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.cashier-shift-report.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);

        $baseQuery = $this->buildSessionQuery($filters);

        $rows = (clone $baseQuery)
            ->orderByDesc('cs.opened_at')
            ->get();

        $dailyShiftRows = $this->buildDailyShiftReportQuery(clone $baseQuery)
            ->orderByDesc('report_date')
            ->orderBy('x.store_name')
            ->orderBy('x.shift_start_time')
            ->get();

        $filename = 'cashier-shift-report-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new CashierShiftReportExport($dailyShiftRows, $rows), $filename);
    }

    public function detail(Request $request, string $cashierSession)
    {
        if (! Str::isUuid($cashierSession)) {
            abort(404);
        }

        $session = $this->getSessionDetail($cashierSession);

        abort_unless($session, 404);

        $transactions = $this->getSessionTransactions($cashierSession);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Shift Kasir',
            'description' => 'Ringkasan sesi kasir, termasuk transaksi tunai, non-tunai, dan piutang rekanan.',
            'data' => [
                'session' => $session,
                'transactions' => $transactions,
                'payment_breakdown' => $session->payment_breakdown ?? [],
                'summary' => [
                    'total_transactions' => (int) ($session->total_transactions ?? 0),
                    'total_omzet' => (float) ($session->payment_total_amount ?? $session->total_sales ?? 0),
                    'total_cash_amount' => (float) ($session->total_cash_amount ?? 0),
                    'total_non_cash_amount' => (float) ($session->total_non_cash_amount ?? 0),
                    'total_cash_transactions' => (int) ($session->total_cash_transactions ?? 0),
                    'total_non_cash_transactions' => (int) ($session->total_non_cash_transactions ?? 0),
                    'total_receivable_amount' => (float) ($session->total_receivable_amount ?? 0),
                ],
            ],
            'filters' => request()->query(),
            'urls' => [
                'index' => url()->current(),
                'export' => route('monitoring.cashier-shift-report.detail-export', ['cashierSession' => $cashierSession]),
            ],
        ]);
    }

    public function detailExport(Request $request, string $cashierSession)
    {
        if (! Str::isUuid($cashierSession)) {
            abort(404);
        }

        $session = $this->getSessionDetail($cashierSession);

        abort_unless($session, 404);

        $transactions = $this->getSessionTransactions($cashierSession);

        $transactionIds = $transactions
            ->pluck('payment_id')
            ->filter()
            ->values()
            ->all();

        $items = $this->getTransactionItemsForExport($transactionIds);

        $filename = 'cashier-shift-report-detail-'.
            ($session->session_number ?? 'session').'-'.
            now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new CashierShiftReportDetailExport($session, $transactions, $items),
            $filename
        );
    }

    public function transactionItems(string $paymentId)
    {
        if (! Str::isUuid($paymentId)) {
            return response()->json([
                'transaction' => null,
                'items' => [],
            ], 404);
        }

        $transaction = DB::table('payments as p')
            ->leftJoin('store_visit_payments as svp', 'svp.payment_id', '=', 'p.id')
            ->leftJoin('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->where('p.id', $paymentId)
            ->selectRaw('
                p.id,
                p.transaction_number,
                p.total_amount,
                p.tax_amount,
                p.paid_amount,
                p.change_amount,
                p.status,
                p.note,
                p.completed_at,
                svp.visit_id,
                c.name as customer_name
            ')
            ->first();

        if (! $transaction) {
            return response()->json([
                'transaction' => null,
                'items' => [],
            ], 404);
        }

        $items = $this->getTransactionItemsForExport([$paymentId])
            ->where('payment_id', $paymentId)
            ->values()
            ->map(function ($item) {
                return [
                    'product_code' => $item->product_code,
                    'product_name' => $item->product_name,
                    'qty' => (float) $item->qty,
                    'price' => (float) $item->price,
                    'subtotal' => (float) $item->subtotal,
                ];
            })
            ->values();

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'total_amount' => (float) $transaction->total_amount,
                'tax_amount' => (float) $transaction->tax_amount,
                'paid_amount' => (float) $transaction->paid_amount,
                'change_amount' => (float) $transaction->change_amount,
                'status' => $transaction->status,
                'note' => $transaction->note,
                'completed_at' => $transaction->completed_at,
                'customer_name' => $transaction->customer_name,
            ],
            'items' => $items,
        ]);
    }

    private function getFilters(Request $request): array
    {
        $today = now()->toDateString();

        $dateFrom = $this->normalizeNullableString($request->query('date_from')) ?? $today;
        $dateTo = $this->normalizeNullableString($request->query('date_to')) ?? $today;

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $status = $this->normalizeNullableString($request->query('status'));

        if (! in_array($status, [null, 'open', 'closed'], true)) {
            $status = null;
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'cashier_id' => $this->normalizeNullableString($request->query('cashier_id')),
            'shift_id' => $this->normalizeNullableString($request->query('shift_id')),
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'per_page' => $perPage,
            'auto_refresh' => $request->boolean('auto_refresh'),
        ];
    }

    private function getSessionDetail(string $cashierSession)
    {
        $userStoreIds = $this->accessibleStoreIds();

        $session = DB::table('cashier_sessions as cs')
            ->join('stores as s', 's.id', '=', 'cs.store_id')
            ->join('users as u', 'u.id', '=', 'cs.user_id')
            ->where('cs.id', $cashierSession)
            ->whereIn('cs.store_id', $userStoreIds)
            ->selectRaw('
                cs.id,
                cs.session_number,
                cs.status,
                cs.opened_at,
                cs.closed_at,
                cs.opening_cash,
                cs.expected_cash,
                cs.actual_cash,
                cs.difference,
                cs.total_sales,
                cs.total_transactions,
                cs.note_open,
                cs.note_close,
                s.name as store_name,
                u.name as cashier_name
            ')
            ->first();

        if (! $session) {
            return null;
        }

        $breakdown = $this->getSessionPaymentBreakdown($cashierSession);

        $session->payment_breakdown = $breakdown['rows'];
        $session->payment_total_amount = $breakdown['total_payment_amount'];
        $session->total_cash_amount = $breakdown['total_cash_amount'];
        $session->total_cash_transactions = $breakdown['total_cash_transactions'];
        $session->total_non_cash_amount = $breakdown['total_non_cash_amount'];
        $session->total_non_cash_transactions = $breakdown['total_non_cash_transactions'];
        $session->total_receivable_amount = $breakdown['total_receivable_amount'];

        return $session;
    }

    private function getSessionPaymentBreakdown(string $cashierSession): array
    {
        $rows = DB::table('cashier_session_payment_summaries as csps')
            ->join('payment_methods as pm', 'pm.id', '=', 'csps.payment_method_id')
            ->where('csps.cashier_session_id', $cashierSession)
            ->selectRaw('
                pm.name as payment_method_name,
                pm.type::text as payment_method_type,
                COALESCE(csps.total_amount, 0)::numeric as total_amount,
                COALESCE(csps.transaction_count, 0)::int as transaction_count
            ')
            ->orderBy('pm.type')
            ->orderBy('pm.name')
            ->get();

        return [
            'rows' => $rows,
            'total_payment_amount' => (float) $rows->sum('total_amount'),
            'total_cash_amount' => (float) $rows->where('payment_method_type', 'cash')->sum('total_amount'),
            'total_cash_transactions' => (int) $rows->where('payment_method_type', 'cash')->sum('transaction_count'),
            'total_non_cash_amount' => (float) $rows->whereIn('payment_method_type', ['non_cash', 'api'])->sum('total_amount'),
            'total_non_cash_transactions' => (int) $rows->whereIn('payment_method_type', ['non_cash', 'api'])->sum('transaction_count'),
            'total_receivable_amount' => (float) $rows->where('payment_method_type', 'receivable')->sum('total_amount'),
        ];
    }

    private function getSessionTransactions(string $cashierSession)
    {
        return DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->leftJoin('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->leftJoin('customers as c', 'c.id', '=', 'scv.customer_id')
            ->leftJoin('users as cashier', 'cashier.id', '=', 'svp.cashier_user_id')
            ->where('svp.cashier_session_id', $cashierSession)
            ->selectRaw('
                svp.id,
                svp.visit_id,
                svp.payment_id,
                svp.source,
                svp.note as store_payment_note,
                p.transaction_number,
                p.total_amount,
                p.tax_amount,
                p.paid_amount,
                p.change_amount,
                p.status as payment_status,
                p.note as payment_note,
                p.completed_at,
                c.name as customer_name,
                cashier.name as cashier_name
            ')
            ->orderByRaw('COALESCE(p.completed_at, p.created_at) DESC')
            ->get();
    }

    private function getTransactionItemsForExport(array $paymentIds)
    {
        if (empty($paymentIds)) {
            return collect();
        }

        return DB::table('store_visit_payments as svp')
            ->join('store_carts as sc', 'sc.visit_id', '=', 'svp.visit_id')
            ->join('store_cart_details as scd', 'scd.cart_id', '=', 'sc.id')
            ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
            ->whereIn('svp.payment_id', $paymentIds)
            ->where(function ($q) {
                $q->whereNull('scd.void_status')
                    ->orWhereNotIn('scd.void_status', ['void', 'voided', 'gimmick']);
            })
            ->selectRaw("
                svp.payment_id,
                scd.id,
                pr.code as product_code,
                COALESCE(pr.name, 'Produk tidak ditemukan') as product_name,
                1::numeric as qty,
                COALESCE(scd.product_price_amount, 0)::numeric as price,
                COALESCE(scd.discount_amount, 0)::numeric as discount_amount,
                (COALESCE(scd.product_price_amount, 0)::numeric - COALESCE(scd.discount_amount, 0)::numeric) as subtotal
            ")
            ->orderBy('svp.payment_id')
            ->orderBy('pr.name')
            ->get();
    }

    private function buildSessionQuery(array $filters)
    {
        $userStoreIds = $this->accessibleStoreIds();

        /*
        |--------------------------------------------------------------------------
        | Total kembalian per cashier session
        |--------------------------------------------------------------------------
        */
        $changeSub = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->selectRaw('
                svp.cashier_session_id,
                COALESCE(SUM(p.change_amount), 0)::numeric as total_change_amount
            ')
            ->groupBy('svp.cashier_session_id');

        /*
        |--------------------------------------------------------------------------
        | Total diskon per cashier session
        |--------------------------------------------------------------------------
        | Diskon produk dihitung dari detail keranjang, sedangkan voucher dan
        | poin dihitung sekali per keranjang agar tidak terduplikasi.
        */
        $cartDiscountSub = DB::table('store_carts as sc')
            ->leftJoin('store_cart_details as scd', 'scd.cart_id', '=', 'sc.id')
            ->where(function ($query): void {
                $query->whereNull('scd.void_status')
                    ->orWhereNotIn('scd.void_status', ['void', 'voided', 'gimmick']);
            })
            ->select([
                'sc.id',
                'sc.visit_id',
                'sc.voucher_discount',
                'sc.point_amount',
            ])
            ->selectRaw('COALESCE(SUM(COALESCE(scd.discount_amount, 0)), 0)::numeric as product_discount')
            ->groupBy('sc.id', 'sc.visit_id', 'sc.voucher_discount', 'sc.point_amount');

        $discountSub = DB::table('store_visit_payments as svp')
            ->joinSub($cartDiscountSub, 'cart', function ($join): void {
                $join->on('cart.visit_id', '=', 'svp.visit_id');
            })
            ->select('svp.cashier_session_id')
            ->selectRaw('COALESCE(SUM(cart.product_discount), 0)::numeric as total_product_discount')
            ->selectRaw('COALESCE(SUM(cart.voucher_discount), 0)::numeric as total_voucher_discount')
            ->selectRaw('COALESCE(SUM(cart.point_amount), 0)::numeric as total_point_discount')
            ->groupBy('svp.cashier_session_id');

        $paymentSummarySub = DB::table('cashier_session_payment_summaries as csps')
            ->join('payment_methods as pm', 'pm.id', '=', 'csps.payment_method_id')
            ->selectRaw("
                csps.cashier_session_id,
                COALESCE(SUM(csps.total_amount), 0)::numeric as total_payment_amount,
                COALESCE(SUM(csps.transaction_count), 0)::int as total_payment_count,
                COALESCE(SUM(CASE WHEN pm.type::text = 'cash' THEN csps.total_amount ELSE 0 END), 0)::numeric as total_cash_amount,
                COALESCE(SUM(CASE WHEN pm.type::text = 'cash' THEN csps.transaction_count ELSE 0 END), 0)::int as total_cash_transactions,
                COALESCE(SUM(CASE WHEN pm.type::text IN ('non_cash', 'api') THEN csps.total_amount ELSE 0 END), 0)::numeric as total_non_cash_amount,
                COALESCE(SUM(CASE WHEN pm.type::text IN ('non_cash', 'api') THEN csps.transaction_count ELSE 0 END), 0)::int as total_non_cash_transactions,
                COALESCE(SUM(CASE WHEN pm.type::text = 'receivable' THEN csps.total_amount ELSE 0 END), 0)::numeric as total_receivable_amount
            ")
            ->groupBy('csps.cashier_session_id');

        $attendanceShiftSub = $this->shiftResolver->attendanceShiftSubquery();
        $scheduledShiftSub = $this->shiftResolver->scheduledShiftSubquery();

        return DB::table('cashier_sessions as cs')
            ->join('stores as s', 's.id', '=', 'cs.store_id')
            ->join('users as u', 'u.id', '=', 'cs.user_id')
            ->leftJoinSub($changeSub, 'chg', function ($join) {
                $join->on('chg.cashier_session_id', '=', 'cs.id');
            })
            ->leftJoinSub($discountSub, 'disc', function ($join) {
                $join->on('disc.cashier_session_id', '=', 'cs.id');
            })
            ->leftJoinSub($paymentSummarySub, 'pay_sum', function ($join) {
                $join->on('pay_sum.cashier_session_id', '=', 'cs.id');
            })
            ->leftJoinSub($attendanceShiftSub, 'attendance_shift', function ($join) {
                $join->on('attendance_shift.store_id', '=', 'cs.store_id')
                    ->on('attendance_shift.user_id', '=', 'cs.user_id')
                    ->on('attendance_shift.shift_date', '=', DB::raw('DATE(cs.opened_at)'));
            })
            ->leftJoinSub($scheduledShiftSub, 'scheduled_shift', function ($join) {
                $join->on('scheduled_shift.store_id', '=', 'cs.store_id')
                    ->on('scheduled_shift.user_id', '=', 'cs.user_id')
                    ->on('scheduled_shift.shift_date', '=', DB::raw('DATE(cs.opened_at)'));
            })
            ->whereIn('cs.store_id', $userStoreIds)
            ->when($filters['date_from'] !== '' || $filters['date_to'] !== '', function ($q) use ($filters) {
                /*
                | A session belongs to the report period when it was opened or
                | closed in that period. Filtering only opened_at hides a
                | session that is closed today but was opened on an earlier day.
                */
                $q->where(function ($dateQuery) use ($filters) {
                    $dateQuery->where(function ($openedQuery) use ($filters) {
                        $openedQuery
                            ->when($filters['date_from'] !== '', function ($query) use ($filters) {
                                $query->whereDate('cs.opened_at', '>=', $filters['date_from']);
                            })
                            ->when($filters['date_to'] !== '', function ($query) use ($filters) {
                                $query->whereDate('cs.opened_at', '<=', $filters['date_to']);
                            });
                    })->orWhere(function ($closedQuery) use ($filters) {
                        $closedQuery
                            ->whereNotNull('cs.closed_at')
                            ->when($filters['date_from'] !== '', function ($query) use ($filters) {
                                $query->whereDate('cs.closed_at', '>=', $filters['date_from']);
                            })
                            ->when($filters['date_to'] !== '', function ($query) use ($filters) {
                                $query->whereDate('cs.closed_at', '<=', $filters['date_to']);
                            });
                    });
                });
            })
            ->when($filters['store_id'], function ($q) use ($filters) {
                $q->where('cs.store_id', $filters['store_id']);
            })
            ->when($filters['cashier_id'], function ($q) use ($filters) {
                $q->where('cs.user_id', $filters['cashier_id']);
            })
            ->when($filters['shift_id'], function ($q) use ($filters) {
                $q->whereRaw('COALESCE(attendance_shift.shift_id, scheduled_shift.shift_id) = ?', [$filters['shift_id']]);
            })
            ->when($filters['status'], function ($q) use ($filters) {
                $q->where('cs.status', $filters['status']);
            })
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $search = $filters['search'];

                $q->where(function ($qq) use ($search) {
                    $qq->where('u.name', 'ILIKE', "%{$search}%")
                        ->orWhere('s.name', 'ILIKE', "%{$search}%")
                        ->orWhere('cs.session_number', 'ILIKE', "%{$search}%")
                        ->orWhere('cs.status', 'ILIKE', "%{$search}%")
                        ->orWhereRaw("COALESCE(attendance_shift.shift_name, scheduled_shift.shift_name, '') ILIKE ?", ["%{$search}%"])
                        ->orWhere(DB::raw('CAST(cs.id AS TEXT)'), 'ILIKE', "%{$search}%");
                });
            })
            ->selectRaw("
                cs.id as cashier_session_id,
                cs.store_id,
                cs.user_id as cashier_id,
                TO_CHAR(cs.opened_at, 'DD-MM-YYYY') as period_label,
                s.name as store_name,
                u.name as cashier_name,
                cs.session_number,
                COALESCE(attendance_shift.shift_id, scheduled_shift.shift_id) as shift_id,
                COALESCE(attendance_shift.shift_name, scheduled_shift.shift_name, 'Tanpa Shift') as shift_name,
                COALESCE(attendance_shift.shift_code, scheduled_shift.shift_code) as shift_code,
                COALESCE(attendance_shift.shift_start_time, scheduled_shift.shift_start_time) as shift_start_time,
                cs.status as cashier_status,
                cs.opened_at,
                cs.closed_at,
                COALESCE(cs.opening_cash, 0)::numeric as opening_cash,
                COALESCE(cs.expected_cash, 0)::numeric as closing_cash_system,
                COALESCE(cs.actual_cash, 0)::numeric as closing_cash_physical,
                COALESCE(cs.difference, 0)::numeric as cash_difference,
                COALESCE(pay_sum.total_payment_amount, cs.total_sales, 0)::numeric as omzet,
                COALESCE(pay_sum.total_payment_count, cs.total_transactions, 0)::int as total_transactions,
                COALESCE(pay_sum.total_cash_amount, 0)::numeric as total_cash_amount,
                COALESCE(pay_sum.total_cash_transactions, 0)::int as total_cash_transactions,
                COALESCE(pay_sum.total_non_cash_amount, 0)::numeric as total_non_cash_amount,
                COALESCE(pay_sum.total_non_cash_transactions, 0)::int as total_non_cash_transactions,
                COALESCE(pay_sum.total_receivable_amount, 0)::numeric as total_receivable_amount,
                COALESCE(chg.total_change_amount, 0)::numeric as total_change_amount,
                COALESCE(disc.total_product_discount, 0)::numeric as total_product_discount,
                COALESCE(disc.total_voucher_discount, 0)::numeric as total_voucher_discount,
                COALESCE(disc.total_point_discount, 0)::numeric as total_point_discount,
                (
                    COALESCE(disc.total_product_discount, 0)
                    + COALESCE(disc.total_voucher_discount, 0)
                    + COALESCE(disc.total_point_discount, 0)
                )::numeric as total_discount_amount,
                1::int as total_sessions
            ");
    }

    private function buildDailyShiftReportQuery($baseQuery)
    {
        return DB::query()
            ->fromSub($baseQuery, 'x')
            ->selectRaw("
                DATE(x.opened_at) as report_date,
                TO_CHAR(DATE(x.opened_at), 'DD-MM-YYYY') as period_label,
                x.store_id,
                x.store_name,
                x.shift_id,
                x.shift_name,
                x.shift_code,
                x.shift_start_time,
                COUNT(DISTINCT x.cashier_id)::int as total_cashiers,
                COUNT(DISTINCT x.cashier_session_id)::int as total_sessions,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COALESCE(SUM(x.omzet), 0)::numeric as total_omzet,
                COALESCE(SUM(x.total_cash_amount), 0)::numeric as total_cash_amount,
                COALESCE(SUM(x.total_non_cash_amount), 0)::numeric as total_non_cash_amount,
                COALESCE(SUM(x.total_cash_transactions), 0)::int as total_cash_transactions,
                COALESCE(SUM(x.total_non_cash_transactions), 0)::int as total_non_cash_transactions,
                COALESCE(SUM(x.total_receivable_amount), 0)::numeric as total_receivable_amount,
                COALESCE(SUM(x.total_change_amount), 0)::numeric as total_change_amount,
                COALESCE(SUM(x.total_product_discount), 0)::numeric as total_product_discount,
                COALESCE(SUM(x.total_voucher_discount), 0)::numeric as total_voucher_discount,
                COALESCE(SUM(x.total_point_discount), 0)::numeric as total_point_discount,
                COALESCE(SUM(x.total_discount_amount), 0)::numeric as total_discount_amount,
                COALESCE(SUM(x.cash_difference), 0)::numeric as total_cash_difference
            ")
            ->groupByRaw('DATE(x.opened_at), x.store_id, x.store_name, x.shift_id, x.shift_name, x.shift_code, x.shift_start_time');
    }

    private function accessibleStoreIds(): array
    {
        return $this->accessibleStoresQuery()
            ->pluck('stores.id')
            ->toArray();
    }

    private function accessibleStoresQuery()
    {
        $user = Auth::user();

        if ($user->backoffice_access) {
            return DB::table('stores')
                ->whereNull('stores.deleted_at');
        }

        return $user->stores();
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
