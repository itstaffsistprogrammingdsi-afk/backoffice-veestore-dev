<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\Analytics\Support\FinancialBreakdown;
use App\Exports\OmsetKotorSalesExport;
use App\Http\Controllers\Controller;
use App\Support\Concerns\NormalizesDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OmsetKotorSalesController extends Controller
{
    use NormalizesDate;

    public function __construct()
    {
        /*
         * Jika permission baru belum dibuat, ganti sementara menjadi:
         * permission:view monitoring-laba-rugi-penjualan
         */
        $this->middleware('permission:view monitoring-omset-kotor')
            ->only(['index', 'export', 'detail']);
    }

    public function index(Request $request)
    {
        $channel = $this->normalizeNullableString($request->query('channel')) ?? 'all';
        $productId = $this->normalizeNullableString($request->query('product_id'));
        $customerId = $this->normalizeNullableString($request->query('customer_id'));
        $q = $this->normalizeNullableString($request->query('q'));
        $dateFrom = $this->normalizeDate($request->query('date_from')) ?? now()->toDateString();
        $dateTo = $this->normalizeDate($request->query('date_to')) ?? now()->toDateString();

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $groupBy = $this->normalizeNullableString($request->query('group_by')) ?? 'product';
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($channel, ['all', 'store', 'ecommerce'], true)) {
            $channel = 'all';
        }

        if (! in_array($groupBy, ['product', 'daily', 'monthly'], true)) {
            $groupBy = 'product';
        }

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $promo = $this->normalizeNullableString($request->query('promo')) ?? 'all';

        if (! in_array($promo, ['all', 'none', 'any', 'product', 'voucher'], true)) {
            $promo = 'all';
        }

        $products = DB::table('products')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $customers = DB::table('customers')
            ->orderBy('name')
            ->get(['id', 'name']);

        $summary = $this->getSummaryCards(
            channel: $channel,
            productId: $productId,
            customerId: $customerId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            promo: $promo
        );

        $finance = $this->getFinancialSummary(
            channel: $channel,
            productId: $productId,
            customerId: $customerId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            promo: $promo
        );

        $rows = match ($groupBy) {
            'daily' => $this->getDailyRows(
                channel: $channel,
                productId: $productId,
                customerId: $customerId,
                q: $q,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                perPage: $perPage,
                promo: $promo
            ),
            'monthly' => $this->getMonthlyRows(
                channel: $channel,
                productId: $productId,
                customerId: $customerId,
                q: $q,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                perPage: $perPage,
                promo: $promo
            ),
            default => $this->getProductRows(
                channel: $channel,
                productId: $productId,
                customerId: $customerId,
                q: $q,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                perPage: $perPage,
                promo: $promo
            ),
        };

        return Inertia::render('Monitoring/Report', [
            'title' => 'Omset Kotor & Bersih',
            'description' => 'Omset kotor = nilai jual sebelum potongan. Omset bersih = nilai setelah diskon produk, voucher, dan poin. Hanya transaksi lunas; retur, void, dan order batal tidak masuk.',
            'data' => [
                'rows' => $rows,
                'summary' => $summary,
                'finance' => $finance,
                'products' => $products,
                'customers' => $customers,
                'channel' => $channel,
                'productId' => $productId,
                'customerId' => $customerId,
                'q' => $q,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'groupBy' => $groupBy,
                'promo' => $promo,
                'promoOptions' => $this->promoOptions(),
                'perPage' => $perPage,
                'omsetTab' => 'kotor',
                'omsetUrls' => [
                    'cabang' => route('monitoring.omset-cabang.index'),
                    'kotor' => route('monitoring.omset-kotor.index'),
                ],
                'today' => now()->toDateString(),
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.omset-kotor.export')],
        ]);
    }

    public function detail(string $productId, Request $request)
    {
        $channel = $this->normalizeNullableString($request->query('channel')) ?? 'all';
        $customerId = $this->normalizeNullableString($request->query('customer_id'));
        $q = $this->normalizeNullableString($request->query('q'));
        $dateFrom = $this->normalizeDate($request->query('date_from')) ?? now()->toDateString();
        $dateTo = $this->normalizeDate($request->query('date_to')) ?? now()->toDateString();

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($channel, ['all', 'store', 'ecommerce'], true)) {
            $channel = 'all';
        }

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        $promo = $this->normalizeNullableString($request->query('promo')) ?? 'all';

        if (! in_array($promo, ['all', 'none', 'any', 'product', 'voucher'], true)) {
            $promo = 'all';
        }

        $product = DB::table('products')
            ->where('id', $productId)
            ->first(['id', 'name', 'code']);

        abort_if(! $product, 404);

        $rows = $this->getTransactionDetailRows(
            channel: $channel,
            productId: $productId,
            customerId: $customerId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            perPage: $perPage,
            promo: $promo
        );

        $summary = DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->selectRaw('
                COUNT(*) as total_rows,
                SUM(x.gross_sales) as total_gross_sales,
                SUM(x.final_paid_amount) as total_final_paid,
                SUM(x.total_discount_amount) as total_discount_amount,
                SUM(x.net_after_product_discount) as total_net_after_product_discount,
                SUM(x.qty) as total_qty,
                COUNT(DISTINCT x.product_id) as total_products,
                COUNT(DISTINCT x.customer_id) as total_customers,
                SUM(x.base_price_total) as total_base_price,
                SUM(x.product_discount_amount) as total_product_discount,
                SUM(x.allocated_order_discount) as total_voucher_discount
            ')
            ->first();

        $finance = $this->getFinancialSummary(
            channel: $channel,
            productId: $productId,
            customerId: $customerId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            promo: $promo
        );

        return Inertia::render('Monitoring/Report', [
            'title' => 'Omset Kotor & Bersih',
            'description' => 'Omset kotor = nilai jual sebelum potongan. Omset bersih = nilai setelah diskon produk, voucher, dan poin. Hanya transaksi lunas; retur, void, dan order batal tidak masuk.',
            'data' => [
                'product' => $product,
                'rows' => $rows,
                'summary' => $summary,
                'finance' => $finance,
                'channel' => $channel,
                'customerId' => $customerId,
                'q' => $q,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => $perPage,
                'omsetTab' => 'kotor',
                'omsetUrls' => [
                    'cabang' => route('monitoring.omset-cabang.index'),
                    'kotor' => route('monitoring.omset-kotor.index'),
                ],
                'promo' => $promo,
                'promoOptions' => $this->promoOptions(),
                'today' => now()->toDateString(),
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function financialSummary(string $dateFrom, string $dateTo, string $channel = 'all'): array
    {
        return $this->getFinancialSummary(
            channel: $channel,
            dateFrom: $dateFrom,
            dateTo: $dateTo
        );
    }

    public function export(Request $request)
    {
        $channel = $this->normalizeNullableString($request->query('channel')) ?? 'all';
        $productId = $this->normalizeNullableString($request->query('product_id'));
        $customerId = $this->normalizeNullableString($request->query('customer_id'));
        $q = $this->normalizeNullableString($request->query('q'));
        $dateFrom = $this->normalizeDate($request->query('date_from')) ?? now()->toDateString();
        $dateTo = $this->normalizeDate($request->query('date_to')) ?? now()->toDateString();

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if (! in_array($channel, ['all', 'store', 'ecommerce'], true)) {
            $channel = 'all';
        }

        $promo = $this->normalizeNullableString($request->query('promo')) ?? 'all';

        if (! in_array($promo, ['all', 'none', 'any', 'product', 'voucher'], true)) {
            $promo = 'all';
        }

        $detailRows = $this->getTransactionDetailRows(
            channel: $channel,
            productId: $productId,
            customerId: $customerId,
            q: $q,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            perPage: 100000,
            promo: $promo
        )->getCollection();

        return Excel::download(
            new OmsetKotorSalesExport($detailRows),
            'omset-kotor-detail-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function buildStoreCartNetSubquery()
    {
        return DB::table('store_cart_details as scd')
            ->whereRaw("COALESCE(scd.void_status, '') NOT IN ('void', 'voided', 'gimmick')")
            ->selectRaw('
                scd.cart_id,
                SUM(
                    GREATEST(
                        COALESCE(scd.product_price_amount, 0) - COALESCE(scd.discount_amount, 0),
                        0
                    )
                ) as cart_item_net_total
            ')
            ->groupBy('scd.cart_id');
    }

    private function buildStoreVisitPaymentSubquery()
    {
        return DB::query()->fromSub(
            DB::table('store_visit_payments as svp')
                ->join('payments as pay', 'pay.id', '=', 'svp.payment_id')
                ->whereNull('pay.deleted_at')
                ->where(function ($query) {
                    $query->whereNotNull('pay.completed_at')
                        ->orWhereIn(DB::raw('LOWER(pay.status::text)'), ['paid', 'settlement', 'capture', 'success', 'completed']);
                })
                ->selectRaw('
                    svp.visit_id,
                    pay.id as payment_id,
                    pay.transaction_number,
                    ROW_NUMBER() OVER (
                        PARTITION BY svp.visit_id
                        ORDER BY pay.completed_at DESC NULLS LAST, pay.created_at DESC NULLS LAST, pay.id DESC
                    ) as rn
                '),
            'svp_pay_raw'
        )
            ->select([
                'svp_pay_raw.visit_id',
                'svp_pay_raw.payment_id',
                'svp_pay_raw.transaction_number',
            ])
            ->where('svp_pay_raw.rn', 1);
    }

    private function buildEcommerceOrderNetSubquery()
    {
        return DB::table('ecommerce_order_details as eod')
            ->selectRaw('
                eod.ecommerce_order_id,
                SUM(
                    COALESCE(
                        NULLIF(eod.total_price, 0),
                        (COALESCE(eod.product_price, 0) * COALESCE(eod.quantity, 0)) - COALESCE(eod.total_discount, 0)
                    )
                ) as order_item_net_total
            ')
            ->groupBy('eod.ecommerce_order_id');
    }

    private function buildEcommerceWarehouseRfidSubquery()
    {
        return DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('warehouse_outbounds as wo', 'wo.order_id', '=', 'eo.id')
            ->join('warehouse_outbound_details as wod', 'wod.warehouse_outbound_id', '=', 'wo.id')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wod.item_id')
                    ->on('i.product_id', '=', 'eod.product_id');
            })
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->selectRaw("
                eod.ecommerce_order_id,
                eod.product_id,
                STRING_AGG(DISTINCT i.id::text, ', ') as item_ids,
                STRING_AGG(DISTINCT COALESCE(rt.value, i.id::text), ', ') as rfid_values
            ")
            ->groupBy('eod.ecommerce_order_id', 'eod.product_id');
    }

    private function buildEcommerceStoreRfidSubquery()
    {
        return DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('store_outbounds as so', 'so.ecommerce_order_id', '=', 'eo.id')
            ->join('store_outbound_details as sod', 'sod.store_outbound_id', '=', 'so.id')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'sod.item_id')
                    ->on('i.product_id', '=', 'eod.product_id');
            })
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->selectRaw("
                eod.ecommerce_order_id,
                eod.product_id,
                STRING_AGG(DISTINCT i.id::text, ', ') as item_ids,
                STRING_AGG(DISTINCT COALESCE(rt.value, i.id::text), ', ') as rfid_values
            ")
            ->groupBy('eod.ecommerce_order_id', 'eod.product_id');
    }

    private function discountNamesSql(string $productIdColumn, string $trxDateTimeColumn, string $discountAmountColumn): string
    {
        return "
            CASE
                WHEN COALESCE({$discountAmountColumn}, 0) > 0 THEN
                    COALESCE(
                        (
                            SELECT STRING_AGG(DISTINCT d.name, ', ' ORDER BY d.name)
                            FROM discount_products dp
                            JOIN discounts d ON d.id = dp.discount_id
                            WHERE dp.product_id = {$productIdColumn}
                              AND COALESCE(d.is_active, true) = true
                              AND (d.valid_from IS NULL OR d.valid_from <= {$trxDateTimeColumn})
                              AND (d.valid_until IS NULL OR d.valid_until >= {$trxDateTimeColumn})
                        ),
                        'Diskon Produk'
                    )
                ELSE NULL
            END
        ";
    }

    private function voucherNamesSql(string $trxDateTimeColumn, string $voucherAmountColumn): string
    {
        return "
            CASE
                WHEN COALESCE({$voucherAmountColumn}, 0) > 0 THEN
                    COALESCE(
                        (
                            SELECT STRING_AGG(DISTINCT v.name, ', ' ORDER BY v.name)
                            FROM vouchers v
                            WHERE COALESCE(v.is_active, true) = true
                              AND v.deleted_at IS NULL
                              AND (v.valid_from IS NULL OR v.valid_from <= {$trxDateTimeColumn})
                              AND (v.valid_until IS NULL OR v.valid_until >= {$trxDateTimeColumn})
                        ),
                        'Voucher / Point'
                    )
                ELSE NULL
            END
        ";
    }

    private function buildStoreSalesQuery()
    {
        $cartNetSub = $this->buildStoreCartNetSubquery();
        $storeVisitPaymentSub = $this->buildStoreVisitPaymentSubquery();

        $storeSellingPriceSql = "
            COALESCE(
                NULLIF(scd.product_price_amount, 0),
                (
                    SELECT pp.price
                    FROM product_prices pp
                    WHERE pp.product_id = i.product_id
                      AND pp.price_type::text = 'store'
                      AND pp.valid_from <= scd.created_at::date
                      AND (pp.valid_to IS NULL OR pp.valid_to >= scd.created_at::date)
                    ORDER BY pp.valid_from DESC, pp.created_at DESC
                    LIMIT 1
                ),
                0
            )
        ";

        $storeBasePriceSql = "
            COALESCE(
                NULLIF(scd.product_base_price_amount, 0),
                (
                    SELECT pp.price
                    FROM product_prices pp
                    WHERE pp.product_id = i.product_id
                      AND pp.price_type::text = 'base'
                      AND pp.valid_from <= scd.created_at::date
                      AND (pp.valid_to IS NULL OR pp.valid_to >= scd.created_at::date)
                    ORDER BY pp.valid_from DESC, pp.created_at DESC
                    LIMIT 1
                ),
                0
            )
        ";

        $storeOrderDiscountSql = "
            CASE
                WHEN COALESCE(cart_net.cart_item_net_total, 0) > 0 THEN
                    ROUND(
                        (
                            GREATEST(
                                ({$storeSellingPriceSql}) - COALESCE(scd.discount_amount, 0),
                                0
                            ) / cart_net.cart_item_net_total
                        ) * (COALESCE(sc.voucher_discount, 0) + COALESCE(sc.point_amount, 0)),
                        2
                    )
                ELSE 0
            END
        ";

        return DB::table('store_cart_details as scd')
            ->join('store_carts as sc', 'sc.id', '=', 'scd.cart_id')
            ->join('items as i', 'i.id', '=', 'scd.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('customers as c', 'c.id', '=', 'sc.customer_id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->leftJoinSub($cartNetSub, 'cart_net', function ($join) {
                $join->on('cart_net.cart_id', '=', 'sc.id');
            })
            ->joinSub($storeVisitPaymentSub, 'svp_pay', function ($join) {
                $join->on('svp_pay.visit_id', '=', 'sc.visit_id');
            })
            ->whereRaw("COALESCE(scd.void_status, '') NOT IN ('void', 'voided', 'gimmick')")
            ->selectRaw("
                scd.created_at::date as trx_date,
                scd.created_at as trx_datetime,
                'store' as channel,
                sc.id as transaction_id,
                COALESCE(svp_pay.transaction_number, sc.id::text) as transaction_number,
                c.id as customer_id,
                c.name as customer_name,
                p.id as product_id,
                p.name as product_name,
                p.code as product_code,
                i.id as item_id,
                i.id::text as display_item_id,
                rt.value as rfid_value,
                1::int as qty,

                ({$storeSellingPriceSql})::numeric(18,2) as selling_price,
                ({$storeSellingPriceSql})::numeric(18,2) as gross_sales,

                ({$storeBasePriceSql})::numeric(18,2) as base_price_amount,
                ({$storeBasePriceSql})::numeric(18,2) as base_price_total,

                COALESCE(scd.discount_amount, 0)::numeric(18,2) as product_discount_amount,
                ({$storeOrderDiscountSql})::numeric(18,2) as allocated_order_discount,

                {$this->discountNamesSql('i.product_id', 'scd.created_at', 'scd.discount_amount')} as discount_names,
                {$this->voucherNamesSql('scd.created_at', "({$storeOrderDiscountSql})")} as voucher_names
            ");
    }

    private function buildEcommerceSalesQuery()
    {
        $orderNetSub = $this->buildEcommerceOrderNetSubquery();
        $warehouseRfidSub = $this->buildEcommerceWarehouseRfidSubquery();
        $storeRfidSub = $this->buildEcommerceStoreRfidSubquery();

        $ecommerceSellingPriceSql = "
            COALESCE(
                NULLIF(eod.product_price, 0),
                (
                    SELECT pp.price
                    FROM product_prices pp
                    WHERE pp.product_id = eod.product_id
                      AND pp.price_type::text = 'ecommerce'
                      AND pp.valid_from <= eod.created_at::date
                      AND (pp.valid_to IS NULL OR pp.valid_to >= eod.created_at::date)
                    ORDER BY pp.valid_from DESC, pp.created_at DESC
                    LIMIT 1
                ),
                0
            )
        ";

        $ecommerceBasePriceSql = "
            COALESCE(
                NULLIF(eod.product_base_price_amount, 0),
                (
                    SELECT pp.price
                    FROM product_prices pp
                    WHERE pp.product_id = eod.product_id
                      AND pp.price_type::text = 'base'
                      AND pp.valid_from <= eod.created_at::date
                      AND (pp.valid_to IS NULL OR pp.valid_to >= eod.created_at::date)
                    ORDER BY pp.valid_from DESC, pp.created_at DESC
                    LIMIT 1
                ),
                0
            )
        ";

        $ecommerceGrossSql = "
            ({$ecommerceSellingPriceSql}) * COALESCE(eod.quantity, 0)
        ";

        $ecommerceOrderDiscountSql = "
            CASE
                WHEN COALESCE(order_net.order_item_net_total, 0) > 0 THEN
                    ROUND(
                        (
                            COALESCE(
                                NULLIF(eod.total_price, 0),
                                ({$ecommerceGrossSql}) - COALESCE(eod.total_discount, 0)
                            ) / order_net.order_item_net_total
                        ) * GREATEST(COALESCE(eo.discount_total, 0), 0),
                        2
                    )
                ELSE 0
            END
        ";

        return DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('payments as pay', 'pay.id', '=', 'eo.payment_id')
            ->join('products as p', 'p.id', '=', 'eod.product_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoinSub($orderNetSub, 'order_net', function ($join) {
                $join->on('order_net.ecommerce_order_id', '=', 'eo.id');
            })
            ->whereNull('pay.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('pay.completed_at')
                    ->orWhereIn(DB::raw('LOWER(pay.status::text)'), ['paid', 'settlement', 'capture', 'success', 'completed']);
            })
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->leftJoinSub($warehouseRfidSub, 'ewr', function ($join) {
                $join->on('ewr.ecommerce_order_id', '=', 'eod.ecommerce_order_id')
                    ->on('ewr.product_id', '=', 'eod.product_id');
            })
            ->leftJoinSub($storeRfidSub, 'esr', function ($join) {
                $join->on('esr.ecommerce_order_id', '=', 'eod.ecommerce_order_id')
                    ->on('esr.product_id', '=', 'eod.product_id');
            })
            ->selectRaw("
                eod.created_at::date as trx_date,
                eod.created_at as trx_datetime,
                'ecommerce' as channel,
                eo.id as transaction_id,
                COALESCE(eo.order_number, eo.id::text) as transaction_number,
                c.id as customer_id,
                c.name as customer_name,
                p.id as product_id,
                p.name as product_name,
                p.code as product_code,
                null::uuid as item_id,
                COALESCE(NULLIF(ewr.item_ids, ''), NULLIF(esr.item_ids, '')) as display_item_id,
                COALESCE(NULLIF(ewr.rfid_values, ''), NULLIF(esr.rfid_values, '')) as rfid_value,
                COALESCE(eod.quantity, 0)::int as qty,

                ({$ecommerceSellingPriceSql})::numeric(18,2) as selling_price,
                ({$ecommerceGrossSql})::numeric(18,2) as gross_sales,

                ({$ecommerceBasePriceSql})::numeric(18,2) as base_price_amount,
                (({$ecommerceBasePriceSql}) * COALESCE(eod.quantity, 0))::numeric(18,2) as base_price_total,

                COALESCE(eod.total_discount, 0)::numeric(18,2) as product_discount_amount,
                ({$ecommerceOrderDiscountSql})::numeric(18,2) as allocated_order_discount,

                {$this->discountNamesSql('eod.product_id', 'eod.created_at', 'eod.total_discount')} as discount_names,
                {$this->voucherNamesSql('eod.created_at', "({$ecommerceOrderDiscountSql})")} as voucher_names
            ");
    }

    private function promoOptions(): array
    {
        return [
            'all' => 'Semua Transaksi',
            'none' => 'Tanpa Promo/Diskon (Harga Asli)',
            'any' => 'Ada Promo/Diskon',
            'product' => 'Ada Diskon Produk',
            'voucher' => 'Ada Voucher/Point',
        ];
    }

    private function buildTransactionBaseQuery(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $promo = 'all'
    ) {
        $storeBase = DB::query()->fromSub($this->buildStoreSalesQuery(), 's')
            ->selectRaw('
                s.*,
                GREATEST(s.gross_sales - s.product_discount_amount, 0)::numeric(18,2) as net_after_product_discount,
                GREATEST((s.gross_sales - s.product_discount_amount) - s.allocated_order_discount, 0)::numeric(18,2) as final_paid_amount,
                (s.product_discount_amount + s.allocated_order_discount)::numeric(18,2) as total_discount_amount
            ');

        $ecommerceBase = DB::query()->fromSub($this->buildEcommerceSalesQuery(), 'e')
            ->selectRaw('
                e.*,
                GREATEST(e.gross_sales - e.product_discount_amount, 0)::numeric(18,2) as net_after_product_discount,
                GREATEST((e.gross_sales - e.product_discount_amount) - e.allocated_order_discount, 0)::numeric(18,2) as final_paid_amount,
                (e.product_discount_amount + e.allocated_order_discount)::numeric(18,2) as total_discount_amount
            ');

        if ($channel === 'store') {
            $base = DB::query()->fromSub($storeBase, 'x');
        } elseif ($channel === 'ecommerce') {
            $base = DB::query()->fromSub($ecommerceBase, 'x');
        } else {
            $union = $storeBase->unionAll($ecommerceBase);
            $base = DB::query()->fromSub($union, 'x');
        }

        if ($productId) {
            $base->where('x.product_id', $productId);
        }

        if ($customerId) {
            $base->where('x.customer_id', $customerId);
        }

        if ($dateFrom) {
            $base->whereDate('x.trx_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $base->whereDate('x.trx_date', '<=', $dateTo);
        }

        if ($q) {
            $base->where(function ($query) use ($q) {
                $query->where('x.product_name', 'ILIKE', "%{$q}%")
                    ->orWhere('x.product_code', 'ILIKE', "%{$q}%")
                    ->orWhere('x.customer_name', 'ILIKE', "%{$q}%")
                    ->orWhere('x.transaction_number', 'ILIKE', "%{$q}%")
                    ->orWhere('x.channel', 'ILIKE', "%{$q}%")
                    ->orWhere('x.discount_names', 'ILIKE', "%{$q}%")
                    ->orWhere('x.voucher_names', 'ILIKE', "%{$q}%");
            });
        }

        /*
         * Filter promo/diskon. "none" = hanya transaksi tanpa potongan
         * apa pun sehingga omset kotor = murni harga asli produk.
         */
        match ($promo) {
            'none' => $base->where(function ($query) {
                $query->where('x.product_discount_amount', '<=', 0)
                    ->where('x.allocated_order_discount', '<=', 0);
            }),
            'any' => $base->where(function ($query) {
                $query->where('x.product_discount_amount', '>', 0)
                    ->orWhere('x.allocated_order_discount', '>', 0);
            }),
            'product' => $base->where('x.product_discount_amount', '>', 0),
            'voucher' => $base->where('x.allocated_order_discount', '>', 0),
            default => null,
        };

        return DB::query()
            ->fromSub($base, 'trx')
            ->selectRaw('trx.*');
    }

    private function getSummaryCards(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $promo = 'all'
    ) {
        return DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->selectRaw('
                COUNT(*) as total_rows,
                SUM(x.gross_sales) as total_gross_sales,
                SUM(x.final_paid_amount) as total_final_paid,
                SUM(x.total_discount_amount) as total_discount_amount,
                SUM(x.net_after_product_discount) as total_net_after_product_discount,
                SUM(x.qty) as total_qty,
                COUNT(DISTINCT x.product_id) as total_products,
                COUNT(DISTINCT x.customer_id) as total_customers,
                SUM(x.base_price_total) as total_base_price,
                SUM(x.product_discount_amount) as total_product_discount,
                SUM(x.allocated_order_discount) as total_voucher_discount
            ')
            ->first();
    }

    private function getFinancialSummary(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $promo = 'all'
    ): array {
        $channelRows = DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->selectRaw('
                x.channel,
                COUNT(DISTINCT x.transaction_id) as transactions,
                COALESCE(SUM(x.qty), 0) as qty,
                COALESCE(SUM(x.gross_sales), 0) as gross_sales,
                COALESCE(SUM(x.product_discount_amount), 0) as product_discount,
                COALESCE(SUM(x.net_after_product_discount), 0) as net_after_product_discount,
                COALESCE(SUM(x.allocated_order_discount), 0) as voucher_discount,
                COALESCE(SUM(x.final_paid_amount), 0) as net_sales,
                COALESCE(SUM(x.base_price_total), 0) as base_price
            ')
            ->groupBy('x.channel')
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

    private function getProductRows(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 10,
        string $promo = 'all'
    ) {
        return DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->selectRaw("
                x.channel,
                x.product_id,
                x.product_name,
                x.product_code,
                COUNT(*) as total_transactions,
                COUNT(DISTINCT x.customer_id) as total_customers,
                SUM(x.qty) as total_qty,
                SUM(x.gross_sales) as total_gross_sales,
                SUM(x.base_price_total) as total_base_price,
                SUM(x.product_discount_amount) as total_product_discount,
                SUM(x.allocated_order_discount) as total_voucher_discount,
                SUM(x.total_discount_amount) as total_discount_amount,
                SUM(x.final_paid_amount) as total_final_paid,
                STRING_AGG(DISTINCT NULLIF(x.discount_names, ''), ', ') as discount_names,
                STRING_AGG(DISTINCT NULLIF(x.voucher_names, ''), ', ') as voucher_names
            ")
            ->groupBy('x.channel', 'x.product_id', 'x.product_name', 'x.product_code')
            ->orderBy('x.product_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function getDailyRows(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 10,
        string $promo = 'all'
    ) {
        return DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->selectRaw("
                x.trx_date,
                x.channel,
                x.product_id,
                x.product_name,
                x.product_code,
                COUNT(*) as total_transactions,
                COUNT(DISTINCT x.customer_id) as total_customers,
                SUM(x.qty) as total_qty,
                SUM(x.gross_sales) as total_gross_sales,
                SUM(x.base_price_total) as total_base_price,
                SUM(x.product_discount_amount) as total_product_discount,
                SUM(x.allocated_order_discount) as total_voucher_discount,
                SUM(x.total_discount_amount) as total_discount_amount,
                SUM(x.final_paid_amount) as total_final_paid,
                STRING_AGG(DISTINCT NULLIF(x.discount_names, ''), ', ') as discount_names,
                STRING_AGG(DISTINCT NULLIF(x.voucher_names, ''), ', ') as voucher_names
            ")
            ->groupBy('x.trx_date', 'x.channel', 'x.product_id', 'x.product_name', 'x.product_code')
            ->orderBy('x.trx_date', 'desc')
            ->orderBy('x.product_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function getMonthlyRows(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 10,
        string $promo = 'all'
    ) {
        return DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->selectRaw("
                TO_CHAR(x.trx_date, 'YYYY-MM') as period_month,
                x.channel,
                x.product_id,
                x.product_name,
                x.product_code,
                COUNT(*) as total_transactions,
                COUNT(DISTINCT x.customer_id) as total_customers,
                SUM(x.qty) as total_qty,
                SUM(x.gross_sales) as total_gross_sales,
                SUM(x.base_price_total) as total_base_price,
                SUM(x.product_discount_amount) as total_product_discount,
                SUM(x.allocated_order_discount) as total_voucher_discount,
                SUM(x.total_discount_amount) as total_discount_amount,
                SUM(x.final_paid_amount) as total_final_paid,
                STRING_AGG(DISTINCT NULLIF(x.discount_names, ''), ', ') as discount_names,
                STRING_AGG(DISTINCT NULLIF(x.voucher_names, ''), ', ') as voucher_names
            ")
            ->groupByRaw("TO_CHAR(x.trx_date, 'YYYY-MM'), x.channel, x.product_id, x.product_name, x.product_code")
            ->orderBy('period_month', 'desc')
            ->orderBy('x.product_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function getTransactionDetailRows(
        string $channel = 'all',
        ?string $productId = null,
        ?string $customerId = null,
        ?string $q = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 10,
        string $promo = 'all'
    ) {
        return DB::query()
            ->fromSub(
                $this->buildTransactionBaseQuery(
                    channel: $channel,
                    productId: $productId,
                    customerId: $customerId,
                    q: $q,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                    promo: $promo
                ),
                'x'
            )
            ->orderBy('x.trx_datetime', 'desc')
            ->paginate($perPage)
            ->withQueryString();
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
