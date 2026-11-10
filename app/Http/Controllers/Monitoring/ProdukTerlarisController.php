<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\ProdukTerlarisExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProdukTerlarisController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $brands = DB::table('brands')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);
        $categories = DB::table('product_categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Grafik selalu membandingkan seluruh cabang; filter periode dan pencarian tetap diterapkan.
        $chartFilters = [...$filters, 'store_id' => null];

        return Inertia::render('Monitoring/Report', [
            'title' => 'Produk Terlaris',
            'description' => 'Laporan monitoring.',
            'data' => [
                'rows' => $this->rows($filters)->paginate($filters['per_page'])->withQueryString(),
                'summary' => $this->summary($filters),
                'topProductChart' => $this->topProductChart($chartFilters),
                'topStoreChart' => $this->topStoreChart($chartFilters),
                'stores' => $stores,
                'brands' => $brands,
                'categories' => $categories,
                'hasBrandColumn' => $this->hasBrandColumn(),
                'filters' => $filters,
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.produk-terlaris.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $storeName = $filters['store_id']
            ? (DB::table('stores')->where('id', $filters['store_id'])->value('name') ?: '-')
            : 'Semua Cabang';

        return Excel::download(
            new ProdukTerlarisExport($this->rows($filters)->get(), $storeName, $filters),
            'produk-terlaris-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        $today = now()->toDateString();
        $from = $this->date($request->query('date_from')) ?: $today;
        $to = $this->date($request->query('date_to')) ?: $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) $request->query('per_page', 10);

        return [
            'store_id' => $this->nullable($request->query('store_id')),
            'brand_id' => $this->nullable($request->query('brand_id')),
            'category_id' => $this->nullable($request->query('category_id')),
            'q' => $this->nullable($request->query('q')),
            'date_from' => $from,
            'date_to' => $to,
            'per_page' => in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10,
        ];
    }

    private function rows(array $filters)
    {
        return DB::query()
            ->fromSub($this->salesBase($filters), 'sales')
            ->selectRaw("\n                sales.periode_bulan,\n                sales.store_id,\n                sales.store_name as cabang_name,\n                sales.product_id,\n                sales.product_code,\n                sales.product_name,\n                sales.brand_name,\n                sales.category_name,\n                COUNT(DISTINCT sales.visit_id)::int as total_transaksi,\n                COALESCE(SUM(sales.qty), 0)::int as total_qty,\n                COALESCE(SUM(sales.qty_good), 0)::int as qty_good,\n                COALESCE(SUM(sales.qty_good_after_repair), 0)::int as qty_good_after_repair,\n                COALESCE(SUM(sales.qty_clearance), 0)::int as qty_clearance,\n                COALESCE(SUM(sales.clearance_discount), 0)::numeric(18,2) as clearance_discount,\n                CASE WHEN COALESCE(SUM(sales.clearance_gross), 0) > 0 THEN ROUND((SUM(sales.clearance_discount) / SUM(sales.clearance_gross)) * 100, 2) ELSE 0 END as clearance_percentage,\n                COALESCE(SUM(sales.nominal_penjualan), 0)::numeric(18,2) as nominal_penjualan\n            ")
            ->groupBy('sales.periode_bulan', 'sales.store_id', 'sales.store_name', 'sales.product_id', 'sales.product_code', 'sales.product_name', 'sales.brand_name', 'sales.category_name')
            ->orderByDesc('sales.periode_bulan')
            ->orderByDesc(DB::raw('SUM(sales.nominal_penjualan)'))
            ->orderBy('sales.product_name');
    }

    private function summary(array $filters)
    {
        return DB::query()
            ->fromSub($this->salesBase($filters), 'sales')
            ->selectRaw("\n                COUNT(DISTINCT sales.product_id)::int as total_produk,\n                COUNT(DISTINCT sales.store_id)::int as total_cabang,\n                COUNT(DISTINCT sales.visit_id)::int as total_transaksi,\n                COALESCE(SUM(sales.qty), 0)::int as total_qty,\n                COALESCE(SUM(sales.nominal_penjualan), 0)::numeric(18,2) as total_nominal,\n                COALESCE(SUM(sales.qty_good), 0)::int as total_qty_good,\n                COALESCE(SUM(sales.qty_good_after_repair), 0)::int as total_qty_good_after_repair,\n                COALESCE(SUM(sales.qty_clearance), 0)::int as total_qty_clearance,\n                COALESCE(SUM(sales.clearance_discount), 0)::numeric(18,2) as total_clearance_discount,\n                CASE WHEN COALESCE(SUM(sales.clearance_gross), 0) > 0 THEN ROUND((SUM(sales.clearance_discount) / SUM(sales.clearance_gross)) * 100, 2) ELSE 0 END as clearance_percentage\n            ")
            ->first();
    }

    private function topProductChart(array $filters)
    {
        return DB::query()
            ->fromSub($this->salesBase($filters), 'sales')
            ->selectRaw("\n                sales.product_name,\n                COALESCE(SUM(sales.qty), 0)::int as total_qty,\n                COALESCE(SUM(sales.nominal_penjualan), 0)::numeric(18,2) as nominal_penjualan\n            ")
            ->groupBy('sales.product_id', 'sales.product_name')
            ->orderByDesc(DB::raw('SUM(sales.qty)'))
            ->orderByDesc(DB::raw('SUM(sales.nominal_penjualan)'))
            ->limit(10)
            ->get();
    }

    private function topStoreChart(array $filters)
    {
        return DB::query()
            ->fromSub($this->salesBase($filters), 'sales')
            ->selectRaw("\n                sales.store_name,\n                COALESCE(SUM(sales.qty), 0)::int as total_qty,\n                COALESCE(SUM(sales.nominal_penjualan), 0)::numeric(18,2) as nominal_penjualan\n            ")
            ->groupBy('sales.store_id', 'sales.store_name')
            ->orderByDesc(DB::raw('SUM(sales.qty)'))
            ->orderByDesc(DB::raw('SUM(sales.nominal_penjualan)'))
            ->limit(10)
            ->get();
    }

    /** Transaksi POS yang sudah berhasil dibayar, satu baris untuk setiap barang terjual. */
    private function salesBase(array $filters)
    {
        $successfulStatuses = ['paid', 'success', 'completed', 'settlement'];

        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->where(function ($query) use ($successfulStatuses) {
                $query->whereNotNull('p.completed_at')
                    ->orWhereIn(DB::raw('LOWER(p.status::text)'), $successfulStatuses);
            })
            ->selectRaw('svp.visit_id, MIN(COALESCE(p.completed_at, p.created_at)) as paid_at')
            ->groupBy('svp.visit_id');

        $cartNet = DB::table('store_cart_details as cart_detail')
            ->selectRaw('cart_detail.cart_id, SUM(GREATEST(COALESCE(cart_detail.product_price_amount, 0) - COALESCE(cart_detail.discount_amount, 0), 0)) as item_net_total')
            ->where(function ($query) {
                $query->whereNull('cart_detail.void_status')
                    ->orWhereNotIn('cart_detail.void_status', ['void', 'voided', 'gimmick']);
            })
            ->groupBy('cart_detail.cart_id');

        $query = DB::query()
            ->fromSub($paidVisits, 'paid_visits')
            ->join('store_customer_visits as visit', 'visit.id', '=', 'paid_visits.visit_id')
            ->join('stores as store', 'store.id', '=', 'visit.store_id')
            ->join('store_carts as cart', 'cart.visit_id', '=', 'visit.id')
            ->join('store_cart_details as detail', 'detail.cart_id', '=', 'cart.id')
            ->leftJoinSub($cartNet, 'cart_net', function ($join) {
                $join->on('cart_net.cart_id', '=', 'cart.id');
            })
            ->leftJoin('items as item', 'item.id', '=', 'detail.item_id')
            ->leftJoin('item_conditions as item_condition', 'item_condition.id', '=', 'item.current_condition_id')
            ->leftJoin('product_prices as price', 'price.id', '=', 'detail.product_price_id')
            ->leftJoin('products as product', function ($join) {
                $join->on('product.id', '=', 'item.product_id')
                    ->orOn('product.id', '=', 'price.product_id');
            })
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id');

        if ($this->hasBrandColumn()) {
            $query->leftJoin('brands as brand', 'brand.id', '=', 'product.brand_id');
        }

        $query
            ->whereNull('store.deleted_at')
            ->where(function ($query) {
                $query->whereNull('detail.void_status')
                    ->orWhereNotIn('detail.void_status', ['void', 'voided', 'gimmick']);
            })
            ->whereNotNull('product.id')
            ->whereDate('paid_visits.paid_at', '>=', $filters['date_from'])
            ->whereDate('paid_visits.paid_at', '<=', $filters['date_to'])
            ->when($filters['store_id'], fn ($query, $storeId) => $query->where('visit.store_id', $storeId))
            ->when($filters['category_id'], fn ($query, $categoryId) => $query->where('product.category_id', $categoryId))
            ->when($filters['brand_id'] && $this->hasBrandColumn(), fn ($query, $brandId) => $query->where('product.brand_id', $brandId))
            ->when($filters['q'], function ($query, $keyword) {
                $like = "%{$keyword}%";
                $hasBrand = $this->hasBrandColumn();
                $query->where(function ($search) use ($like, $hasBrand) {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('store.name', 'ILIKE', $like)
                        ->orWhere('store.code', 'ILIKE', $like)
                        ->orWhere('category.name', 'ILIKE', $like);

                    if ($hasBrand) {
                        $search->orWhere('brand.name', 'ILIKE', $like);
                    }
                });
            });

        $brandNameSelect = $this->hasBrandColumn() ? 'brand.name' : 'NULL';

        return $query->selectRaw("\n            paid_visits.visit_id,\n            paid_visits.paid_at,\n            TO_CHAR(paid_visits.paid_at, 'MM/YYYY') as periode_bulan,\n            store.id as store_id,\n            store.name as store_name,\n            product.id as product_id,\n            product.code as product_code,\n            product.name as product_name,\n            {$brandNameSelect} as brand_name,\n            category.name as category_name,\n            1::int as qty,\n            CASE WHEN item_condition.status::text = 'good' THEN 1 ELSE 0 END::int as qty_good,\n            CASE WHEN item_condition.status::text = 'good_after_repair' THEN 1 ELSE 0 END::int as qty_good_after_repair,\n            CASE WHEN item_condition.status::text = 'good_after_repair' THEN 1 ELSE 0 END::int as qty_clearance,\n            CASE WHEN item_condition.status::text = 'good_after_repair' THEN COALESCE(detail.discount_amount, 0) ELSE 0 END::numeric(18,2) as clearance_discount,\n            CASE WHEN item_condition.status::text = 'good_after_repair' THEN COALESCE(detail.product_price_amount, 0) ELSE 0 END::numeric(18,2) as clearance_gross,\n            GREATEST(\n                GREATEST(COALESCE(detail.product_price_amount, 0) - COALESCE(detail.discount_amount, 0), 0)\n                - CASE\n                    WHEN COALESCE(cart_net.item_net_total, 0) > 0 THEN\n                        (GREATEST(COALESCE(detail.product_price_amount, 0) - COALESCE(detail.discount_amount, 0), 0) / cart_net.item_net_total)\n                        * (COALESCE(cart.voucher_discount, 0) + COALESCE(cart.point_amount, 0))\n                    ELSE 0\n                  END,\n                0\n            )::numeric(18,2) as nominal_penjualan\n        ");
    }

    /** Kolom products.brand_id baru ditambah via migrasi; abaikan bila belum dijalankan. */
    private function hasBrandColumn(): bool
    {
        try {
            return Schema::hasColumn('products', 'brand_id');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?string
    {
        $value = $this->nullable($value);

        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
