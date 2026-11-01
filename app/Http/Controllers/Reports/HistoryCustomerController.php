<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class HistoryCustomerController extends Controller
{
    /** Status yang dianggap paid/sukses (lowercase). */
    private function successStatuses(): array
    {
        return ['paid', 'success', 'settlement', 'completed', 'processed', 'shipped', 'received'];
    }

    public function index(Request $request)
    {
        // ========== Filters ==========
        $from = $request->input('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->input('to') ?: now()->toDateString();
        $channel = $request->input('channel', 'pos');   // 'pos' | 'ecom'
        $storeId = $request->input('store_id');         // uuid|null
        $search = $request->input('q');                // cari nama/wa/produk
        $custId = $request->input('customer_id');      // filter by customer

        $fromTs = Carbon::parse($from)->startOfDay();
        $toTs = Carbon::parse($to)->endOfDay();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        $rowsPos = collect(); // list transaksi POS (per item, tanpa harga)
        $rowsEcom = collect(); // list baris cart ECOM yang terbayar (produk & qty), tanpa harga

        // ========== STORE (POS) — HANYA VISIT YANG SUDAH ADA PEMBAYARAN ==========
        if ($channel === 'pos') {
            if (
                Schema::hasTable('store_customer_visits') &&
                Schema::hasTable('store_carts') &&
                Schema::hasTable('store_cart_details') &&
                Schema::hasTable('store_visit_payments') &&
                Schema::hasTable('payments')
            ) {
                $paid = $this->successStatuses();

                // Subquery paid_visits: visit yang punya payment valid + timestamp paid_at
                $paidVisits = DB::table('store_visit_payments as svp')
                    ->join('payments as p', 'p.id', '=', 'svp.payment_id')
                    ->whereNull('p.deleted_at')
                    ->where(function ($w) use ($paid) {
                        $w->whereNotNull('p.completed_at')
                            ->orWhereIn(DB::raw('lower(p.status::text)'), $paid);
                    })
                    ->selectRaw('svp.visit_id, MIN(COALESCE(p.completed_at, p.created_at)) AS paid_at')
                    ->groupBy('svp.visit_id');

                // Join ke visit → carts → cart_details → items/product_prices → products (nama produk), TANPA harga
                $q = DB::query()->fromSub($paidVisits, 'pv')
                    ->join('store_customer_visits as v', 'v.id', '=', 'pv.visit_id')
                    ->join('stores as s', 's.id', '=', 'v.store_id')
                    ->leftJoin('customers as c', 'c.id', '=', 'v.customer_id')
                    ->join('store_carts as sc', 'sc.visit_id', '=', 'v.id')
                    ->join('store_cart_details as d', 'd.cart_id', '=', 'sc.id')
                    ->leftJoin('items as it', 'it.id', '=', 'd.item_id')
                    ->leftJoin('product_prices as pp', 'pp.id', '=', 'd.product_price_id')
                    ->leftJoin('products as pr', function ($j) {
                        $j->on('pr.id', '=', 'it.product_id')
                            ->orOn('pr.id', '=', 'pp.product_id');
                    })
                    ->selectRaw('
                        v.id               AS visit_id,
                        v.check_in_at,
                        pv.paid_at,               -- waktu dibayar
                        s.id               AS store_id,
                        s.name             AS store_name,
                        c.id               AS customer_id,
                        c.name             AS customer_name,
                        c.wa_number,
                        pr.id              AS product_id,
                        pr.name            AS product_name
                    ')
                    ->whereBetween('pv.paid_at', [$fromTs, $toTs])      // periode pakai paid_at
                    ->when($storeId, fn ($w) => $w->where('v.store_id', $storeId))
                    ->when($custId, fn ($w) => $w->where('v.customer_id', $custId))
                    ->when($search, function ($w) use ($search) {
                        $like = '%'.$search.'%';
                        $w->where(function ($w2) use ($like) {
                            $w2->where('c.name', 'ilike', $like)
                                ->orWhere('c.wa_number', 'ilike', $like)
                                ->orWhere('pr.name', 'ilike', $like)
                                ->orWhere('s.name', 'ilike', $like);
                        });
                    })
                    ->orderByDesc('pv.paid_at')
                    ->limit(300);

                $rowsPos = $q->get();
            }
        }

        // ========== E-COMMERCE — SUMBER UTAMA: ecommerce_carts → payments ==========
        if ($channel === 'ecom') {
            if (
                Schema::hasTable('ecommerce_carts') &&
                Schema::hasTable('ecommerce_payments') &&
                Schema::hasTable('payments') &&
                Schema::hasTable('customers')
            ) {
                $paid = $this->successStatuses();

                // Semua payment e-com yang valid pada periode
                $paidEvents = DB::table('ecommerce_payments as ep')
                    ->join('payments as p', 'p.id', '=', 'ep.payment_id')
                    ->whereNull('p.deleted_at')
                    ->where(function ($w) use ($paid) {
                        $w->whereNotNull('p.completed_at')
                            ->orWhereIn(DB::raw('lower(p.status::text)'), $paid);
                    })
                    ->selectRaw('
                        ep.customer_id,
                        ep.ecommerce_order_id,
                        p.id as payment_id,
                        COALESCE(p.completed_at, p.created_at) as paid_at
                    ');

                // Gabungkan carts berdasarkan customer & periode (no harga)
                // Catatan: karena carts tidak punya order_id, kita tautkan via customer_id
                // dan filter waktu cart berada dalam window yang dipilih.
                $q = DB::query()->fromSub($paidEvents, 'pe')
                    ->join('customers as c', 'c.id', '=', 'pe.customer_id')
                    ->join('ecommerce_carts as ec', 'ec.customer_id', '=', 'pe.customer_id')
                    ->leftJoin('ecommerce_orders as o', 'o.id', '=', 'pe.ecommerce_order_id') // opsional
                    ->leftJoin('products as pr', 'pr.id', '=', 'ec.product_id')
                    ->selectRaw('
                        ec.id                    as cart_id,
                        ec.created_at            as cart_at,
                        pe.paid_at               as paid_at,
                        pe.payment_id,
                        pe.ecommerce_order_id,
                        o.order_number,
                        c.id                     as customer_id,
                        c.name                   as customer_name,
                        c.wa_number,
                        pr.id                    as product_id,
                        pr.name                  as product_name,
                        COALESCE(ec.quantity,0)  as qty
                    ')
                    // periode disaring oleh cart_at agar sesuai permintaan user
                    ->whereBetween('ec.created_at', [$fromTs, $toTs])
                    ->when($custId, fn ($w) => $w->where('pe.customer_id', $custId))
                    ->when($search, function ($w) use ($search) {
                        $like = '%'.$search.'%';
                        $w->where(function ($w2) use ($like) {
                            $w2->where('c.name', 'ilike', $like)
                                ->orWhere('c.wa_number', 'ilike', $like)
                                ->orWhere('pr.name', 'ilike', $like)
                                ->orWhere('o.order_number', 'ilike', $like);
                        });
                    })
                    // jika ingin hanya carts yang waktunya tidak setelah pembayaran pertama,
                    // aktifkan baris di bawah:
                    // ->whereColumn('ec.created_at', '<=', 'pe.paid_at')
                    ->orderByDesc('pe.paid_at')  // urut berdasarkan waktu bayar
                    ->limit(500);

                $rowsEcom = $q->get();
            }
        }

        return Inertia::render('Reports/HistoryCustomer', compact(
            'from',
            'to',
            'channel',
            'storeId',
            'stores',
            'search',
            'custId',
            'rowsPos',
            'rowsEcom'
        ) + [
            'urls' => [
                'index' => route('reports.history-customer.index'),
                'ecom' => route('reports.history-customer.ecom.detail', ['customerId' => '__ID__']),
            ],
        ]);
    }

    // ---- Detail E-commerce (masih placeholder, bisa dikembangkan nanti) ----
    public function ecomDetail(Request $request, string $customerId)
    {
        $from = $request->input('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->input('to') ?: now()->toDateString();
        $fromTs = Carbon::parse($from)->startOfDay();
        $toTs = Carbon::parse($to)->endOfDay();

        $orders = collect();
        if (Schema::hasTable('ecommerce_orders')) {
            $orders = DB::table('ecommerce_orders as o')
                ->selectRaw('
                    o.id as order_id,
                    COALESCE(o.created_at, o.updated_at) as order_date
                ')
                ->where('o.customer_id', $customerId)
                ->whereBetween(DB::raw('COALESCE(o.created_at, o.updated_at)'), [$fromTs, $toTs])
                ->orderByDesc('order_date')
                ->limit(500)
                ->get();
        }

        return Inertia::render('Reports/HistoryCustomerEcom', compact(
            'customerId',
            'from',
            'to',
            'orders'
        ) + [
            'urls' => [
                'index' => route('reports.history-customer.index'),
            ],
        ]);
    }
}
