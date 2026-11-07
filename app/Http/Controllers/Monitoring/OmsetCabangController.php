<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\Analytics\Support\FinancialBreakdown;
use App\Exports\OmsetCabangExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OmsetCabangController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-omset-cabang')
            ->only(['index', 'export']);
    }

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $storesQuery = DB::table('stores')
            ->select('id', 'name', 'code');

        $this->applyDeletedAtFilter($storesQuery, 'stores');

        $stores = $storesQuery
            ->orderBy('name')
            ->get();

        $productsQuery = DB::table('products')
            ->select('id', 'name', 'code');

        $this->applyDeletedAtFilter($productsQuery, 'products');

        $products = $productsQuery
            ->orderBy('name')
            ->get();

        $summary = $this->getSummary(
            storeId: $filters['store_id'],
            productId: $filters['product_id'],
            q: $filters['q'],
            dateFrom: $filters['date_from'],
            dateTo: $filters['date_to']
        );

        $finance = $this->getFinancialSummary(
            storeId: $filters['store_id'],
            productId: $filters['product_id'],
            q: $filters['q'],
            dateFrom: $filters['date_from'],
            dateTo: $filters['date_to']
        );

        $rows = $this->getRows(
            storeId: $filters['store_id'],
            productId: $filters['product_id'],
            q: $filters['q'],
            dateFrom: $filters['date_from'],
            dateTo: $filters['date_to'],
            perPage: $filters['per_page']
        );

        $chartRows = $this->getChartRows(
            storeId: $filters['store_id'],
            productId: $filters['product_id'],
            q: $filters['q'],
            dateFrom: $filters['date_from'],
            dateTo: $filters['date_to']
        );

        return Inertia::render('Monitoring/Report', [
            'title' => 'Omset Cabang',
            'description' => 'Ringkasan omset per cabang dari transaksi lunas POS dan ecommerce. Baris tabel adalah cabang, bukan rincian produk.',
            'data' => [
                'rows' => $rows,
                'summary' => $summary,
                'finance' => $finance,
                'stores' => $stores,
                'products' => $products,
                'chartRows' => $chartRows,
                'omsetTab' => 'cabang',
                'omsetUrls' => [
                    'cabang' => route('monitoring.omset-cabang.index'),
                    'kotor' => route('monitoring.omset-kotor.index'),
                ],

                'storeId' => $filters['store_id'],
                'productId' => $filters['product_id'],
                'q' => $filters['q'],
                'dateFrom' => $filters['date_from'],
                'dateTo' => $filters['date_to'],
                'perPage' => $filters['per_page'],
                'today' => $filters['today'],
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.omset-cabang.export')],
        ]);
    }

    public function export(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Export default hari ini
        |--------------------------------------------------------------------------
        | Jika tanggal kosong, export mengikuti default halaman yaitu hari ini.
        */
        $filters = $this->getFilters($request);

        $rows = $this->getExportRows(
            storeId: $filters['store_id'],
            productId: $filters['product_id'],
            q: $filters['q'],
            dateFrom: $filters['date_from'],
            dateTo: $filters['date_to']
        );

        return Excel::download(
            new OmsetCabangExport($rows),
            'laporan-omset-cabang-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function getFilters(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Default data hari ini
        |--------------------------------------------------------------------------
        | Sebelumnya default awal bulan sampai hari ini.
        | Sekarang default hanya hari ini.
        */
        $today = now()->toDateString();

        $dateFrom = $this->normalizeDate($request->query('date_from')) ?: $today;
        $dateTo = $this->normalizeDate($request->query('date_to')) ?: $today;

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'product_id' => $this->normalizeNullableString($request->query('product_id')),
            'q' => $this->normalizeNullableString($request->query('q')),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'per_page' => $perPage,
            'today' => $today,
        ];
    }

    private function getRows(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 10
    ) {
        return $this->buildReportQuery(
            storeId: $storeId,
            productId: $productId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo
        )
            ->orderBy('x.store_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function getExportRows(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        return $this->buildReportQuery(
            storeId: $storeId,
            productId: $productId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo
        )
            ->orderBy('x.store_name')
            ->get();
    }

    private function buildReportQuery(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        return DB::query()
            ->fromSub(
                $this->buildBaseQuery(
                    storeId: $storeId,
                    productId: $productId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo
                ),
                'x'
            )
            ->selectRaw('
                MAX(x.store_id::text)::uuid as store_id,
                x.store_name,
                x.store_code,

                COUNT(DISTINCT x.order_id)::int as total_order,
                COUNT(DISTINCT x.product_id)::int as total_produk,

                COALESCE(SUM(x.requested_qty), 0)::int as total_barang_diambil,
                COALESCE(SUM(x.approved_qty), 0)::int as total_barang_keluar,
                COALESCE(SUM(x.received_qty), 0)::int as total_barang_diterima,
                COALESCE(SUM(x.requested_qty - x.approved_qty), 0)::int as total_sisa_belum_keluar,

                COALESCE(SUM(x.total_harga_awal), 0)::numeric as total_omset_kotor,
                COALESCE(SUM(x.total_diskon_produk), 0)::numeric as total_diskon_produk,
                COALESCE(SUM(x.total_voucher_promo), 0)::numeric as total_voucher_promo,
                COALESCE(SUM(x.total_harga_akhir), 0)::numeric as total_omset_bersih,
                COALESCE(SUM(x.total_harga_base), 0)::numeric as total_harga_base,
                COALESCE(SUM(x.total_harga_akhir - x.total_harga_base), 0)::numeric as laba_kotor,
                CASE
                    WHEN SUM(x.total_harga_akhir) > 0 THEN
                        ROUND((SUM(x.total_harga_akhir - x.total_harga_base) / SUM(x.total_harga_akhir)) * 100, 2)
                    ELSE 0
                END as margin_kotor_percent,
                COALESCE(SUM(x.total_omset_kotor_store), 0)::numeric as total_omset_kotor_store,
                COALESCE(SUM(x.total_omset_kotor_ecommerce), 0)::numeric as total_omset_kotor_ecommerce,
                COALESCE(SUM(x.total_harga_akhir), 0)::numeric as total_omset
            ')
            ->groupBy('x.store_name', 'x.store_code');
    }

    private function getSummary(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        return DB::query()
            ->fromSub(
                $this->buildBaseQuery(
                    storeId: $storeId,
                    productId: $productId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo
                ),
                'x'
            )
            ->selectRaw('
                COUNT(DISTINCT COALESCE(x.store_code, x.store_name)) as total_cabang,
                COUNT(DISTINCT x.product_id) as total_produk,
                COUNT(DISTINCT x.customer_id) as total_customer,
                COUNT(DISTINCT x.order_id) as total_store_order,
                COUNT(DISTINCT x.order_id) as total_transaksi,

                COALESCE(SUM(x.requested_qty), 0) as total_barang_diambil,
                COALESCE(SUM(x.approved_qty), 0) as total_barang_keluar,
                COALESCE(SUM(x.received_qty), 0) as total_barang_diterima,
                COALESCE(SUM(x.requested_qty - x.approved_qty), 0) as total_sisa_belum_keluar,

                COALESCE(SUM(x.total_harga_order_store), 0) as total_harga_order_store,
                COALESCE(SUM(x.total_harga_awal), 0) as total_omset_kotor,
                COALESCE(SUM(x.total_diskon_produk), 0) as total_diskon_produk,
                COALESCE(SUM(x.total_voucher_promo), 0) as total_voucher_promo,
                COALESCE(SUM(x.total_harga_akhir), 0) as total_omset_bersih,
                COALESCE(SUM(x.total_harga_base), 0) as total_harga_base,
                COALESCE(SUM(x.total_omset_kotor_store), 0) as total_omset_kotor_store,
                COALESCE(SUM(x.total_omset_kotor_ecommerce), 0) as total_omset_kotor_ecommerce,
                COALESCE(SUM(x.total_harga_akhir), 0) as total_omset
            ')
            ->first();
    }

    private function getFinancialSummary(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $channelRows = DB::query()
            ->fromSub(
                $this->buildBaseQuery(
                    storeId: $storeId,
                    productId: $productId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo
                ),
                'x'
            )
            ->selectRaw('
                x.sales_channel as channel,
                COUNT(DISTINCT x.order_id) as transactions,
                COALESCE(SUM(x.requested_qty), 0) as qty,
                COALESCE(SUM(x.total_harga_awal), 0) as gross_sales,
                COALESCE(SUM(x.total_diskon_produk), 0) as product_discount,
                COALESCE(SUM(GREATEST(x.total_harga_awal - x.total_diskon_produk, 0)), 0) as net_after_product_discount,
                COALESCE(SUM(x.total_voucher_promo), 0) as voucher_discount,
                COALESCE(SUM(x.total_harga_akhir), 0) as net_sales,
                COALESCE(SUM(x.total_harga_base), 0) as base_price
            ')
            ->groupBy('x.sales_channel')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $totals = [
            'transactions' => array_sum(array_column($channelRows, 'transactions')),
            'qty' => array_sum(array_column($channelRows, 'qty')),
            'gross_sales' => array_sum(array_column($channelRows, 'gross_sales')),
            'product_discount' => array_sum(array_column($channelRows, 'product_discount')),
            'net_after_product_discount' => array_sum(array_column($channelRows, 'net_after_product_discount')),
            'voucher_discount' => array_sum(array_column($channelRows, 'voucher_discount')),
            'net_sales' => array_sum(array_column($channelRows, 'net_sales')),
            'base_price' => array_sum(array_column($channelRows, 'base_price')),
        ];

        return [
            ...FinancialBreakdown::make(
                $totals['gross_sales'],
                $totals['product_discount'],
                $totals['voucher_discount'],
                $totals['net_sales'],
                $totals['base_price'],
                $totals['transactions'],
                $totals['qty'],
                $totals['net_after_product_discount']
            ),
            'channels' => FinancialBreakdown::channels($channelRows),
        ];
    }

    private function getChartRows(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        return DB::query()
            ->fromSub(
                $this->buildBaseQuery(
                    storeId: $storeId,
                    productId: $productId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo
                ),
                'x'
            )
            ->selectRaw('
                MAX(x.store_id::text)::uuid as store_id,
                x.store_name,
                x.store_code,

                COALESCE(SUM(x.approved_qty), 0) as total_barang_keluar,
                COALESCE(SUM(x.total_harga_base), 0) as total_harga_base,
                COALESCE(SUM(x.total_omset_kotor_store), 0) as total_omset_kotor_store,
                COALESCE(SUM(x.total_omset_kotor_ecommerce), 0) as total_omset_kotor_ecommerce
            ')
            ->groupBy('x.store_name', 'x.store_code')
            ->orderByDesc(DB::raw('SUM(x.total_omset_kotor_ecommerce)'))
            ->limit(10)
            ->get();
    }

    private function buildBaseQuery(
        ?string $storeId = null,
        ?string $productId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        $storeOrderQuery = $this->buildStoreSalesBaseQuery();
        $ecommerceOrderQuery = $this->buildEcommerceSalesBaseQuery();

        $union = $storeOrderQuery->unionAll($ecommerceOrderQuery);

        $query = DB::query()->fromSub($union, 'base_data');

        if ($storeId === '__ecommerce__') {
            $query->where('base_data.sales_channel', 'ecommerce');
        } elseif ($storeId) {
            $query->where(function ($storeQuery) use ($storeId) {
                $storeQuery->where('base_data.store_id', $storeId)
                    ->orWhereRaw(
                        "POSITION(? IN COALESCE(',' || base_data.fulfillment_store_ids || ',', '')) > 0",
                        [$storeId]
                    );
            });
        }

        if ($productId) {
            $query->where('base_data.product_id', $productId);
        }

        if ($dateFrom) {
            $query->whereDate('base_data.order_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('base_data.order_date', '<=', $dateTo);
        }

        if ($q) {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('base_data.store_name', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.store_code', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.fulfillment_store_names', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.fulfillment_warehouse_names', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.product_name', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.product_code', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.order_number', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.order_source', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.customer_name', 'ILIKE', "%{$q}%")
                    ->orWhere('base_data.status', 'ILIKE', "%{$q}%");
            });
        }

        return $query->selectRaw('base_data.*');
    }

    /**
     * Penjualan Store berasal dari keranjang POS yang sudah dibayar, bukan dari
     * store_orders karena store_orders adalah permintaan/transfer stok.
     */
    private function buildStoreSalesBaseQuery()
    {
        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as pay', 'pay.id', '=', 'svp.payment_id')
            ->whereNull('pay.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('pay.completed_at')
                    ->orWhereIn(DB::raw('LOWER(pay.status::text)'), ['paid', 'settlement', 'capture', 'success', 'completed']);
            })
            ->selectRaw('svp.visit_id, MIN(COALESCE(pay.completed_at, pay.created_at)) as paid_at, MIN(pay.transaction_number) as transaction_number')
            ->groupBy('svp.visit_id');

        $orderDateSql = 'COALESCE(paid_visit.paid_at, scd.created_at)::date';
        $basePriceSql = $this->priceSql('p.id', $orderDateSql, 'base');
        $storePriceSql = "COALESCE(NULLIF(scd.product_price_amount, 0), ({$this->priceSql('p.id', $orderDateSql, 'store')}))";
        $cartNet = DB::table('store_cart_details')
            ->selectRaw('cart_id, SUM(GREATEST(COALESCE(product_price_amount, 0) - COALESCE(discount_amount, 0), 0)) as net_total')
            ->groupBy('cart_id');
        $voucherPromoSql = "
            CASE WHEN COALESCE(cart_net.net_total, 0) > 0 THEN ROUND(
                (GREATEST(({$storePriceSql}) - COALESCE(scd.discount_amount, 0), 0) / cart_net.net_total)
                * (COALESCE(sc.voucher_discount, 0) + COALESCE(sc.point_amount, 0)), 2
            ) ELSE 0 END
        ";
        $storeFinalSql = "GREATEST(({$storePriceSql}) - COALESCE(scd.discount_amount, 0) - ({$voucherPromoSql}), 0)";

        return DB::table('store_cart_details as scd')
            ->join('store_carts as sc', 'sc.id', '=', 'scd.cart_id')
            ->joinSub($paidVisits, 'paid_visit', fn ($join) => $join->on('paid_visit.visit_id', '=', 'sc.visit_id'))
            ->join('stores as s', 's.id', '=', 'sc.store_id')
            ->join('items as i', 'i.id', '=', 'scd.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('customers as c', 'c.id', '=', 'sc.customer_id')
            ->leftJoinSub($cartNet, 'cart_net', fn ($join) => $join->on('cart_net.cart_id', '=', 'sc.id'))
            ->selectRaw("
                'Store POS' as order_source,
                'store' as sales_channel,
                sc.id as order_id,
                COALESCE(paid_visit.transaction_number, sc.id::text) as order_number,
                'paid'::text as status,

                c.id as customer_id,
                COALESCE(c.name, '-') as customer_name,

                sc.store_id,
                NULL::uuid as warehouse_id,
                paid_visit.paid_at as order_datetime,
                {$orderDateSql} as order_date,

                s.name as store_name,
                s.code as store_code,
                NULL::text as fulfillment_store_ids,
                NULL::text as fulfillment_store_names,
                NULL::text as fulfillment_warehouse_names,

                p.id as product_id,
                p.name as product_name,
                p.code as product_code,

                1::int as requested_qty,
                1::int as approved_qty,
                1::int as received_qty,
                0::int as fulfillment_store_qty,
                0::int as fulfillment_warehouse_qty,

                ({$storePriceSql})::numeric(18,2) as harga_order_store,
                ({$basePriceSql})::numeric(18,2) as harga_base,
                ({$storePriceSql})::numeric(18,2) as harga_jual_store,
                0::numeric(18,2) as harga_jual_ecommerce,

                ({$storePriceSql})::numeric(18,2) as total_harga_order_store,
                ({$basePriceSql})::numeric(18,2) as total_harga_base,
                ({$storePriceSql})::numeric(18,2) as total_harga_awal,
                COALESCE(scd.discount_amount, 0)::numeric(18,2) as total_diskon_produk,
                ({$voucherPromoSql})::numeric(18,2) as total_voucher_promo,
                ({$storeFinalSql})::numeric(18,2) as total_harga_akhir,
                ({$storeFinalSql})::numeric(18,2) as total_omset_kotor_store,
                0::numeric(18,2) as total_omset_kotor_ecommerce
            ");
    }

    /**
     * Satu baris Ecommerce tetap satu penjualan. Lokasi fulfillment diagregasi
     * agar omzet Ecommerce tidak terduplikasi ketika stok diambil dari beberapa lokasi.
     */
    private function buildEcommerceSalesBaseQuery()
    {
        $storeFulfillment = DB::table('store_outbounds as outbound')
            ->join('store_outbound_details as detail', 'detail.store_outbound_id', '=', 'outbound.id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->join('stores as store', 'store.id', '=', 'outbound.store_id')
            ->whereNotNull('outbound.ecommerce_order_id')
            ->selectRaw("
                outbound.ecommerce_order_id,
                item.product_id,
                STRING_AGG(DISTINCT outbound.store_id::text, ',') as fulfillment_store_ids,
                STRING_AGG(DISTINCT store.name, ', ' ORDER BY store.name) as fulfillment_store_names,
                COUNT(DISTINCT detail.id)::int as fulfillment_store_qty
            ")
            ->groupBy('outbound.ecommerce_order_id', 'item.product_id');

        $warehouseFulfillment = DB::table('warehouse_outbounds as outbound')
            ->join('warehouse_outbound_details as detail', 'detail.warehouse_outbound_id', '=', 'outbound.id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->join('warehouses as warehouse', 'warehouse.id', '=', 'outbound.warehouse_id')
            ->whereNotNull('outbound.order_id')
            ->selectRaw("
                outbound.order_id as ecommerce_order_id,
                item.product_id,
                STRING_AGG(DISTINCT warehouse.name, ', ' ORDER BY warehouse.name) as fulfillment_warehouse_names,
                COUNT(DISTINCT detail.id)::int as fulfillment_warehouse_qty
            ")
            ->groupBy('outbound.order_id', 'item.product_id');

        $orderDateSql = 'eo.created_at::date';
        $basePriceSql = "COALESCE(NULLIF(eod.product_base_price_amount, 0), ({$this->priceSql('p.id', $orderDateSql, 'base')}))";
        $ecommercePriceSql = "COALESCE(NULLIF(eod.product_price, 0), ({$this->priceSql('p.id', $orderDateSql, 'ecommerce')}))";
        $ecommerceInitialSql = "({$ecommercePriceSql}) * COALESCE(eod.quantity, 0)";
        $ecommerceRevenueSql = "COALESCE(NULLIF(eod.total_price, 0), ({$ecommerceInitialSql}) - COALESCE(eod.total_discount, 0))";
        $orderNet = DB::table('ecommerce_order_details')
            ->selectRaw('ecommerce_order_id, SUM(COALESCE(NULLIF(total_price, 0), (COALESCE(product_price, 0) * COALESCE(quantity, 0)) - COALESCE(total_discount, 0))) as net_total')
            ->groupBy('ecommerce_order_id');
        $voucherPromoSql = "
            CASE WHEN COALESCE(order_net.net_total, 0) > 0 THEN ROUND(
                (({$ecommerceRevenueSql}) / order_net.net_total) * COALESCE(eo.discount_total, 0), 2
            ) ELSE 0 END
        ";
        $ecommerceFinalSql = "GREATEST(({$ecommerceRevenueSql}) - ({$voucherPromoSql}), 0)";

        return DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('payments as pay', 'pay.id', '=', 'eo.payment_id')
            ->join('products as p', 'p.id', '=', 'eod.product_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->whereNull('pay.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('pay.completed_at')
                    ->orWhereIn(DB::raw('LOWER(pay.status::text)'), ['paid', 'settlement', 'capture', 'success', 'completed']);
            })
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->leftJoinSub($orderNet, 'order_net', fn ($join) => $join->on('order_net.ecommerce_order_id', '=', 'eo.id'))
            ->leftJoin('stores as handled_store', 'handled_store.id', '=', 'eo.handled_by_store_id')
            ->leftJoin('warehouses as handled_warehouse', 'handled_warehouse.id', '=', 'eo.handled_by_warehouse_id')
            ->leftJoinSub($storeFulfillment, 'store_fulfillment', function ($join) {
                $join->on('store_fulfillment.ecommerce_order_id', '=', 'eo.id')
                    ->on('store_fulfillment.product_id', '=', 'eod.product_id');
            })
            ->leftJoinSub($warehouseFulfillment, 'warehouse_fulfillment', function ($join) {
                $join->on('warehouse_fulfillment.ecommerce_order_id', '=', 'eo.id')
                    ->on('warehouse_fulfillment.product_id', '=', 'eod.product_id');
            })
            ->selectRaw("
                'Ecommerce' as order_source,
                'ecommerce' as sales_channel,
                eo.id as order_id,
                COALESCE(eo.order_number, eo.id::text) as order_number,
                COALESCE(eo.status::text, '-') as status,

                c.id as customer_id,
                COALESCE(c.name, '-') as customer_name,

                NULL::uuid as store_id,
                NULL::uuid as warehouse_id,
                eo.created_at as order_datetime,
                {$orderDateSql} as order_date,

                COALESCE(store_fulfillment.fulfillment_store_names, handled_store.name, 'Ecommerce (Gudang)')::text as store_name,
                COALESCE(handled_store.code, 'ECOMMERCE')::text as store_code,
                COALESCE(store_fulfillment.fulfillment_store_ids, eo.handled_by_store_id::text) as fulfillment_store_ids,
                COALESCE(store_fulfillment.fulfillment_store_names, handled_store.name) as fulfillment_store_names,
                COALESCE(warehouse_fulfillment.fulfillment_warehouse_names, handled_warehouse.name) as fulfillment_warehouse_names,

                p.id as product_id,
                p.name as product_name,
                p.code as product_code,

                COALESCE(eod.quantity, 0)::int as requested_qty,
                (COALESCE(store_fulfillment.fulfillment_store_qty, 0) + COALESCE(warehouse_fulfillment.fulfillment_warehouse_qty, 0))::int as approved_qty,
                COALESCE(eod.quantity, 0)::int as received_qty,
                COALESCE(store_fulfillment.fulfillment_store_qty, 0)::int as fulfillment_store_qty,
                COALESCE(warehouse_fulfillment.fulfillment_warehouse_qty, 0)::int as fulfillment_warehouse_qty,

                0::numeric(18,2) as harga_order_store,
                ({$basePriceSql})::numeric(18,2) as harga_base,
                0::numeric(18,2) as harga_jual_store,
                ({$ecommercePriceSql})::numeric(18,2) as harga_jual_ecommerce,

                0::numeric(18,2) as total_harga_order_store,
                (({$basePriceSql}) * COALESCE(eod.quantity, 0))::numeric(18,2) as total_harga_base,
                ({$ecommerceInitialSql})::numeric(18,2) as total_harga_awal,
                COALESCE(eod.total_discount, 0)::numeric(18,2) as total_diskon_produk,
                ({$voucherPromoSql})::numeric(18,2) as total_voucher_promo,
                ({$ecommerceFinalSql})::numeric(18,2) as total_harga_akhir,
                0::numeric(18,2) as total_omset_kotor_store,
                ({$ecommerceFinalSql})::numeric(18,2) as total_omset_kotor_ecommerce
            ");
    }

    private function buildStoreOrderBaseQuery()
    {
        $orderDateSql = 'COALESCE(so.requested_at, so.created_at)::date';

        $basePriceSql = $this->priceSql('p.id', $orderDateSql, 'base');
        $storePriceSql = $this->priceSql('p.id', $orderDateSql, 'store');
        $ecommercePriceSql = $this->priceSql('p.id', $orderDateSql, 'ecommerce');

        return DB::table('store_order_details as sod')
            ->join('store_orders as so', 'so.id', '=', 'sod.store_order_id')
            ->join('stores as s', 's.id', '=', 'so.store_id')
            ->join('products as p', 'p.id', '=', 'sod.product_id')
            ->selectRaw("
                'Store Order' as order_source,
                so.id as order_id,
                COALESCE(so.order_number, so.id::text) as order_number,
                COALESCE(so.status::text, '-') as status,

                NULL::uuid as customer_id,
                '-'::text as customer_name,

                so.store_id,
                so.warehouse_id,
                COALESCE(so.requested_at, so.created_at) as order_datetime,
                {$orderDateSql} as order_date,

                s.name as store_name,
                s.code as store_code,

                p.id as product_id,
                p.name as product_name,
                p.code as product_code,

                COALESCE(sod.requested_qty, 0)::int as requested_qty,
                COALESCE(sod.approved_qty, 0)::int as approved_qty,
                COALESCE(sod.received_qty, 0)::int as received_qty,

                COALESCE(sod.product_price, 0)::numeric(18,2) as harga_order_store,

                ({$basePriceSql})::numeric(18,2) as harga_base,
                ({$storePriceSql})::numeric(18,2) as harga_jual_store,
                ({$ecommercePriceSql})::numeric(18,2) as harga_jual_ecommerce,

                (
                    COALESCE(sod.approved_qty, 0) *
                    COALESCE(sod.product_price, 0)
                )::numeric(18,2) as total_harga_order_store,

                (
                    COALESCE(sod.approved_qty, 0) *
                    ({$basePriceSql})
                )::numeric(18,2) as total_harga_base,

                (
                    COALESCE(sod.approved_qty, 0) *
                    ({$storePriceSql})
                )::numeric(18,2) as total_omset_kotor_store,

                0::numeric(18,2) as total_omset_kotor_ecommerce
            ");
    }

    private function buildEcommerceOrderBaseQuery()
    {
        $orderDateSql = 'eo.created_at::date';

        $basePriceSql = $this->priceSql('p.id', $orderDateSql, 'base');
        $storePriceSql = $this->priceSql('p.id', $orderDateSql, 'store');
        $ecommercePriceSql = $this->priceSql('p.id', $orderDateSql, 'ecommerce');

        $storeOutboundQtySub = DB::table('store_outbound_details as sod_out')
            ->join('store_outbounds as so_out', 'so_out.id', '=', 'sod_out.store_outbound_id')
            ->join('items as i', 'i.id', '=', 'sod_out.item_id')
            ->selectRaw('
                so_out.ecommerce_order_id,
                so_out.store_id,
                i.product_id,
                COUNT(sod_out.item_id)::int as outbound_qty
            ')
            ->whereNotNull('so_out.ecommerce_order_id')
            ->groupBy(
                'so_out.ecommerce_order_id',
                'so_out.store_id',
                'i.product_id'
            );

        return DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('products as p', 'p.id', '=', 'eod.product_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoinSub($storeOutboundQtySub, 'out_qty', function ($join) {
                $join->on('out_qty.ecommerce_order_id', '=', 'eo.id')
                    ->on('out_qty.product_id', '=', 'eod.product_id');
            })
            ->leftJoin('stores as s', 's.id', '=', 'out_qty.store_id')
            ->selectRaw("
                'Ecommerce Order' as order_source,
                eo.id as order_id,
                COALESCE(eo.order_number, eo.id::text) as order_number,
                COALESCE(eo.status::text, '-') as status,

                c.id as customer_id,
                COALESCE(c.name, '-') as customer_name,

                out_qty.store_id,
                NULL::uuid as warehouse_id,

                eo.created_at as order_datetime,
                {$orderDateSql} as order_date,

                COALESCE(s.name, 'Ecommerce') as store_name,
                COALESCE(s.code, 'ECOMMERCE') as store_code,

                p.id as product_id,
                p.name as product_name,
                p.code as product_code,

                COALESCE(eod.quantity, 0)::int as requested_qty,
                COALESCE(out_qty.outbound_qty, eod.quantity, 0)::int as approved_qty,
                COALESCE(out_qty.outbound_qty, eod.quantity, 0)::int as received_qty,

                0::numeric(18,2) as harga_order_store,

                ({$basePriceSql})::numeric(18,2) as harga_base,
                ({$storePriceSql})::numeric(18,2) as harga_jual_store,

                COALESCE(
                    NULLIF(eod.product_price, 0),
                    ({$ecommercePriceSql})
                )::numeric(18,2) as harga_jual_ecommerce,

                0::numeric(18,2) as total_harga_order_store,

                (
                    COALESCE(out_qty.outbound_qty, eod.quantity, 0) *
                    ({$basePriceSql})
                )::numeric(18,2) as total_harga_base,

                0::numeric(18,2) as total_omset_kotor_store,

                (
                    COALESCE(out_qty.outbound_qty, eod.quantity, 0) *
                    COALESCE(
                        NULLIF(eod.product_price, 0),
                        ({$ecommercePriceSql})
                    )
                )::numeric(18,2) as total_omset_kotor_ecommerce
            ");
    }

    private function priceSql(string $productIdColumn, string $dateSql, string $priceType): string
    {
        return "
            COALESCE(
                (
                    SELECT pp.price
                    FROM product_prices pp
                    WHERE pp.product_id = {$productIdColumn}
                      AND pp.price_type::text = '{$priceType}'
                      AND pp.valid_from <= {$dateSql}
                      AND (pp.valid_to IS NULL OR pp.valid_to >= {$dateSql})
                    ORDER BY pp.valid_from DESC, pp.created_at DESC
                    LIMIT 1
                ),
                0
            )
        ";
    }

    private function normalizeDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim((string) $date);

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

    private function applyDeletedAtFilter($query, string $table, ?string $alias = null): void
    {
        if (! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        $column = $alias ? "{$alias}.deleted_at" : 'deleted_at';

        $query->whereNull($column);
    }
}
