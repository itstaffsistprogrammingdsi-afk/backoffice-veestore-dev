<?php

namespace App\Domain\Analytics\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerProductPreferenceQuery
{
    private const DIMENSIONS = [
        'age_group' => 'Kelompok Usia',
        'gender' => 'Gender',
        'province' => 'Provinsi',
        'regency' => 'Kota / Kabupaten',
        'membership' => 'Membership Tier',
        'registration_channel' => 'Kanal Registrasi',
    ];

    private const METRICS = [
        'qty' => ['label' => 'Quantity', 'unit' => 'pcs'],
        'revenue' => ['label' => 'Omzet', 'unit' => 'Rp'],
        'buyers' => ['label' => 'Customer Unik', 'unit' => 'customer'],
        'transactions' => ['label' => 'Transaksi', 'unit' => 'transaksi'],
    ];

    public function build(array $input, int $perPage = 20): array
    {
        $filters = $this->normalizeFilters($input);
        $dimension = $this->dimension($filters['dimension']);
        $sales = $this->salesQuery($filters);

        $summary = $this->summary(clone $sales);
        $summaryMetric = (float) $this->metricValue($summary, $filters['metric']);
        $segmentTotals = $this->segmentTotals(clone $sales, $dimension, $filters['metric']);
        $topProducts = $this->topProducts(clone $sales, $filters['metric'], 12);
        $matrixSegments = $segmentTotals->count() <= 7
            ? $segmentTotals->values()
            : $segmentTotals->sortByDesc('metric_value')->take(7)->values();
        $matrixRows = $this->matrixRows(clone $sales, $dimension, $topProducts, $matrixSegments, $filters['metric']);

        $selectedProduct = $filters['product_id'] !== ''
            ? $topProducts->firstWhere('product_id', $filters['product_id'])
            : $topProducts->first();

        $breakdown = $selectedProduct
            ? $this->segmentTotals(
                (clone $sales)->where('s.product_id', $selectedProduct['product_id']),
                $dimension,
                $filters['metric']
            )
            : collect();

        $insight = $this->insight($selectedProduct, $breakdown, $filters['metric'], $dimension);
        $topSegment = $segmentTotals->sortByDesc('metric_value')->first();

        return [
            'filters' => $filters,
            'dimensionOptions' => collect(self::DIMENSIONS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'metricOptions' => collect(self::METRICS)
                ->map(fn (array $meta, string $value) => [
                    'value' => $value,
                    'label' => $meta['label'],
                    'unit' => $meta['unit'],
                ])
                ->values(),
            'channelOptions' => [
                ['value' => '', 'label' => 'Semua channel'],
                ['value' => 'pos', 'label' => 'POS / Store'],
                ['value' => 'ecommerce', 'label' => 'Ecommerce'],
            ],
            'stores' => $this->stores(),
            'categories' => $this->categories(),
            'brands' => $this->brands(),
            'productOptions' => $this->productOptions($filters),
            'dimensionLabel' => $dimension['label'],
            'metricLabel' => self::METRICS[$filters['metric']]['label'],
            'metricUnit' => self::METRICS[$filters['metric']]['unit'],
            'summary' => [
                'total_products' => (int) ($summary->total_products ?? 0),
                'total_buyers' => (int) ($summary->buyers ?? 0),
                'total_transactions' => (int) ($summary->transactions ?? 0),
                'total_qty' => (int) ($summary->qty ?? 0),
                'total_revenue' => (float) ($summary->revenue ?? 0),
                'metric_value' => $summaryMetric,
                'segment_count' => $segmentTotals->count(),
                'top_segment' => $topSegment
                    ? [
                        'segment_label' => $topSegment['segment_label'],
                        'metric_value' => $topSegment['metric_value'],
                        'share' => $summaryMetric > 0
                            ? (int) round((((float) $topSegment['metric_value']) / $summaryMetric) * 100)
                            : 0,
                    ]
                    : null,
            ],
            'segmentTotals' => $segmentTotals,
            'matrixSegments' => $matrixSegments,
            'matrixRows' => $matrixRows,
            'selectedProduct' => $selectedProduct,
            'breakdown' => $breakdown,
            'insight' => $insight,
            'rows' => $this->detailRows($filters, $dimension, $perPage),
        ];
    }

    public function exportMeta(array $input): array
    {
        $filters = $this->normalizeFilters($input);
        $dimension = $this->dimension($filters['dimension']);
        $storeName = $filters['store_id'] !== ''
            ? (DB::table('stores')->where('id', $filters['store_id'])->value('name') ?: '-')
            : 'Semua Store';
        $channelLabel = match ($filters['channel']) {
            'pos' => 'POS / Store',
            'ecommerce' => 'Ecommerce',
            default => 'Semua Channel',
        };

        return [
            'filters' => $filters,
            'dimension_label' => $dimension['label'],
            'metric_label' => self::METRICS[$filters['metric']]['label'],
            'channel_label' => $channelLabel,
            'store_name' => $storeName,
        ];
    }

    public function exportRows(array $input): Collection
    {
        $filters = $this->normalizeFilters($input);
        $dimension = $this->dimension($filters['dimension']);
        $query = $this->detailQuery($filters, $dimension);
        $metric = $filters['metric'];

        return $query->get()->map(fn ($row) => $this->decorateRow($row, $metric));
    }

    private function normalizeFilters(array $input): array
    {
        $defaultFrom = CarbonImmutable::now()->subDays(89)->toDateString();
        $defaultTo = CarbonImmutable::now()->toDateString();

        $from = $this->normalizeDate($input['date_from'] ?? null) ?: $defaultFrom;
        $to = $this->normalizeDate($input['date_to'] ?? null) ?: $defaultTo;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $channel = trim((string) ($input['channel'] ?? ''));
        $channel = in_array($channel, ['pos', 'ecommerce'], true) ? $channel : '';

        $dimension = trim((string) ($input['dimension'] ?? ''));
        $dimension = array_key_exists($dimension, self::DIMENSIONS) ? $dimension : 'age_group';

        $metric = trim((string) ($input['metric'] ?? ''));
        $metric = array_key_exists($metric, self::METRICS) ? $metric : 'qty';

        return [
            'date_from' => $from,
            'date_to' => $to,
            'channel' => $channel,
            'store_id' => $this->normalizeUuid($input['store_id'] ?? null),
            'category_id' => $this->normalizeUuid($input['category_id'] ?? null),
            'brand_id' => $this->normalizeUuid($input['brand_id'] ?? null),
            'product_id' => $this->normalizeUuid($input['product_id'] ?? null),
            'q' => trim((string) ($input['q'] ?? '')),
            'dimension' => $dimension,
            'metric' => $metric,
        ];
    }

    private function salesQuery(array $filters): Builder
    {
        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as payment', 'payment.id', '=', 'svp.payment_id')
            ->whereNull('payment.deleted_at')
            ->whereRaw('LOWER(payment.status::text) = ?', ['paid'])
            ->selectRaw('svp.visit_id, MIN(COALESCE(payment.completed_at, payment.created_at)) AS paid_at')
            ->groupBy('svp.visit_id');

        $cartNet = DB::table('store_cart_details as cart_detail')
            ->selectRaw('cart_detail.cart_id, SUM(GREATEST(COALESCE(cart_detail.product_price_amount, 0) - COALESCE(cart_detail.discount_amount, 0), 0)) AS item_net_total')
            ->whereRaw("COALESCE(cart_detail.void_status, '') <> 'void'")
            ->groupBy('cart_detail.cart_id');

        $pos = DB::query()
            ->fromSub($paidVisits, 'paid_visits')
            ->join('store_customer_visits as visit', 'visit.id', '=', 'paid_visits.visit_id')
            ->join('store_carts as cart', 'cart.visit_id', '=', 'visit.id')
            ->join('store_cart_details as detail', 'detail.cart_id', '=', 'cart.id')
            ->leftJoinSub($cartNet, 'cart_net', fn ($join) => $join->on('cart_net.cart_id', '=', 'cart.id'))
            ->leftJoin('items as item', 'item.id', '=', 'detail.item_id')
            ->leftJoin('product_prices as price', 'price.id', '=', 'detail.product_price_id')
            ->whereRaw("COALESCE(detail.void_status, '') <> 'void'")
            ->whereRaw('COALESCE(item.product_id, price.product_id) IS NOT NULL')
            ->whereRaw('paid_visits.paid_at::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['channel'] === 'ecommerce', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($filters['store_id'] !== '', fn ($query) => $query->where('visit.store_id', $filters['store_id']))
            ->selectRaw("
                visit.customer_id,
                COALESCE(item.product_id, price.product_id) AS product_id,
                'pos'::text AS channel,
                visit.id AS transaction_id,
                visit.store_id,
                paid_visits.paid_at AS sold_at,
                1::int AS qty,
                GREATEST(
                    GREATEST(COALESCE(detail.product_price_amount, 0) - COALESCE(detail.discount_amount, 0), 0)
                    - CASE
                        WHEN COALESCE(cart_net.item_net_total, 0) > 0 THEN
                            (GREATEST(COALESCE(detail.product_price_amount, 0) - COALESCE(detail.discount_amount, 0), 0) / cart_net.item_net_total)
                            * (COALESCE(cart.voucher_discount, 0) + COALESCE(cart.point_amount, 0))
                        ELSE 0
                      END,
                    0
                )::numeric(18,2) AS amount
            ");

        $ecommerce = DB::query()
            ->from('ecommerce_orders as eo')
            ->join('ecommerce_order_details as eod', 'eod.ecommerce_order_id', '=', 'eo.id')
            ->join('payments as ep', 'ep.id', '=', 'eo.payment_id')
            ->whereNull('ep.deleted_at')
            ->whereRaw('LOWER(ep.status::text) = ?', ['paid'])
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->whereNotNull('eo.customer_id')
            ->whereRaw('COALESCE(ep.completed_at, eo.created_at)::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['channel'] === 'pos', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($filters['store_id'] !== '', fn ($query) => $query->where('eo.handled_by_store_id', $filters['store_id']))
            ->selectRaw("
                eo.customer_id,
                eod.product_id,
                'ecommerce'::text AS channel,
                eo.id AS transaction_id,
                eo.handled_by_store_id AS store_id,
                COALESCE(ep.completed_at, eo.created_at) AS sold_at,
                GREATEST(COALESCE(eod.quantity, 0), 0)::int AS qty,
                COALESCE(eod.total_price, COALESCE(eod.product_price, 0) * COALESCE(eod.quantity, 0), 0)::numeric(18,2) AS amount
            ");

        $latestMemberships = $this->latestMembershipSubquery();

        return DB::query()
            ->fromSub($pos->unionAll($ecommerce), 's')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->leftJoin('products as product', 'product.id', '=', 's.product_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('brands as brand', 'brand.id', '=', 'product.brand_id')
            ->leftJoin('provinces as province', 'province.code', '=', 'c.province_code')
            ->leftJoin('regencies as regency', 'regency.code', '=', 'c.regency_code')
            ->leftJoinSub($latestMemberships, 'lm', fn ($join) => $join->on('lm.customer_id', '=', 'c.id'))
            ->leftJoin('memberships as membership_tier', 'membership_tier.id', '=', 'lm.membership_id')
            ->whereNull('c.deleted_at')
            ->whereNotNull('s.product_id')
            ->when($filters['category_id'] !== '', fn ($query) => $query->where('category.id', $filters['category_id']))
            ->when($filters['brand_id'] !== '', fn ($query) => $query->where('brand.id', $filters['brand_id']))
            ->when($filters['product_id'] !== '', fn ($query) => $query->where('s.product_id', $filters['product_id']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like) {
                    $search->where('product.name', 'ILIKE', $like)
                        ->orWhere('product.code', 'ILIKE', $like)
                        ->orWhere('category.name', 'ILIKE', $like)
                        ->orWhere('brand.name', 'ILIKE', $like);
                });
            });
    }

    private function summary(Builder $query): object
    {
        return $query->selectRaw('
            COUNT(DISTINCT s.product_id)::int AS total_products,
            COUNT(DISTINCT s.customer_id)::int AS buyers,
            COUNT(DISTINCT s.transaction_id)::int AS transactions,
            COALESCE(SUM(s.qty), 0)::int AS qty,
            COALESCE(SUM(s.amount), 0)::numeric AS revenue
        ')->first() ?? (object) [
            'total_products' => 0,
            'buyers' => 0,
            'transactions' => 0,
            'qty' => 0,
            'revenue' => 0,
        ];
    }

    private function segmentTotals(Builder $query, array $dimension, string $metric): Collection
    {
        $query->selectRaw("
            {$dimension['expression']} AS segment_label,
            COALESCE(SUM(s.qty), 0)::int AS qty,
            COALESCE(SUM(s.amount), 0)::numeric AS revenue,
            COUNT(DISTINCT s.customer_id)::int AS buyers,
            COUNT(DISTINCT s.transaction_id)::int AS transactions
        ");

        if ($dimension['sort'] !== null) {
            $query->selectRaw("MIN({$dimension['sort']}) AS sort_order")->orderBy('sort_order');
        }

        $rows = $query
            ->groupByRaw($dimension['expression'])
            ->orderByRaw($this->metricOrderSql($metric).' DESC')
            ->orderBy('segment_label')
            ->get()
            ->map(fn ($row) => $this->decorateRow($row, $metric));

        $total = (float) $rows->sum('metric_value');

        return $rows->map(function (array $row) use ($total) {
            $row['share'] = $total > 0 ? (int) round(($row['metric_value'] / $total) * 100) : 0;

            return $row;
        })->values();
    }

    private function topProducts(Builder $query, string $metric, int $limit): Collection
    {
        return $query
            ->selectRaw('
                s.product_id,
                product.code AS product_code,
                product.name AS product_name,
                category.name AS category_name,
                brand.name AS brand_name,
                COALESCE(SUM(s.qty), 0)::int AS qty,
                COALESCE(SUM(s.amount), 0)::numeric AS revenue,
                COUNT(DISTINCT s.customer_id)::int AS buyers,
                COUNT(DISTINCT s.transaction_id)::int AS transactions
            ')
            ->groupBy('s.product_id', 'product.code', 'product.name', 'category.name', 'brand.name')
            ->orderByRaw($this->metricOrderSql($metric).' DESC')
            ->orderBy('product.name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->decorateRow($row, $metric))
            ->values();
    }

    private function matrixRows(
        Builder $query,
        array $dimension,
        Collection $topProducts,
        Collection $segments,
        string $metric
    ): array {
        if ($topProducts->isEmpty() || $segments->isEmpty()) {
            return [];
        }

        $cells = $query
            ->whereIn('s.product_id', $topProducts->pluck('product_id'))
            ->selectRaw("
                s.product_id,
                {$dimension['expression']} AS segment_label,
                COALESCE(SUM(s.qty), 0)::int AS qty,
                COALESCE(SUM(s.amount), 0)::numeric AS revenue,
                COUNT(DISTINCT s.customer_id)::int AS buyers,
                COUNT(DISTINCT s.transaction_id)::int AS transactions
            ")
            ->groupBy('s.product_id')
            ->groupByRaw($dimension['expression'])
            ->get()
            ->keyBy(fn ($row) => $row->product_id.'|'.$row->segment_label);

        return $topProducts->map(function (array $product) use ($cells, $segments, $metric) {
            return [
                'product_id' => $product['product_id'],
                'product_code' => $product['product_code'],
                'product_name' => $product['product_name'],
                'category_name' => $product['category_name'],
                'brand_name' => $product['brand_name'],
                'metric_value' => $product['metric_value'],
                'qty' => $product['qty'],
                'revenue' => $product['revenue'],
                'buyers' => $product['buyers'],
                'transactions' => $product['transactions'],
                'cells' => $segments->map(function (array $segment) use ($cells, $product, $metric) {
                    $cell = $cells->get($product['product_id'].'|'.$segment['segment_label']);
                    $row = $cell ? $this->decorateRow($cell, $metric) : $this->emptyMetricRow();

                    return [
                        'segment_label' => $segment['segment_label'],
                        'metric_value' => $row['metric_value'],
                        'qty' => $row['qty'],
                        'revenue' => $row['revenue'],
                        'buyers' => $row['buyers'],
                        'transactions' => $row['transactions'],
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private function insight(?array $product, Collection $breakdown, string $metric, array $dimension): ?array
    {
        if (! $product || $breakdown->isEmpty()) {
            return null;
        }

        $total = (float) $breakdown->sum('metric_value');

        if ($total <= 0) {
            return null;
        }

        $sorted = $breakdown->sortByDesc('metric_value')->values();
        $top = $sorted->first();
        $second = $sorted->get(1);
        $topValue = (float) ($top['metric_value'] ?? 0);

        return [
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'product_code' => $product['product_code'],
            'category_name' => $product['category_name'],
            'dimension_label' => $dimension['label'],
            'metric' => $metric,
            'metric_label' => self::METRICS[$metric]['label'],
            'metric_unit' => self::METRICS[$metric]['unit'],
            'segment_label' => $top['segment_label'],
            'metric_value' => $topValue,
            'segment_buyers' => (int) ($top['buyers'] ?? 0),
            'segment_transactions' => (int) ($top['transactions'] ?? 0),
            'share' => (int) round(($topValue / $total) * 100),
            'total_value' => $total,
            'total_buyers' => (int) ($product['buyers'] ?? 0),
            'total_transactions' => (int) ($product['transactions'] ?? 0),
            'second_segment_label' => $second['segment_label'] ?? null,
            'second_value' => $second ? (float) $second['metric_value'] : null,
            'second_share' => $second && $total > 0
                ? (int) round((((float) $second['metric_value']) / $total) * 100)
                : null,
        ];
    }

    private function detailRows(array $filters, array $dimension, int $perPage): LengthAwarePaginator
    {
        return $this->detailQuery($filters, $dimension)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($row) => $this->decorateRow($row, $filters['metric']));
    }

    private function detailQuery(array $filters, array $dimension): Builder
    {
        $query = $this->salesQuery($filters)
            ->selectRaw("
                s.product_id,
                product.code AS product_code,
                product.name AS product_name,
                category.name AS category_name,
                brand.name AS brand_name,
                {$dimension['expression']} AS segment_label,
                COALESCE(SUM(s.qty), 0)::int AS qty,
                COALESCE(SUM(s.amount), 0)::numeric AS revenue,
                COUNT(DISTINCT s.customer_id)::int AS buyers,
                COUNT(DISTINCT s.transaction_id)::int AS transactions
            ")
            ->groupBy('s.product_id', 'product.code', 'product.name', 'category.name', 'brand.name')
            ->groupByRaw($dimension['expression'])
            ->orderByRaw($this->metricOrderSql($filters['metric']).' DESC')
            ->orderBy('product.name')
            ->orderBy('segment_label');

        return $query;
    }

    private function productOptions(array $filters): Collection
    {
        $optionsFilters = [...$filters, 'product_id' => '', 'q' => ''];

        return $this->salesQuery($optionsFilters)
            ->selectRaw('s.product_id, product.code, product.name')
            ->groupBy('s.product_id', 'product.code', 'product.name')
            ->orderBy('product.name')
            ->limit(500)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->product_id,
                'code' => $row->code,
                'name' => $row->name,
            ])
            ->values();
    }

    private function stores(): Collection
    {
        return DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function categories(): Collection
    {
        return DB::table('product_categories')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function brands(): Collection
    {
        return DB::table('brands')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function latestMembershipSubquery(): Builder
    {
        $ranked = DB::table('customer_memberships as cm')
            ->select([
                'cm.customer_id',
                'cm.membership_id',
                'cm.status',
                'cm.tier_evaluated_at',
                'cm.updated_at',
            ])
            ->selectRaw("
                ROW_NUMBER() OVER (
                    PARTITION BY cm.customer_id
                    ORDER BY
                        CASE WHEN cm.status::text = 'active' THEN 0 ELSE 1 END,
                        cm.tier_evaluated_at DESC NULLS LAST,
                        cm.updated_at DESC NULLS LAST
                ) AS row_rank
            ");

        return DB::query()
            ->fromSub($ranked, 'ranked_memberships')
            ->where('row_rank', 1);
    }

    private function dimension(string $key): array
    {
        $ageSql = 'EXTRACT(YEAR FROM AGE(CURRENT_DATE, c.date_of_birth))';
        $ageGroup = "CASE
            WHEN c.date_of_birth IS NULL THEN 'Usia belum diisi'
            WHEN {$ageSql} < 18 THEN '< 18 tahun'
            WHEN {$ageSql} BETWEEN 18 AND 24 THEN '18–24 tahun'
            WHEN {$ageSql} BETWEEN 25 AND 34 THEN '25–34 tahun'
            WHEN {$ageSql} BETWEEN 35 AND 44 THEN '35–44 tahun'
            WHEN {$ageSql} BETWEEN 45 AND 54 THEN '45–54 tahun'
            ELSE '55+ tahun'
        END";
        $ageSort = "CASE
            WHEN c.date_of_birth IS NULL THEN 7
            WHEN {$ageSql} < 18 THEN 1
            WHEN {$ageSql} BETWEEN 18 AND 24 THEN 2
            WHEN {$ageSql} BETWEEN 25 AND 34 THEN 3
            WHEN {$ageSql} BETWEEN 35 AND 44 THEN 4
            WHEN {$ageSql} BETWEEN 45 AND 54 THEN 5
            ELSE 6
        END";

        return match ($key) {
            'gender' => [
                'label' => self::DIMENSIONS[$key],
                'expression' => "CASE LOWER(COALESCE(c.gender::text, ''))
                    WHEN 'male' THEN 'Laki-laki'
                    WHEN 'm' THEN 'Laki-laki'
                    WHEN 'laki-laki' THEN 'Laki-laki'
                    WHEN 'female' THEN 'Perempuan'
                    WHEN 'f' THEN 'Perempuan'
                    WHEN 'perempuan' THEN 'Perempuan'
                    ELSE 'Belum diisi'
                END",
                'sort' => "CASE LOWER(COALESCE(c.gender::text, ''))
                    WHEN 'male' THEN 1
                    WHEN 'female' THEN 2
                    ELSE 3
                END",
            ],
            'province' => [
                'label' => self::DIMENSIONS[$key],
                'expression' => "COALESCE(NULLIF(province.name, ''), 'Belum diisi')",
                'sort' => null,
            ],
            'regency' => [
                'label' => self::DIMENSIONS[$key],
                'expression' => "COALESCE(NULLIF(regency.name, ''), 'Belum diisi')",
                'sort' => null,
            ],
            'membership' => [
                'label' => self::DIMENSIONS[$key],
                'expression' => "COALESCE(membership_tier.name, 'Belum memiliki tier')",
                'sort' => 'COALESCE(membership_tier.sort_order, 9999)',
            ],
            'registration_channel' => [
                'label' => self::DIMENSIONS[$key],
                'expression' => "CASE LOWER(COALESCE(c.registration_channel::text, ''))
                    WHEN 'store' THEN 'Store'
                    WHEN 'ecommerce' THEN 'E-commerce'
                    WHEN 'backoffice' THEN 'Backoffice'
                    WHEN 'warehouse' THEN 'Warehouse'
                    WHEN '' THEN 'Belum diisi'
                    ELSE c.registration_channel::text
                END",
                'sort' => "CASE LOWER(COALESCE(c.registration_channel::text, ''))
                    WHEN 'store' THEN '1'
                    WHEN 'ecommerce' THEN '2'
                    WHEN 'backoffice' THEN '3'
                    WHEN 'warehouse' THEN '4'
                    ELSE '5'
                END",
            ],
            default => [
                'label' => self::DIMENSIONS['age_group'],
                'expression' => $ageGroup,
                'sort' => $ageSort,
            ],
        };
    }

    private function metricOrderSql(string $metric): string
    {
        return match ($metric) {
            'revenue' => 'COALESCE(SUM(s.amount), 0)',
            'buyers' => 'COUNT(DISTINCT s.customer_id)',
            'transactions' => 'COUNT(DISTINCT s.transaction_id)',
            default => 'COALESCE(SUM(s.qty), 0)',
        };
    }

    private function metricValue(object|array $row, string $metric): float|int
    {
        $values = is_array($row) ? $row : (array) $row;

        return match ($metric) {
            'revenue' => (float) ($values['revenue'] ?? 0),
            'buyers' => (int) ($values['buyers'] ?? 0),
            'transactions' => (int) ($values['transactions'] ?? 0),
            default => (int) ($values['qty'] ?? 0),
        };
    }

    private function decorateRow(object $row, string $metric): array
    {
        return [
            'product_id' => $row->product_id ?? null,
            'product_code' => $row->product_code ?? null,
            'product_name' => $row->product_name ?? null,
            'category_name' => $row->category_name ?? null,
            'brand_name' => $row->brand_name ?? null,
            'segment_label' => $row->segment_label ?? null,
            'qty' => (int) ($row->qty ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'buyers' => (int) ($row->buyers ?? 0),
            'transactions' => (int) ($row->transactions ?? 0),
            'metric_value' => $this->metricValue($row, $metric),
        ];
    }

    private function emptyMetricRow(): array
    {
        return [
            'product_id' => null,
            'product_code' => null,
            'product_name' => null,
            'category_name' => null,
            'brand_name' => null,
            'segment_label' => null,
            'qty' => 0,
            'revenue' => 0.0,
            'buyers' => 0,
            'transactions' => 0,
            'metric_value' => 0,
        ];
    }

    private function normalizeUuid(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1
            ? $value
            : '';
    }

    private function normalizeDate(mixed $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) !== 1) {
            return '';
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        return checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : '';
    }
}
