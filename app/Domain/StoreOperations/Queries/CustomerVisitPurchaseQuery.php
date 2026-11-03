<?php

namespace App\Domain\StoreOperations\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerVisitPurchaseQuery
{
    public function build(string $q, ?string $storeId): array
    {
        $visits = $this->visitRows($q, $storeId);
        $payments = $this->paymentRows($q, $storeId)->keyBy(fn (object $row) => $row->customer_id.'|'.$row->store_id);
        $items = $this->itemRows($q, $storeId)->groupBy(fn (object $row) => $row->customer_id.'|'.$row->store_id);
        $visitDetails = $this->visitDetailRows($q, $storeId);

        $rows = $visits->map(function (object $visit) use ($payments, $items, $visitDetails) {
            $key = $visit->customer_id.'|'.$visit->store_id;
            $payment = $payments->get($key);
            $products = collect($items->get($key, []));

            return [
                'customer_id' => $visit->customer_id,
                'customer_name' => $visit->customer_name,
                'wa_number' => $visit->wa_number,
                'email' => $visit->email,
                'membership_tier' => $visit->membership_tier,
                'loyalty_points' => (float) ($visit->loyalty_points ?? 0),
                'store_id' => $visit->store_id,
                'store_name' => $visit->store_name,
                'visit_count' => (int) $visit->visit_count,
                'last_visit_at' => $visit->last_visit_at,
                'visits' => collect($visitDetails->get($key, []))
                    ->map(fn (object $row) => [
                        'id' => $row->id,
                        'visit_code' => $row->visit_code,
                        'visit_at' => $row->visit_at,
                        'check_in_at' => $row->check_in_at,
                        'check_out_at' => $row->check_out_at,
                        'status' => $row->status,
                    ])
                    ->values()
                    ->all(),
                'purchase_count' => (int) ($payment->purchase_count ?? 0),
                'total_spending' => (float) ($payment->total_spending ?? 0),
                'item_qty' => (int) $products->sum('qty'),
                'products' => $products
                    ->map(fn (object $row) => [
                        'product_id' => $row->product_id,
                        'product_code' => $row->product_code,
                        'product_name' => $row->product_name,
                        'qty' => (int) $row->qty,
                        'spending' => (float) $row->spending,
                    ])
                    ->values()
                    ->all(),
            ];
        })->sortByDesc('total_spending')->values();

        $page = Paginator::resolveCurrentPage();
        $perPage = 10;
        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return [
            'rows' => $paginator,
            'summary' => [
                'total_customers' => $rows->count(),
                'total_visits' => (int) $rows->sum('visit_count'),
                'paid_transactions' => (int) $rows->sum('purchase_count'),
                'total_spending' => (float) $rows->sum('total_spending'),
            ],
        ];
    }

    private function visitRows(string $q, ?string $storeId): Collection
    {
        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->whereNull('c.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($storeId, fn ($query) => $query->where('v.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('c.name', 'ILIKE', "%{$q}%")
                        ->orWhere('c.email', 'ILIKE', "%{$q}%")
                        ->orWhere('c.wa_number', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%")
                        ->orWhere('v.visit_code', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                c.id as customer_id,
                c.name as customer_name,
                c.wa_number,
                c.email,
                (
                    SELECT m.name
                    FROM customer_memberships cm
                    JOIN memberships m ON m.id = cm.membership_id
                    WHERE cm.customer_id = c.id
                      AND cm.status = \'active\'
                    ORDER BY cm.updated_at DESC
                    LIMIT 1
                ) as membership_tier,
                (
                    SELECT clp.balance
                    FROM customer_loyalty_points clp
                    WHERE clp.customer_id = c.id
                    ORDER BY clp.updated_at DESC
                    LIMIT 1
                ) as loyalty_points,
                s.id as store_id,
                s.name as store_name,
                COUNT(DISTINCT v.id)::int as visit_count,
                MAX(v.check_in_at) as last_visit_at
            ')
            ->groupBy('c.id', 'c.name', 'c.wa_number', 'c.email', 's.id', 's.name')
            ->get();
    }

    private function visitDetailRows(string $q, ?string $storeId): Collection
    {
        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->whereNull('c.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($storeId, fn ($query) => $query->where('v.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('c.name', 'ILIKE', "%{$q}%")
                        ->orWhere('c.email', 'ILIKE', "%{$q}%")
                        ->orWhere('c.wa_number', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%")
                        ->orWhere('v.visit_code', 'ILIKE', "%{$q}%");
                });
            })
            ->select([
                'v.id',
                'v.visit_code',
                'v.check_in_at',
                'v.check_out_at',
                'v.status',
                'c.id as customer_id',
                's.id as store_id',
                DB::raw('COALESCE(v.check_in_at, v.created_at) as visit_at'),
            ])
            ->orderByDesc(DB::raw('COALESCE(v.check_in_at, v.created_at)'))
            ->get()
            ->groupBy(fn (object $row) => $row->customer_id.'|'.$row->store_id)
            ->map(fn (Collection $group) => $group->take(20)->values());
    }

    private function paymentRows(string $q, ?string $storeId): Collection
    {
        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->join('store_visit_payments as vp', 'vp.visit_id', '=', 'v.id')
            ->join('payments as p', 'p.id', '=', 'vp.payment_id')
            ->whereNull('c.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('p.status', 'paid')
            ->when($storeId, fn ($query) => $query->where('v.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('c.name', 'ILIKE', "%{$q}%")
                        ->orWhere('c.email', 'ILIKE', "%{$q}%")
                        ->orWhere('c.wa_number', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                c.id as customer_id,
                s.id as store_id,
                COUNT(DISTINCT p.id)::int as purchase_count,
                COALESCE(SUM(p.total_amount), 0)::numeric as total_spending
            ')
            ->groupBy('c.id', 's.id')
            ->get();
    }

    private function itemRows(string $q, ?string $storeId): Collection
    {
        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->join('store_carts as cart', function ($join) {
                $join->on('cart.visit_id', '=', 'v.id')
                    ->on('cart.store_id', '=', 'v.store_id')
                    ->on('cart.customer_id', '=', 'v.customer_id');
            })
            ->join('store_cart_details as cd', 'cd.cart_id', '=', 'cart.id')
            ->join('items as i', 'i.id', '=', 'cd.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereNull('c.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('cart.status', 'paid')
            ->when($storeId, fn ($query) => $query->where('v.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('c.name', 'ILIKE', "%{$q}%")
                        ->orWhere('c.email', 'ILIKE', "%{$q}%")
                        ->orWhere('c.wa_number', 'ILIKE', "%{$q}%")
                        ->orWhere('s.name', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                c.id as customer_id,
                s.id as store_id,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(cd.id)::int as qty,
                COALESCE(SUM(cd.product_price_amount - COALESCE(cd.discount_amount, 0)), 0)::numeric as spending
            ')
            ->groupBy('c.id', 's.id', 'p.id', 'p.code', 'p.name')
            ->orderByDesc('qty')
            ->get();
    }
}
