<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use App\Support\Concerns\NormalizesDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class PromoUsageController extends Controller
{
    use NormalizesDate;

    public function index(Request $request)
    {
        return Inertia::render('Monitoring/Report', [
            'title' => 'Promo Usage',
            'description' => 'Laporan monitoring.',
            'data' => $this->reportData($request),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.promo-usage.export')],
        ]);
    }

    public function reportData(Request $request): array
    {
        $filters = $this->getFilters($request);

        $rows = $this->buildRowsQuery($filters)
            ->orderByDesc('o.created_at')
            ->orderBy('o.order_number')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = $this->getSummary($filters);

        $stores = DB::table('stores')
            ->when(Schema::hasColumn('stores', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $promos = DB::table('promos')
            ->when(Schema::hasColumn('promos', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $discounts = DB::table('discounts')
            ->when(Schema::hasColumn('discounts', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $vouchers = DB::table('vouchers')
            ->when(Schema::hasColumn('vouchers', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $orderStatuses = DB::table('ecommerce_orders')
            ->selectRaw('DISTINCT status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return [
            'rows' => $rows,
            'summary' => $summary,

            'q' => $filters['q'],
            'promoId' => $filters['promo_id'],
            'discountId' => $filters['discount_id'],
            'storeId' => $filters['store_id'],
            'voucherId' => $filters['voucher_id'],
            'orderStatus' => $filters['order_status'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'perPage' => $filters['per_page'],
            'lastUpdate' => now()->format('d-m-Y H:i:s'),

            'stores' => $stores,
            'promos' => $promos,
            'discounts' => $discounts,
            'vouchers' => $vouchers,
            'orderStatuses' => $orderStatuses,
        ];
    }

    public function export(Request $request)
    {
        $filename = 'promo-usage-report-'.now()->format('Ymd_His').'.xlsx';

        return app(ReportExportService::class)->enqueue($request, 'promo-usage', $filename);
    }

    private function getFilters(Request $request): array
    {
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'promo_id' => $this->normalizeNullableString($request->query('promo_id')),
            'discount_id' => $this->normalizeNullableString($request->query('discount_id')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'voucher_id' => $this->normalizeNullableString($request->query('voucher_id')),
            'order_status' => $this->normalizeNullableString($request->query('order_status')),
            'from' => $this->normalizeDate($request->query('from')) ?: now()->startOfMonth()->toDateString(),
            'to' => $this->normalizeDate($request->query('to')) ?: now()->toDateString(),
            'per_page' => $perPage,
        ];
    }

    private function buildRowsQuery(array $filters)
    {
        $query = DB::table('ecommerce_order_details as od')
            ->join('ecommerce_orders as o', 'o.id', '=', 'od.ecommerce_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'od.product_id')
            ->leftJoin('ecommerce_order_detail_discounts as ds', 'ds.id', '=', 'od.discount_snapshot_id')
            ->leftJoin('discounts as d', 'd.id', '=', 'ds.master_id')
            ->leftJoin('promos as p', function ($join) {
                $join->whereRaw('p.id = COALESCE(d.promo_id, ds.promo_id)');
            })
            ->leftJoin('stores as s', function ($join) {
                $join->whereRaw('s.id = ANY(p.store_id)');
            })
            ->leftJoin('ecommerce_order_vouchers as cov', 'cov.id', '=', 'o.checkout_voucher_snapshot_id')
            ->leftJoin('vouchers as cv', 'cv.id', '=', 'cov.master_id')
            ->leftJoin('ecommerce_order_vouchers as sov', 'sov.id', '=', 'o.shipping_voucher_snapshot_id')
            ->leftJoin('vouchers as sv', 'sv.id', '=', 'sov.master_id')
            ->select([
                'od.id as order_detail_id',
                'od.ecommerce_order_id',
                'od.discount_snapshot_id',
                'od.quantity',
                'od.product_price',
                'od.product_discount',
                'od.total_discount',
                'od.total_price',

                'ds.id as detail_discount_snapshot_id',
                'ds.master_id as discount_master_id',
                'ds.promo_id as detail_promo_id',
                'ds.name as discount_name',
                'ds.description as discount_description',
                'ds.type as discount_type',
                'ds.value as discount_value',
                'ds.max_discount',
                'ds.min_purchase',
                'ds.priority',
                'ds.combinable',
                'ds.is_active',
                'ds.created_at as snapshot_created_at',

                'd.id as discount_id',
                DB::raw('COALESCE(d.promo_id, ds.promo_id) as promo_id'),

                'p.name as promo_name',
                'p.code as promo_code',

                'o.id as order_id',
                'o.order_number',
                'o.status as order_status',
                'o.created_at as order_created_at',
                'o.sub_total',
                'o.discount_total',
                'o.grand_total',
                'o.checkout_voucher_snapshot_id',
                'o.shipping_voucher_snapshot_id',

                'c.id as customer_id',
                'c.name as customer_name',
                'c.email',
                'c.wa_number',

                'pr.id as product_id',
                'pr.name as product_name',
                'pr.code as product_code',

                'cov.id as checkout_order_voucher_id',
                'cov.master_id as checkout_voucher_master_id',
                'cov.code as checkout_snapshot_voucher_code',
                'cov.name as checkout_snapshot_voucher_name',
                'cov.context as checkout_voucher_context',

                'cv.id as checkout_voucher_id',
                'cv.code as checkout_voucher_code',
                'cv.name as checkout_voucher_name',

                'sov.id as shipping_order_voucher_id',
                'sov.master_id as shipping_voucher_master_id',
                'sov.code as shipping_snapshot_voucher_code',
                'sov.name as shipping_snapshot_voucher_name',
                'sov.context as shipping_voucher_context',

                'sv.id as shipping_voucher_id',
                'sv.code as shipping_voucher_code',
                'sv.name as shipping_voucher_name',

                DB::raw("
                    COALESCE(
                        NULLIF(
                            TRIM(
                                COALESCE(cv.name, cov.name, '') ||
                                CASE
                                    WHEN COALESCE(cv.code, cov.code) IS NOT NULL
                                         AND COALESCE(cv.code, cov.code) <> ''
                                    THEN ' (' || COALESCE(cv.code, cov.code) || ')'
                                    ELSE ''
                                END
                            ),
                            ''
                        ),
                        o.checkout_voucher_snapshot_id::text,
                        '-'
                    ) as checkout_voucher_label
                "),

                DB::raw("
                    COALESCE(
                        NULLIF(
                            TRIM(
                                COALESCE(sv.name, sov.name, '') ||
                                CASE
                                    WHEN COALESCE(sv.code, sov.code) IS NOT NULL
                                         AND COALESCE(sv.code, sov.code) <> ''
                                    THEN ' (' || COALESCE(sv.code, sov.code) || ')'
                                    ELSE ''
                                END
                            ),
                            ''
                        ),
                        o.shipping_voucher_snapshot_id::text,
                        '-'
                    ) as shipping_voucher_label
                "),

                DB::raw("COALESCE(STRING_AGG(DISTINCT s.name, ', '), '-') as store_name"),
            ])
            ->where(function ($w) {
                $w->whereNotNull('ds.id')
                    ->orWhereNotNull('o.checkout_voucher_snapshot_id')
                    ->orWhereNotNull('o.shipping_voucher_snapshot_id');
            });

        $this->applyFilters($query, $filters);

        return $query->groupBy([
            'od.id',
            'od.ecommerce_order_id',
            'od.discount_snapshot_id',
            'od.quantity',
            'od.product_price',
            'od.product_discount',
            'od.total_discount',
            'od.total_price',

            'ds.id',
            'ds.master_id',
            'ds.promo_id',
            'ds.name',
            'ds.description',
            'ds.type',
            'ds.value',
            'ds.max_discount',
            'ds.min_purchase',
            'ds.priority',
            'ds.combinable',
            'ds.is_active',
            'ds.created_at',

            'd.id',
            'd.promo_id',

            'p.name',
            'p.code',

            'o.id',
            'o.order_number',
            'o.status',
            'o.created_at',
            'o.sub_total',
            'o.discount_total',
            'o.grand_total',
            'o.checkout_voucher_snapshot_id',
            'o.shipping_voucher_snapshot_id',

            'c.id',
            'c.name',
            'c.email',
            'c.wa_number',

            'pr.id',
            'pr.name',
            'pr.code',

            'cov.id',
            'cov.master_id',
            'cov.code',
            'cov.name',
            'cov.context',

            'cv.id',
            'cv.code',
            'cv.name',

            'sov.id',
            'sov.master_id',
            'sov.code',
            'sov.name',
            'sov.context',

            'sv.id',
            'sv.code',
            'sv.name',
        ]);
    }

    private function getSummary(array $filters)
    {
        $subQuery = $this->buildRowsQuery($filters);

        return DB::query()
            ->fromSub($subQuery, 'x')
            ->selectRaw('
                COUNT(*)::int as total_usage,
                COUNT(DISTINCT x.order_id)::int as total_order,
                COALESCE(SUM(COALESCE(x.quantity, 0)), 0)::int as total_qty,
                COALESCE(SUM(COALESCE(x.product_discount, 0)), 0) as total_product_discount,
                COALESCE(SUM(COALESCE(x.total_discount, 0)), 0) as total_discount,
                COUNT(CASE WHEN x.detail_discount_snapshot_id IS NOT NULL THEN 1 END)::int as total_discount_usage,
                COUNT(CASE WHEN x.checkout_order_voucher_id IS NOT NULL THEN 1 END)::int as total_checkout_voucher,
                COUNT(CASE WHEN x.shipping_order_voucher_id IS NOT NULL THEN 1 END)::int as total_shipping_voucher
            ')
            ->first();
    }

    private function applyFilters($query, array $filters): void
    {
        $q = $filters['q'] ?? '';
        $promoId = $filters['promo_id'] ?? null;
        $discountId = $filters['discount_id'] ?? null;
        $storeId = $filters['store_id'] ?? null;
        $voucherId = $filters['voucher_id'] ?? null;
        $orderStatus = $filters['order_status'] ?? null;
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        if ($q !== '') {
            $like = "%{$q}%";

            $query->where(function ($sub) use ($like) {
                $sub->where('o.order_number', 'ILIKE', $like)
                    ->orWhere('c.name', 'ILIKE', $like)
                    ->orWhere('c.email', 'ILIKE', $like)
                    ->orWhere('c.wa_number', 'ILIKE', $like)
                    ->orWhere('pr.name', 'ILIKE', $like)
                    ->orWhere('pr.code', 'ILIKE', $like)
                    ->orWhere('ds.name', 'ILIKE', $like)
                    ->orWhere('p.name', 'ILIKE', $like)
                    ->orWhere('p.code', 'ILIKE', $like)
                    ->orWhere('o.status', 'ILIKE', $like)
                    ->orWhere('cov.name', 'ILIKE', $like)
                    ->orWhere('cov.code', 'ILIKE', $like)
                    ->orWhere('cv.name', 'ILIKE', $like)
                    ->orWhere('cv.code', 'ILIKE', $like)
                    ->orWhere('sov.name', 'ILIKE', $like)
                    ->orWhere('sov.code', 'ILIKE', $like)
                    ->orWhere('sv.name', 'ILIKE', $like)
                    ->orWhere('sv.code', 'ILIKE', $like);
            });
        }

        if ($promoId) {
            $query->whereRaw('COALESCE(d.promo_id, ds.promo_id) = ?', [$promoId]);
        }

        if ($discountId) {
            $query->where(function ($sub) use ($discountId) {
                $sub->where('d.id', $discountId)
                    ->orWhere('ds.master_id', $discountId);
            });
        }

        if ($storeId) {
            $query->whereRaw('?::uuid = ANY(p.store_id)', [$storeId]);
        }

        if ($voucherId) {
            $query->where(function ($sub) use ($voucherId) {
                $sub->where('cv.id', $voucherId)
                    ->orWhere('sv.id', $voucherId)
                    ->orWhere('cov.master_id', $voucherId)
                    ->orWhere('sov.master_id', $voucherId);
            });
        }

        if ($orderStatus) {
            $query->where('o.status', $orderStatus);
        }

        $query->whereBetween(DB::raw('o.created_at::date'), [$from, $to]);
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
