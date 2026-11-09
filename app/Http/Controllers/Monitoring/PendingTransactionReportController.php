<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PendingTransactionReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $base = $this->baseQuery($start, $end)
            ->when($filters['source_type'] !== '', fn ($q) => $q->where('source_type', $filters['source_type']))
            ->when($filters['store_id'] !== '', fn ($q) => $q->where('store_id', $filters['store_id']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('reference', 'ILIKE', $like)
                        ->orWhere('store_name', 'ILIKE', $like)
                        ->orWhere('customer_name', 'ILIKE', $like)
                        ->orWhere('pic_name', 'ILIKE', $like);
                });
            })
            ->when($filters['min_age_days'] > 0, fn ($q) => $q->where('age_days', '>=', $filters['min_age_days']));

        $rows = (clone $base)
            ->orderByDesc('age_days')
            ->orderByDesc('occurred_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $byType = DB::query()
            ->fromSub(clone $base, 'pending')
            ->selectRaw('
                source_type,
                COUNT(*)::int AS total,
                COALESCE(SUM(amount), 0)::numeric AS total_amount,
                MAX(age_days)::int AS max_age_days
            ')
            ->groupBy('source_type')
            ->orderByDesc('total')
            ->get();

        $summary = DB::query()
            ->fromSub(clone $base, 'pending')
            ->selectRaw('
                COUNT(*)::int AS total_pending,
                COUNT(DISTINCT store_id)::int AS affected_stores,
                COALESCE(SUM(amount), 0)::numeric AS total_amount,
                COALESCE(MAX(age_days), 0)::int AS oldest_age_days,
                COUNT(*) FILTER (WHERE age_days >= 7)::int AS over_seven_days
            ')
            ->first();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Transaksi Gantung / Belum Selesai',
            'description' => 'Pantau keranjang, kunjungan, pesanan, permintaan barang, retur, dan pembayaran yang belum selesai agar tidak menjadi transaksi hilang.',
            'data' => [
                'rows' => $rows,
                'pendingByType' => $byType,
                'summary' => (array) $summary,
                'stores' => $stores,
                'filters' => $filters,
                'sourceTypes' => [
                    ['value' => 'POS - Keranjang', 'label' => 'POS - Keranjang'],
                    ['value' => 'POS - Kunjungan', 'label' => 'POS - Kunjungan'],
                    ['value' => 'Pesanan Ecommerce', 'label' => 'Pesanan Ecommerce'],
                    ['value' => 'Permintaan Barang Toko', 'label' => 'Permintaan Barang Toko'],
                    ['value' => 'Pembayaran', 'label' => 'Pembayaran'],
                    ['value' => 'Retur Toko', 'label' => 'Retur Toko'],
                ],
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function baseQuery(Carbon $start, Carbon $end)
    {
        $cartAmount = '(SELECT COALESCE(SUM(d.product_price_amount - COALESCE(d.discount_amount, 0)), 0) FROM store_cart_details d WHERE d.cart_id = sc.id)';
        $storeOrderAmount = '(SELECT COALESCE(SUM(d.approved_qty * d.product_price), 0) FROM store_order_details d WHERE d.store_order_id = so.id)';
        $returnQty = '(SELECT COUNT(*) FROM store_return_details d WHERE d.store_return_id = sr.id)';

        $cart = DB::table('store_carts as sc')
            ->join('stores as s', 's.id', '=', 'sc.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'sc.customer_id')
            ->whereRaw("LOWER(sc.status::text) IN ('pending','on_hold')")
            ->whereBetween('sc.created_at', [$start, $end])
            ->selectRaw("
                'POS - Keranjang' AS source_type,
                sc.id::text AS reference,
                sc.store_id,
                s.name AS store_name,
                NULL::text AS warehouse_name,
                COALESCE(c.name, 'Guest / Non Member') AS customer_name,
                sc.status::text AS status,
                {$cartAmount}::numeric AS amount,
                sc.created_at AS occurred_at,
                GREATEST(EXTRACT(DAY FROM NOW() - sc.created_at)::int, 0) AS age_days,
                NULL::text AS pic_name,
                NULL::text AS details
            ");

        $visit = DB::table('store_customer_visits as v')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->whereRaw("LOWER(v.status::text) IN ('active','on_hold')")
            ->whereBetween('v.created_at', [$start, $end])
            ->selectRaw("
                'POS - Kunjungan' AS source_type,
                COALESCE(v.visit_code, v.id::text) AS reference,
                v.store_id,
                s.name AS store_name,
                NULL::text AS warehouse_name,
                COALESCE(c.name, 'Guest / Non Member') AS customer_name,
                v.status::text AS status,
                0::numeric AS amount,
                COALESCE(v.check_in_at, v.created_at) AS occurred_at,
                GREATEST(EXTRACT(DAY FROM NOW() - COALESCE(v.check_in_at, v.created_at))::int, 0) AS age_days,
                NULL::text AS pic_name,
                NULL::text AS details
            ");

        $ecom = DB::table('ecommerce_orders as eo')
            ->leftJoin('stores as s', 's.id', '=', 'eo.handled_by_store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'eo.handled_by_warehouse_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->whereRaw("LOWER(eo.status::text) IN ('pending','paid','packing','processed','shipped')")
            ->whereBetween('eo.created_at', [$start, $end])
            ->selectRaw("
                'Pesanan Ecommerce' AS source_type,
                eo.order_number AS reference,
                eo.handled_by_store_id AS store_id,
                COALESCE(s.name, '-') AS store_name,
                COALESCE(w.name, '-') AS warehouse_name,
                COALESCE(c.name, 'Guest / Non Member') AS customer_name,
                eo.status::text AS status,
                COALESCE(eo.grand_total, 0)::numeric AS amount,
                eo.created_at AS occurred_at,
                GREATEST(EXTRACT(DAY FROM NOW() - eo.created_at)::int, 0) AS age_days,
                NULL::text AS pic_name,
                NULL::text AS details
            ");

        $storeOrder = DB::table('store_orders as so')
            ->whereRaw("COALESCE(so.order_number, '') NOT LIKE 'TRF-%'")
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'so.warehouse_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'so.created_by')
            ->whereRaw("LOWER(so.status::text) IN ('draft','requested','approved','partial_approved','processing','packing','partial_received')")
            ->whereBetween('so.created_at', [$start, $end])
            ->selectRaw("
                'Permintaan Barang Toko' AS source_type,
                so.order_number AS reference,
                so.store_id,
                s.name AS store_name,
                COALESCE(w.name, '-') AS warehouse_name,
                '-' AS customer_name,
                so.status::text AS status,
                {$storeOrderAmount}::numeric AS amount,
                COALESCE(so.requested_at, so.created_at) AS occurred_at,
                GREATEST(EXTRACT(DAY FROM NOW() - COALESCE(so.requested_at, so.created_at))::int, 0) AS age_days,
                COALESCE(creator.name, '-') AS pic_name,
                NULL::text AS details
            ");

        $payment = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->join('store_customer_visits as v', 'v.id', '=', 'svp.visit_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
            ->whereRaw("LOWER(p.status::text) IN ('pending')")
            ->whereNull('p.deleted_at')
            ->whereBetween('p.created_at', [$start, $end])
            ->selectRaw("
                'Pembayaran' AS source_type,
                COALESCE(p.transaction_number, p.id::text) AS reference,
                v.store_id,
                s.name AS store_name,
                NULL::text AS warehouse_name,
                COALESCE(c.name, 'Guest / Non Member') AS customer_name,
                p.status::text AS status,
                COALESCE(p.total_amount, 0)::numeric AS amount,
                p.created_at AS occurred_at,
                GREATEST(EXTRACT(DAY FROM NOW() - p.created_at)::int, 0) AS age_days,
                NULL::text AS pic_name,
                NULL::text AS details
            ");

        $return = DB::table('store_returns as sr')
            ->join('stores as s', 's.id', '=', 'sr.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'sr.warehouse_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'sr.created_by')
            ->whereRaw("LOWER(sr.status::text) NOT IN ('received','rejected','close')")
            ->whereBetween('sr.created_at', [$start, $end])
            ->selectRaw("
                'Retur Toko' AS source_type,
                COALESCE(sr.return_number, sr.id::text) AS reference,
                sr.store_id,
                s.name AS store_name,
                COALESCE(w.name, '-') AS warehouse_name,
                '-' AS customer_name,
                sr.status::text AS status,
                0::numeric AS amount,
                sr.created_at AS occurred_at,
                GREATEST(EXTRACT(DAY FROM NOW() - sr.created_at)::int, 0) AS age_days,
                COALESCE(creator.name, '-') AS pic_name,
                {$returnQty}::text AS details
            ");

        return DB::query()->fromSub(
            $cart->unionAll($visit)->unionAll($ecom)->unionAll($storeOrder)->unionAll($payment)->unionAll($return),
            'pending'
        );
    }

    private function filters(Request $request): array
    {
        $from = $this->date($request->query('start_date')) ?: now()->subDays(30)->toDateString();
        $to = $this->date($request->query('end_date')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $sourceType = trim((string) $request->query('source_type', ''));

        return [
            'q' => trim((string) $request->query('q', '')),
            'source_type' => $sourceType,
            'store_id' => trim((string) $request->query('store_id', '')),
            'min_age_days' => max(0, (int) $request->query('min_age_days', 0)),
            'start_date' => $from,
            'end_date' => $to,
            'per_page' => $this->perPage($request->query('per_page')),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
