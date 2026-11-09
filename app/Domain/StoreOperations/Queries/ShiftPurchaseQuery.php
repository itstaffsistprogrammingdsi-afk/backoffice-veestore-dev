<?php

namespace App\Domain\StoreOperations\Queries;

use App\Domain\StoreOperations\Support\CashierShiftResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShiftPurchaseQuery
{
    public function __construct(
        private readonly CashierShiftResolver $shiftResolver
    ) {}

    public function build(array $filters, int $perPage = 10): array
    {
        $sessionBase = $this->sessionBase($filters);

        $rows = DB::query()
            ->fromSub($sessionBase, 'x')
            ->groupByRaw('x.report_date, x.store_id, x.store_name, x.shift_id, x.shift_name, x.shift_code, x.shift_start_time')
            ->selectRaw("
                x.report_date,
                TO_CHAR(x.report_date, 'DD-MM-YYYY') as period_label,
                x.store_id,
                x.store_name,
                x.shift_id,
                x.shift_name,
                x.shift_code,
                x.shift_start_time,
                COUNT(DISTINCT x.cashier_id)::int as total_cashiers,
                COUNT(DISTINCT x.cashier_session_id)::int as total_sessions,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COALESCE(SUM(x.total_items), 0)::int as total_items,
                COALESCE(SUM(x.total_products), 0)::int as total_products,
                COALESCE(SUM(x.gross_amount), 0)::numeric as gross_amount,
                COALESCE(SUM(x.discount_amount), 0)::numeric as discount_amount,
                COALESCE(SUM(x.net_amount), 0)::numeric as net_amount,
                COALESCE(SUM(x.payment_total), 0)::numeric as payment_total
            ")
            ->orderByDesc('x.report_date')
            ->orderBy('x.store_name')
            ->orderBy('x.shift_start_time')
            ->paginate($perPage)
            ->withQueryString();

        $summary = DB::query()
            ->fromSub($sessionBase, 'x')
            ->selectRaw("
                COUNT(DISTINCT CONCAT(x.store_id, '|', COALESCE(x.shift_id::text, 'none'), '|', x.report_date))::int as total_shifts,
                COUNT(DISTINCT x.cashier_session_id)::int as total_sessions,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COALESCE(SUM(x.total_items), 0)::int as total_items,
                COALESCE(SUM(x.total_products), 0)::int as total_products,
                COALESCE(SUM(x.gross_amount), 0)::numeric as gross_amount,
                COALESCE(SUM(x.discount_amount), 0)::numeric as discount_amount,
                COALESCE(SUM(x.net_amount), 0)::numeric as net_amount,
                COALESCE(SUM(x.payment_total), 0)::numeric as payment_total
            ")
            ->first();

        $sessionIds = DB::query()
            ->fromSub($sessionBase, 'x')
            ->pluck('x.cashier_session_id')
            ->all();

        return [
            'rows' => $rows,
            'summary' => $summary,
            'productRows' => $this->productRows($sessionIds),
            'stores' => $this->accessibleStoresQuery()->select('stores.id', 'stores.name', 'stores.code')->orderBy('stores.name')->get(),
            'shifts' => $this->shifts(),
            'cashiers' => $this->cashiers(),
            'filters' => $filters,
        ];
    }

    public function exportData(array $filters): array
    {
        $sessionBase = $this->sessionBase($filters);

        $rows = DB::query()
            ->fromSub($sessionBase, 'x')
            ->groupByRaw('x.report_date, x.store_id, x.store_name, x.shift_id, x.shift_name, x.shift_code, x.shift_start_time')
            ->selectRaw("
                x.report_date,
                TO_CHAR(x.report_date, 'DD-MM-YYYY') as period_label,
                x.store_id,
                x.store_name,
                x.shift_id,
                x.shift_name,
                x.shift_code,
                x.shift_start_time,
                COUNT(DISTINCT x.cashier_id)::int as total_cashiers,
                COUNT(DISTINCT x.cashier_session_id)::int as total_sessions,
                COALESCE(SUM(x.total_transactions), 0)::int as total_transactions,
                COALESCE(SUM(x.total_items), 0)::int as total_items,
                COALESCE(SUM(x.total_products), 0)::int as total_products,
                COALESCE(SUM(x.gross_amount), 0)::numeric as gross_amount,
                COALESCE(SUM(x.discount_amount), 0)::numeric as discount_amount,
                COALESCE(SUM(x.net_amount), 0)::numeric as net_amount,
                COALESCE(SUM(x.payment_total), 0)::numeric as payment_total
            ")
            ->orderByDesc('x.report_date')
            ->orderBy('x.store_name')
            ->orderBy('x.shift_start_time')
            ->get();

        $sessionIds = DB::query()
            ->fromSub($sessionBase, 'x')
            ->pluck('x.cashier_session_id')
            ->all();

        $transactions = empty($sessionIds)
            ? collect()
            : DB::table('store_visit_payments as svp')
                ->join('payments as p', 'p.id', '=', 'svp.payment_id')
                ->leftJoin('store_customer_visits as visit', 'visit.id', '=', 'svp.visit_id')
                ->leftJoin('customers as customer', 'customer.id', '=', 'visit.customer_id')
                ->leftJoin('users as cashier', 'cashier.id', '=', 'svp.cashier_user_id')
                ->join('cashier_sessions as session', 'session.id', '=', 'svp.cashier_session_id')
                ->join('stores as store', 'store.id', '=', 'session.store_id')
                ->whereIn('svp.cashier_session_id', $sessionIds)
                ->where('p.status', 'paid')
                ->whereNull('p.deleted_at')
                ->selectRaw('
                    p.id,
                    p.transaction_number,
                    p.total_amount,
                    p.paid_amount,
                    p.change_amount,
                    p.completed_at,
                    p.status::text as status,
                    COALESCE(customer.name, \'Guest\') as customer_name,
                    cashier.name as cashier_name,
                    store.name as store_name,
                    session.session_number,
                    svp.cashier_session_id
                ')
                ->orderByDesc('p.completed_at')
                ->get();

        return [
            'rows' => $rows,
            'summary' => $this->summaryFromRows($rows),
            'products' => $this->productRows($sessionIds),
            'transactions' => $transactions,
        ];
    }

    public function detail(array $filters): array
    {
        $sessionBase = $this->sessionBase($filters);

        $sessions = DB::query()
            ->fromSub($sessionBase, 'x')
            ->selectRaw('
                x.cashier_session_id,
                x.session_number,
                x.store_name,
                x.cashier_name,
                x.shift_name,
                x.shift_code,
                x.shift_start_time,
                x.cashier_status,
                x.opened_at,
                x.closed_at,
                x.total_transactions,
                x.total_items,
                x.total_products,
                x.gross_amount,
                x.discount_amount,
                x.net_amount,
                x.payment_total
            ')
            ->orderBy('x.opened_at')
            ->get();

        $sessionIds = $sessions->pluck('cashier_session_id')->all();

        $transactions = empty($sessionIds)
            ? collect()
            : DB::table('store_visit_payments as svp')
                ->join('payments as p', 'p.id', '=', 'svp.payment_id')
                ->leftJoin('store_customer_visits as visit', 'visit.id', '=', 'svp.visit_id')
                ->leftJoin('customers as customer', 'customer.id', '=', 'visit.customer_id')
                ->leftJoin('users as cashier', 'cashier.id', '=', 'svp.cashier_user_id')
                ->whereIn('svp.cashier_session_id', $sessionIds)
                ->where('p.status', 'paid')
                ->whereNull('p.deleted_at')
                ->selectRaw('
                    p.id,
                    p.transaction_number,
                    p.total_amount,
                    p.paid_amount,
                    p.change_amount,
                    p.completed_at,
                    p.status::text as status,
                    COALESCE(customer.name, \'Guest\') as customer_name,
                    cashier.name as cashier_name,
                    svp.cashier_session_id
                ')
                ->orderByDesc('p.completed_at')
                ->get();

        return [
            'sessions' => $sessions,
            'products' => $this->productRows($sessionIds),
            'transactions' => $transactions,
            'summary' => [
                'total_sessions' => $sessions->count(),
                'total_transactions' => (int) $sessions->sum('total_transactions'),
                'total_items' => (int) $sessions->sum('total_items'),
                'gross_amount' => (float) $sessions->sum('gross_amount'),
                'discount_amount' => (float) $sessions->sum('discount_amount'),
                'net_amount' => (float) $sessions->sum('net_amount'),
            ],
        ];
    }

    private function sessionBase(array $filters)
    {
        $storeIds = $this->accessibleStoreIds();

        return DB::table('cashier_sessions as cs')
            ->join('stores as s', 's.id', '=', 'cs.store_id')
            ->join('users as u', 'u.id', '=', 'cs.user_id')
            ->leftJoinSub($this->shiftResolver->attendanceShiftSubquery(), 'attendance_shift', function ($join): void {
                $join->on('attendance_shift.store_id', '=', 'cs.store_id')
                    ->on('attendance_shift.user_id', '=', 'cs.user_id')
                    ->on('attendance_shift.shift_date', '=', DB::raw('DATE(cs.opened_at)'));
            })
            ->leftJoinSub($this->shiftResolver->scheduledShiftSubquery(), 'scheduled_shift', function ($join): void {
                $join->on('scheduled_shift.store_id', '=', 'cs.store_id')
                    ->on('scheduled_shift.user_id', '=', 'cs.user_id')
                    ->on('scheduled_shift.shift_date', '=', DB::raw('DATE(cs.opened_at)'));
            })
            ->leftJoinSub($this->cartAggSubquery(), 'cart_agg', 'cart_agg.cashier_session_id', '=', 'cs.id')
            ->leftJoinSub($this->paymentAggSubquery(), 'payment_agg', 'payment_agg.cashier_session_id', '=', 'cs.id')
            ->whereIn('cs.store_id', $storeIds)
            ->when($filters['from'], fn ($query) => $query->whereDate('cs.opened_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('cs.opened_at', '<=', $filters['to']))
            ->when($filters['store_id'], fn ($query) => $query->where('cs.store_id', $filters['store_id']))
            ->when($filters['cashier_id'], fn ($query) => $query->where('cs.user_id', $filters['cashier_id']))
            ->when($filters['shift_id'], fn ($query) => $query->whereRaw('COALESCE(attendance_shift.shift_id, scheduled_shift.shift_id) = ?', [$filters['shift_id']]))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $q = $filters['q'];

                $query->where(function ($inner) use ($q): void {
                    $inner->where('u.name', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%")
                        ->orWhere('cs.session_number', 'ILIKE', "%{$q}%")
                        ->orWhereRaw("COALESCE(attendance_shift.shift_name, scheduled_shift.shift_name, '') ILIKE ?", ["%{$q}%"])
                        ->orWhereExists(function ($sub) use ($q): void {
                            $sub->selectRaw('1')
                                ->from('store_visit_payments as search_payment')
                                ->join('payments as search_payment_main', 'search_payment_main.id', '=', 'search_payment.payment_id')
                                ->join('store_carts as search_cart', 'search_cart.visit_id', '=', 'search_payment.visit_id')
                                ->join('store_cart_details as search_detail', 'search_detail.cart_id', '=', 'search_cart.id')
                                ->join('items as search_item', 'search_item.id', '=', 'search_detail.item_id')
                                ->join('products as search_product', 'search_product.id', '=', 'search_item.product_id')
                                ->whereColumn('search_payment.cashier_session_id', 'cs.id')
                                ->where('search_payment_main.status', 'paid')
                                ->where(function ($productQuery) use ($q): void {
                                    $productQuery->where('search_product.name', 'ILIKE', "%{$q}%")
                                        ->orWhere('search_product.code', 'ILIKE', "%{$q}%");
                                });
                        });
                });
            })
            ->selectRaw("
                cs.id as cashier_session_id,
                cs.store_id,
                cs.user_id as cashier_id,
                DATE(cs.opened_at) as report_date,
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
                COALESCE(payment_agg.total_transactions, 0)::int as total_transactions,
                COALESCE(payment_agg.payment_total, 0)::numeric as payment_total,
                COALESCE(cart_agg.total_items, 0)::int as total_items,
                COALESCE(cart_agg.total_products, 0)::int as total_products,
                COALESCE(cart_agg.gross_amount, 0)::numeric as gross_amount,
                COALESCE(cart_agg.discount_amount, 0)::numeric as discount_amount,
                COALESCE(cart_agg.net_amount, 0)::numeric as net_amount
            ");
    }

    private function cartAggSubquery()
    {
        $sessionCart = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->join('store_carts as cart', 'cart.visit_id', '=', 'svp.visit_id')
            ->where('p.status', 'paid')
            ->whereNull('p.deleted_at')
            ->select('svp.cashier_session_id', 'cart.id as cart_id')
            ->distinct();

        return DB::query()
            ->fromSub($sessionCart, 'session_cart')
            ->join('store_cart_details as detail', 'detail.cart_id', '=', 'session_cart.cart_id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->where(function ($query): void {
                $query->whereNull('detail.void_status')
                    ->orWhere('detail.void_status', '!=', 'void');
            })
            ->groupBy('session_cart.cashier_session_id')
            ->selectRaw('
                session_cart.cashier_session_id,
                COUNT(detail.id)::int as total_items,
                COUNT(DISTINCT item.product_id)::int as total_products,
                COALESCE(SUM(detail.product_price_amount), 0)::numeric as gross_amount,
                COALESCE(SUM(detail.discount_amount), 0)::numeric as discount_amount,
                COALESCE(SUM(detail.product_price_amount - COALESCE(detail.discount_amount, 0)), 0)::numeric as net_amount
            ');
    }

    private function paymentAggSubquery()
    {
        return DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->where('p.status', 'paid')
            ->whereNull('p.deleted_at')
            ->groupBy('svp.cashier_session_id')
            ->selectRaw('
                svp.cashier_session_id,
                COUNT(DISTINCT p.id)::int as total_transactions,
                COALESCE(SUM(p.total_amount), 0)::numeric as payment_total
            ');
    }

    private function productRows(array $sessionIds)
    {
        if ($sessionIds === []) {
            return collect();
        }

        $sessionCart = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->join('store_carts as cart', 'cart.visit_id', '=', 'svp.visit_id')
            ->whereIn('svp.cashier_session_id', $sessionIds)
            ->where('p.status', 'paid')
            ->whereNull('p.deleted_at')
            ->select('svp.cashier_session_id', 'cart.id as cart_id')
            ->distinct();

        return DB::query()
            ->fromSub($sessionCart, 'session_cart')
            ->join('store_cart_details as detail', 'detail.cart_id', '=', 'session_cart.cart_id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->join('products as product', 'product.id', '=', 'item.product_id')
            ->where(function ($query): void {
                $query->whereNull('detail.void_status')
                    ->orWhere('detail.void_status', '!=', 'void');
            })
            ->groupBy('product.id', 'product.code', 'product.name')
            ->selectRaw('
                product.id as product_id,
                product.code as product_code,
                product.name as product_name,
                COUNT(detail.id)::int as qty,
                COALESCE(SUM(detail.product_price_amount), 0)::numeric as gross_amount,
                COALESCE(SUM(detail.discount_amount), 0)::numeric as discount_amount,
                COALESCE(SUM(detail.product_price_amount - COALESCE(detail.discount_amount, 0)), 0)::numeric as net_amount,
                COUNT(DISTINCT session_cart.cashier_session_id)::int as session_count
            ')
            ->orderByDesc('qty')
            ->orderBy('product.name')
            ->limit(100)
            ->get();
    }

    private function shifts()
    {
        return DB::table('store_attendance_shifts as shift')
            ->join('stores as store', 'store.id', '=', 'shift.store_id')
            ->whereIn('shift.store_id', $this->accessibleStoreIds())
            ->where('shift.is_active', true)
            ->orderBy('store.name')
            ->orderBy('shift.start_time')
            ->get([
                'shift.id',
                'shift.store_id',
                'shift.name',
                'shift.code',
                'shift.start_time',
                'shift.end_time',
                'store.name as store_name',
            ]);
    }

    private function cashiers()
    {
        return DB::table('users')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                $query->where('store_access', true)
                    ->orWhere('store_access', 1)
                    ->orWhere('store_access', 't');
            })
            ->orderBy('name')
            ->get();
    }

    private function accessibleStoreIds(): array
    {
        return $this->accessibleStoresQuery()->pluck('stores.id')->toArray();
    }

    private function accessibleStoresQuery()
    {
        $user = Auth::user();

        if ($user?->backoffice_access) {
            return DB::table('stores')->whereNull('stores.deleted_at');
        }

        return $user?->stores() ?? DB::table('stores')->whereRaw('1 = 0');
    }

    private function summaryFromRows($rows): array
    {
        return [
            'total_shifts' => $rows->count(),
            'total_sessions' => (int) $rows->sum('total_sessions'),
            'total_transactions' => (int) $rows->sum('total_transactions'),
            'total_items' => (int) $rows->sum('total_items'),
            'total_products' => (int) $rows->sum('total_products'),
            'gross_amount' => (float) $rows->sum('gross_amount'),
            'discount_amount' => (float) $rows->sum('discount_amount'),
            'net_amount' => (float) $rows->sum('net_amount'),
            'payment_total' => (float) $rows->sum('payment_total'),
        ];
    }

    public static function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
