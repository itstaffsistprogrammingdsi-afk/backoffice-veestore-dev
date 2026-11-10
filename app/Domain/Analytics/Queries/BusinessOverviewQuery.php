<?php

namespace App\Domain\Analytics\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BusinessOverviewQuery
{
    private const PERIOD_VALUES = ['today', 'week', 'month', 'year', 'last_month', 'custom'];

    private const ECOMMERCE_STATUS_LABELS = [
        'pending' => 'Menunggu pembayaran',
        'paid' => 'Sudah dibayar',
        'processed' => 'Diproses',
        'packing' => 'Sedang dikemas',
        'shipped' => 'Dikirim',
        'received' => 'Selesai diterima',
        'cancelled' => 'Dibatalkan',
        'on_hold' => 'Ditahan',
    ];

    private const STORE_ORDER_STATUS_LABELS = [
        'draft' => 'Draft',
        'requested' => 'Diminta',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'processing' => 'Diproses',
        'packing' => 'Sedang dikemas',
        'shipped' => 'Dikirim',
        'received' => 'Diterima',
        'cancelled' => 'Dibatalkan',
    ];

    private const RETURN_STATUS_LABELS = [
        'draft' => 'Draft',
        'requested' => 'Diminta',
        'pending_approval' => 'Menunggu persetujuan',
        'approved' => 'Disetujui',
        'partial_approved' => 'Disetujui sebagian',
        'packing' => 'Dikemas',
        'packing_completed' => 'Selesai dikemas',
        'shipped' => 'Dikirim',
        'received' => 'Diterima gudang',
        'rejected' => 'Ditolak',
        'close' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public function build(array $input): array
    {
        $filters = $this->normalizeFilters($input);

        return [
            'filters' => $filters,
            'generated_at' => now()->toIso8601String(),
            'sales' => $this->sales($filters),
            'customers' => $this->customers($filters),
            'orders' => $this->orders($filters),
            'inventory' => $this->inventory($filters),
        ];
    }

    public function salesSnapshot(array $input): array
    {
        return $this->sales($this->normalizeFilters($input));
    }

    public function ordersSnapshot(array $input): array
    {
        return $this->orders($this->normalizeFilters($input));
    }

    public function inventoryOverview(array $input): array
    {
        return $this->inventory($this->normalizeFilters($input));
    }

    public function stockHealthSnapshot(): array
    {
        return $this->inventory($this->normalizeFilters([]), false);
    }

    public function productSalesSnapshot(array $input): array
    {
        $filters = $this->normalizeFilters($input);
        $product = trim((string) ($input['product'] ?? ''));
        $store = trim((string) ($input['store'] ?? ''));
        $limit = max(1, min((int) ($input['limit'] ?? 20), 50));
        $perStore = isset($input['per_store']) && $input['per_store'] !== null
            ? max(1, min((int) $input['per_store'], 10))
            : null;

        $query = $this->productSalesQuery($filters);

        if ($product !== '') {
            $query->where(function (Builder $query) use ($product) {
                $query->where('product_name', 'ilike', '%'.$product.'%')
                    ->orWhere('product_code', 'ilike', '%'.$product.'%');
            });
        }

        if ($store !== '') {
            $query->where(function (Builder $query) use ($store) {
                $query->where('store_name', 'ilike', '%'.$store.'%')
                    ->orWhere('store_code', 'ilike', '%'.$store.'%');
            });
        }

        $grouped = $query
            ->selectRaw('
                store_id,
                store_code,
                store_name,
                product_id,
                product_code,
                product_name,
                SUM(qty)::int AS qty,
                COUNT(DISTINCT transaction_id)::int AS transactions
            ')
            ->groupBy('store_id', 'store_code', 'store_name', 'product_id', 'product_code', 'product_name');

        if ($perStore !== null) {
            $rows = DB::query()
                ->fromSub($grouped, 'sales')
                ->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY store_name ORDER BY qty DESC, product_name) AS rank')
                ->orderBy('store_name')
                ->orderBy('rank')
                ->limit(500)
                ->get()
                ->filter(fn ($row) => (int) $row->rank <= $perStore)
                ->values();
        } else {
            $rows = $grouped
                ->orderByDesc('qty')
                ->orderBy('store_name')
                ->orderBy('product_name')
                ->limit($limit)
                ->get();
        }

        return [
            'period' => $filters,
            'filters' => [
                'product' => $product !== '' ? $product : null,
                'store' => $store !== '' ? $store : null,
                'per_store' => $perStore,
            ],
            'row_count' => $rows->count(),
            'rows' => $rows->map(fn ($row) => [
                'store_id' => $row->store_id,
                'store_code' => $row->store_code,
                'store_name' => $row->store_name,
                'product_id' => $row->product_id,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'qty' => (int) $row->qty,
                'transactions' => (int) $row->transactions,
            ])->values()->all(),
        ];
    }

    private function normalizeFilters(array $input): array
    {
        $period = trim((string) ($input['period'] ?? ''));

        if (! in_array($period, self::PERIOD_VALUES, true)) {
            $period = null;
        }

        $hasCustomRange = $this->normalizeDate($input['date_from'] ?? null) !== ''
            || $this->normalizeDate($input['date_to'] ?? null) !== '';

        if ($period === null) {
            $period = $hasCustomRange ? 'custom' : 'month';
        }

        if ($period === 'custom') {
            $defaultFrom = CarbonImmutable::now()->subDays(29)->toDateString();
            $defaultTo = CarbonImmutable::now()->toDateString();
            $from = $this->normalizeDate($input['date_from'] ?? null) ?: $defaultFrom;
            $to = $this->normalizeDate($input['date_to'] ?? null) ?: $defaultTo;
        } else {
            [$from, $to] = $this->periodRange($period);
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $channel = $input['channel'] ?? 'all';

        if (! in_array($channel, ['all', 'store', 'ecommerce'], true)) {
            $channel = 'all';
        }

        return [
            'period' => $period,
            'date_from' => $from,
            'date_to' => $to,
            'channel' => $channel,
            'store_id' => $this->normalizeNullableString($input['store_id'] ?? null),
            'warehouse_id' => $this->normalizeNullableString($input['warehouse_id'] ?? null),
        ];
    }

    private function periodRange(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'week' => [
                $now->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
                $now->endOfWeek(CarbonImmutable::SUNDAY)->toDateString(),
            ],
            'month' => [
                $now->startOfMonth()->toDateString(),
                $now->endOfMonth()->toDateString(),
            ],
            'year' => [
                $now->startOfYear()->toDateString(),
                $now->endOfYear()->toDateString(),
            ],
            'last_month' => [
                $now->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            default => [
                $now->startOfMonth()->toDateString(),
                $now->endOfMonth()->toDateString(),
            ],
        };
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function sales(array $filters): array
    {
        $summaryRow = $this->salesQuery($filters)->selectRaw("
            COUNT(*)::int AS transactions,
            COALESCE(SUM(amount), 0)::numeric(18,2) AS revenue,
            COUNT(DISTINCT customer_id)::int AS buyers,
            COUNT(*) FILTER (WHERE channel = 'POS')::int AS pos_transactions,
            COALESCE(SUM(amount) FILTER (WHERE channel = 'POS'), 0)::numeric(18,2) AS pos_revenue,
            COUNT(*) FILTER (WHERE channel = 'Ecommerce')::int AS ecommerce_transactions,
            COALESCE(SUM(amount) FILTER (WHERE channel = 'Ecommerce'), 0)::numeric(18,2) AS ecommerce_revenue
        ")->first();

        $transactions = (int) ($summaryRow->transactions ?? 0);
        $revenue = (float) ($summaryRow->revenue ?? 0);
        $posRevenue = (float) ($summaryRow->pos_revenue ?? 0);
        $ecommerceRevenue = (float) ($summaryRow->ecommerce_revenue ?? 0);

        $grossRow = $this->grossSalesQuery($filters)->selectRaw("
            COALESCE(SUM(gross_amount), 0)::numeric(18,2) AS gross_revenue,
            COALESCE(SUM(net_amount), 0)::numeric(18,2) AS net_revenue,
            COALESCE(SUM(gross_amount) FILTER (WHERE channel = 'POS'), 0)::numeric(18,2) AS pos_gross_revenue,
            COALESCE(SUM(net_amount) FILTER (WHERE channel = 'POS'), 0)::numeric(18,2) AS pos_net_revenue,
            COALESCE(SUM(gross_amount) FILTER (WHERE channel = 'Ecommerce'), 0)::numeric(18,2) AS ecommerce_gross_revenue,
            COALESCE(SUM(net_amount) FILTER (WHERE channel = 'Ecommerce'), 0)::numeric(18,2) AS ecommerce_net_revenue
        ")->first();

        $grossRevenue = (float) ($grossRow->gross_revenue ?? 0);
        $netRevenue = (float) ($grossRow->net_revenue ?? 0);
        $posGrossRevenue = (float) ($grossRow->pos_gross_revenue ?? 0);
        $posNetRevenue = (float) ($grossRow->pos_net_revenue ?? 0);
        $ecommerceGrossRevenue = (float) ($grossRow->ecommerce_gross_revenue ?? 0);
        $ecommerceNetRevenue = (float) ($grossRow->ecommerce_net_revenue ?? 0);

        $trend = $this->salesQuery($filters)
            ->selectRaw('sold_at::date AS date, COALESCE(SUM(amount), 0)::numeric(18,2) AS revenue, COUNT(*)::int AS transactions')
            ->groupByRaw('sold_at::date')
            ->orderByRaw('sold_at::date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'label' => CarbonImmutable::parse($row->date)->format('d M'),
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ])
            ->values()
            ->all();

        $stores = $this->salesQuery($filters)
            ->selectRaw('
                store_id,
                store_name,
                COALESCE(SUM(amount), 0)::numeric(18,2) AS revenue,
                COUNT(*)::int AS transactions,
                COUNT(DISTINCT customer_id)::int AS buyers
            ')
            ->groupBy('store_id', 'store_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'store_id' => $row->store_id,
                'store_name' => $row->store_name,
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
                'buyers' => (int) $row->buyers,
            ])
            ->values()
            ->all();

        $products = $this->productSalesQuery($filters)
            ->selectRaw('
                product_id,
                product_code,
                product_name,
                category_name,
                COALESCE(SUM(qty), 0)::int AS qty,
                COUNT(DISTINCT transaction_id)::int AS transactions
            ')
            ->groupBy('product_id', 'product_code', 'product_name', 'category_name')
            ->orderByDesc(DB::raw('SUM(qty)'))
            ->orderBy('product_name')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'category_name' => $row->category_name,
                'qty' => (int) $row->qty,
                'transactions' => (int) $row->transactions,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'gross_revenue' => $grossRevenue,
                'net_revenue' => $netRevenue,
                'revenue' => $revenue,
                'transactions' => $transactions,
                'average_transaction' => $transactions > 0 ? $revenue / $transactions : 0,
                'buyers' => (int) ($summaryRow->buyers ?? 0),
            ],
            'channels' => [
                [
                    'key' => 'pos',
                    'label' => 'POS / Store',
                    'gross_revenue' => $posGrossRevenue,
                    'net_revenue' => $posNetRevenue,
                    'revenue' => $posRevenue,
                    'transactions' => (int) ($summaryRow->pos_transactions ?? 0),
                    'share' => $revenue > 0 ? (int) round(($posRevenue / $revenue) * 100) : 0,
                ],
                [
                    'key' => 'ecommerce',
                    'label' => 'Ecommerce',
                    'gross_revenue' => $ecommerceGrossRevenue,
                    'net_revenue' => $ecommerceNetRevenue,
                    'revenue' => $ecommerceRevenue,
                    'transactions' => (int) ($summaryRow->ecommerce_transactions ?? 0),
                    'share' => $revenue > 0 ? (int) round(($ecommerceRevenue / $revenue) * 100) : 0,
                ],
            ],
            'trend' => $trend,
            'top_stores' => $stores,
            'top_products' => $products,
        ];
    }

    private function customers(array $filters): array
    {
        $from = $this->startOfDay($filters['date_from']);
        $to = $this->endOfDay($filters['date_to']);

        $members = DB::table('customers')
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('deleted_at')
            ->whereIn('registration_channel', ['store', 'ecommerce'])
            ->when($filters['channel'] !== 'all', fn ($query) => $query->where('registration_channel', $filters['channel']))
            ->when($filters['store_id'], function ($query) use ($filters) {
                $query->where('registration_channel', 'store')
                    ->where('registration_source_id', $filters['store_id']);
            })
            ->selectRaw("
                COUNT(*)::int AS new_members,
                COUNT(*) FILTER (WHERE registration_channel = 'store')::int AS store_members,
                COUNT(*) FILTER (WHERE registration_channel = 'ecommerce')::int AS ecommerce_members
            ")
            ->first();

        $visits = DB::table('store_customer_visits')
            ->whereBetween('check_in_at', [$from, $to])
            ->when($filters['store_id'], fn ($query) => $query->where('store_id', $filters['store_id']))
            ->selectRaw('COUNT(*)::int AS visits, COUNT(DISTINCT customer_id)::int AS unique_visitors')
            ->first();

        return [
            'summary' => [
                'new_members' => (int) ($members->new_members ?? 0),
                'store_members' => (int) ($members->store_members ?? 0),
                'ecommerce_members' => (int) ($members->ecommerce_members ?? 0),
                'visits' => (int) ($visits->visits ?? 0),
                'unique_visitors' => (int) ($visits->unique_visitors ?? 0),
            ],
        ];
    }

    private function orders(array $filters): array
    {
        $from = $this->startOfDay($filters['date_from']);
        $to = $this->endOfDay($filters['date_to']);
        $channel = $filters['channel'];

        $ecommerceStatuses = [];

        if (in_array($channel, ['all', 'ecommerce'], true)) {
            $ecommerceStatuses = $this->statusRows(
                DB::table('ecommerce_orders')
                    ->whereBetween('created_at', [$from, $to])
                    ->when($filters['store_id'], fn ($query) => $query->where('handled_by_store_id', $filters['store_id']))
                    ->when($filters['warehouse_id'], fn ($query) => $query->where('handled_by_warehouse_id', $filters['warehouse_id']))
                    ->selectRaw('status::text AS status, COUNT(*)::int AS total')
                    ->groupByRaw('status::text')
                    ->orderByDesc('total'),
                self::ECOMMERCE_STATUS_LABELS
            );
        }

        $statusCount = function (array $rows, string $status): int {
            foreach ($rows as $row) {
                if ($row['status'] === $status) {
                    return (int) $row['count'];
                }
            }

            return 0;
        };

        $ecommerceTotal = (int) collect($ecommerceStatuses)->sum('count');
        $ecommerceCompleted = $statusCount($ecommerceStatuses, 'received');
        $ecommerceCancelled = $statusCount($ecommerceStatuses, 'cancelled');
        $ecommerceOpen = max(0, $ecommerceTotal - $ecommerceCompleted - $ecommerceCancelled);
        $ecommercePendingPayment = $statusCount($ecommerceStatuses, 'pending');
        $ecommerceNeedAttention = 0;

        if (in_array($channel, ['all', 'ecommerce'], true)) {
            $ecommerceNeedAttention = DB::table('ecommerce_orders')
                ->whereBetween('created_at', [$from, $to])
                ->whereIn(DB::raw('LOWER(status::text)'), ['paid', 'processed', 'packing', 'shipped', 'on_hold'])
                ->where('created_at', '<', now()->subDay())
                ->when($filters['store_id'], fn ($query) => $query->where('handled_by_store_id', $filters['store_id']))
                ->when($filters['warehouse_id'], fn ($query) => $query->where('handled_by_warehouse_id', $filters['warehouse_id']))
                ->count();
        }

        $storeOrderStatuses = [];
        $returnStatuses = [];

        if (in_array($channel, ['all', 'store'], true)) {
            $storeOrderStatuses = $this->statusRows(
                DB::table('store_orders')
                    ->whereBetween('created_at', [$from, $to])
                    ->when($filters['store_id'], fn ($query) => $query->where('store_id', $filters['store_id']))
                    ->when($filters['warehouse_id'], fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
                    ->selectRaw('status::text AS status, COUNT(*)::int AS total')
                    ->groupByRaw('status::text')
                    ->orderByDesc('total'),
                self::STORE_ORDER_STATUS_LABELS
            );

            $returnStatuses = $this->statusRows(
                DB::table('store_returns')
                    ->whereBetween('created_at', [$from, $to])
                    ->when($filters['store_id'], fn ($query) => $query->where('store_id', $filters['store_id']))
                    ->when($filters['warehouse_id'], fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
                    ->selectRaw('status::text AS status, COUNT(*)::int AS total')
                    ->groupByRaw('status::text')
                    ->orderByDesc('total'),
                self::RETURN_STATUS_LABELS
            );
        }

        return [
            'summary' => [
                'ecommerce_total' => $ecommerceTotal,
                'ecommerce_open' => $ecommerceOpen,
                'ecommerce_completed' => $ecommerceCompleted,
                'ecommerce_cancelled' => $ecommerceCancelled,
                'ecommerce_pending_payment' => $ecommercePendingPayment,
                'ecommerce_need_attention' => $ecommerceNeedAttention,
                'store_order_total' => (int) collect($storeOrderStatuses)->sum('count'),
                'store_order_open' => (int) collect($storeOrderStatuses)
                    ->whereNotIn('status', ['received', 'cancelled', 'rejected'])
                    ->sum('count'),
                'return_total' => (int) collect($returnStatuses)->sum('count'),
                'return_open' => (int) collect($returnStatuses)
                    ->whereNotIn('status', ['received', 'rejected', 'close', 'cancelled'])
                    ->sum('count'),
            ],
            'ecommerce_statuses' => $ecommerceStatuses,
            'store_order_statuses' => $storeOrderStatuses,
            'return_statuses' => $returnStatuses,
            'attention' => $this->attentionRows($filters),
        ];
    }

    private function inventory(array $filters, bool $includeOperations = true): array
    {
        $warehouseStock = DB::table('items as i')
            ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
            ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
            ->whereNull('w.deleted_at')
            ->whereRaw("LOWER(i.status::text) = 'warehouse_stock'")
            ->when($filters['warehouse_id'], fn ($query) => $query->where('wi.warehouse_id', $filters['warehouse_id']))
            ->selectRaw('
                COUNT(i.id)::int AS units,
                COUNT(DISTINCT i.product_id)::int AS skus,
                COUNT(DISTINCT wi.warehouse_id)::int AS locations
            ')
            ->first();

        $storeStock = DB::table('items as i')
            ->join('store_items as si', 'si.item_id', '=', 'i.id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->whereNull('s.deleted_at')
            ->whereIn(DB::raw('LOWER(i.status::text)'), ['store_stock', 'store_display'])
            ->when($filters['store_id'], fn ($query) => $query->where('si.store_id', $filters['store_id']))
            ->selectRaw('
                COUNT(i.id)::int AS units,
                COUNT(DISTINCT i.product_id)::int AS skus,
                COUNT(DISTINCT si.store_id)::int AS locations
            ')
            ->first();

        $expiring = DB::table('items')
            ->whereIn(DB::raw('LOWER(status::text)'), ['warehouse_stock', 'store_stock', 'store_display'])
            ->selectRaw("
                COUNT(*) FILTER (WHERE expired_date < CURRENT_DATE)::int AS expired_units,
                COUNT(*) FILTER (
                    WHERE expired_date >= CURRENT_DATE
                    AND expired_date <= CURRENT_DATE + INTERVAL '90 days'
                )::int AS expiring_90_units,
                COUNT(*) FILTER (WHERE expired_date IS NOT NULL)::int AS with_expiry_units
            ")
            ->first();

        $warehouseLow = $this->lowStockQuery('warehouse', $filters);
        $storeLow = $this->lowStockQuery('store', $filters);
        $warehouseSample = $this->lowStockSample($warehouseLow);
        $storeSample = $this->lowStockSample($storeLow);

        $lowStockRows = collect()
            ->merge($warehouseSample['rows'])
            ->merge($storeSample['rows'])
            ->sortByDesc('shortfall')
            ->take(6)
            ->values()
            ->all();

        $result = [
            'summary' => [
                'warehouse_units' => (int) ($warehouseStock->units ?? 0),
                'warehouse_skus' => (int) ($warehouseStock->skus ?? 0),
                'warehouse_locations' => (int) ($warehouseStock->locations ?? 0),
                'store_units' => (int) ($storeStock->units ?? 0),
                'store_skus' => (int) ($storeStock->skus ?? 0),
                'store_locations' => (int) ($storeStock->locations ?? 0),
                'low_stock_alerts' => $warehouseSample['total'] + $storeSample['total'],
                'expired_units' => (int) ($expiring->expired_units ?? 0),
                'expiring_90_units' => (int) ($expiring->expiring_90_units ?? 0),
                'with_expiry_units' => (int) ($expiring->with_expiry_units ?? 0),
            ],
            'low_stock' => $lowStockRows,
        ];

        if (! $includeOperations) {
            return $result;
        }

        $result['logistics'] = $this->logisticsActivity($filters);
        $result['pending_queues'] = $this->pendingQueues($filters);

        return $result;
    }

    private function salesQuery(array $filters): Builder
    {
        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->selectRaw('
                svp.visit_id,
                MIN(COALESCE(p.completed_at, p.created_at)) AS sold_at,
                COALESCE(SUM(p.total_amount), 0)::numeric(18,2) AS amount
            ')
            ->groupBy('svp.visit_id');

        $pos = DB::query()
            ->fromSub($paidVisits, 'paid_visits')
            ->join('store_customer_visits as v', 'v.id', '=', 'paid_visits.visit_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->whereNull('s.deleted_at')
            ->whereRaw('paid_visits.sold_at::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($query) => $query->where('v.store_id', $filters['store_id']))
            ->selectRaw("
                'POS'::text AS channel,
                v.id AS transaction_id,
                v.customer_id,
                v.store_id,
                s.name AS store_name,
                paid_visits.sold_at,
                paid_visits.amount
            ");

        $ecommerce = DB::table('ecommerce_orders as eo')
            ->join('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoin('stores as s', 's.id', '=', 'eo.handled_by_store_id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->whereRaw('COALESCE(p.completed_at, eo.created_at)::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($query) => $query->where('eo.handled_by_store_id', $filters['store_id']))
            ->selectRaw("
                'Ecommerce'::text AS channel,
                eo.id AS transaction_id,
                eo.customer_id,
                eo.handled_by_store_id AS store_id,
                COALESCE(s.name, 'Ecommerce tanpa cabang') AS store_name,
                COALESCE(p.completed_at, eo.created_at) AS sold_at,
                COALESCE(p.total_amount, eo.grand_total, 0)::numeric(18,2) AS amount
            ");

        $sales = match ($filters['channel']) {
            'store' => $pos,
            'ecommerce' => $ecommerce,
            default => $pos->unionAll($ecommerce),
        };

        return DB::query()->fromSub($sales, 'sales');
    }

    private function grossSalesQuery(array $filters): Builder
    {
        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->selectRaw('svp.visit_id, MIN(COALESCE(p.completed_at, p.created_at)) AS sold_at')
            ->groupBy('svp.visit_id');

        $cartNet = DB::table('store_cart_details as scd')
            ->whereRaw("COALESCE(scd.void_status, '') NOT IN ('void', 'voided', 'gimmick')")
            ->selectRaw('
                scd.cart_id,
                SUM(GREATEST(COALESCE(scd.product_price_amount, 0) - COALESCE(scd.discount_amount, 0), 0)) AS cart_item_net_total
            ')
            ->groupBy('scd.cart_id');

        $orderNet = DB::table('ecommerce_order_details as eod')
            ->selectRaw('
                eod.ecommerce_order_id,
                SUM(
                    COALESCE(
                        NULLIF(eod.total_price, 0),
                        (COALESCE(eod.product_price, 0) * COALESCE(eod.quantity, 0)) - COALESCE(eod.total_discount, 0)
                    )
                ) AS order_item_net_total
            ')
            ->groupBy('eod.ecommerce_order_id');

        $storeSellingPrice = 'COALESCE(NULLIF(scd.product_price_amount, 0), price.price, 0)';
        $storeLineNet = "GREATEST({$storeSellingPrice} - COALESCE(scd.discount_amount, 0), 0)";
        $storeOrderDiscount = "
            CASE
                WHEN COALESCE(cart_net.cart_item_net_total, 0) > 0 THEN
                    ROUND(
                        ({$storeLineNet} / cart_net.cart_item_net_total)
                        * (COALESCE(sc.voucher_discount, 0) + COALESCE(sc.point_amount, 0)),
                        2
                    )
                ELSE 0
            END
        ";

        $pos = DB::table('store_cart_details as scd')
            ->join('store_carts as sc', 'sc.id', '=', 'scd.cart_id')
            ->join('store_customer_visits as v', 'v.id', '=', 'sc.visit_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->joinSub($paidVisits, 'paid_visits', 'paid_visits.visit_id', '=', 'sc.visit_id')
            ->leftJoin('product_prices as price', 'price.id', '=', 'scd.product_price_id')
            ->leftJoinSub($cartNet, 'cart_net', 'cart_net.cart_id', '=', 'sc.id')
            ->whereNull('s.deleted_at')
            ->whereRaw("COALESCE(scd.void_status, '') NOT IN ('void', 'voided', 'gimmick')")
            ->whereRaw('paid_visits.sold_at::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($query) => $query->where('v.store_id', $filters['store_id']))
            ->selectRaw("
                'POS'::text AS channel,
                ({$storeSellingPrice})::numeric(18,2) AS gross_amount,
                GREATEST(({$storeLineNet}) - ({$storeOrderDiscount}), 0)::numeric(18,2) AS net_amount
            ");

        $ecommerceSellingPrice = 'COALESCE(NULLIF(eod.product_price, 0), price.price, 0)';
        $ecommerceGross = "({$ecommerceSellingPrice} * GREATEST(COALESCE(eod.quantity, 0), 0))";
        $ecommerceLineNet = "COALESCE(NULLIF(eod.total_price, 0), ({$ecommerceGross}) - COALESCE(eod.total_discount, 0))";
        $ecommerceOrderDiscount = "
            CASE
                WHEN COALESCE(order_net.order_item_net_total, 0) > 0 THEN
                    ROUND(
                        ({$ecommerceLineNet} / order_net.order_item_net_total)
                        * GREATEST(COALESCE(eo.discount_total, 0), 0),
                        2
                    )
                ELSE 0
            END
        ";

        $ecommerce = DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoin('product_prices as price', 'price.id', '=', 'eod.product_price_id')
            ->leftJoinSub($orderNet, 'order_net', 'order_net.ecommerce_order_id', '=', 'eo.id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->whereRaw('COALESCE(p.completed_at, eo.created_at)::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($query) => $query->where('eo.handled_by_store_id', $filters['store_id']))
            ->selectRaw("
                'Ecommerce'::text AS channel,
                ({$ecommerceGross})::numeric(18,2) AS gross_amount,
                GREATEST(({$ecommerceLineNet}) - ({$ecommerceOrderDiscount}), 0)::numeric(18,2) AS net_amount
            ");

        $grossSales = match ($filters['channel']) {
            'store' => $pos,
            'ecommerce' => $ecommerce,
            default => $pos->unionAll($ecommerce),
        };

        return DB::query()->fromSub($grossSales, 'gross_sales');
    }

    private function productSalesQuery(array $filters): Builder
    {
        $paidVisits = DB::table('store_visit_payments as svp')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->selectRaw('svp.visit_id, MIN(COALESCE(p.completed_at, p.created_at)) AS paid_at')
            ->groupBy('svp.visit_id');

        $pos = DB::query()
            ->fromSub($paidVisits, 'paid_visits')
            ->join('store_customer_visits as visit', 'visit.id', '=', 'paid_visits.visit_id')
            ->join('stores as store', 'store.id', '=', 'visit.store_id')
            ->join('store_carts as cart', 'cart.visit_id', '=', 'visit.id')
            ->join('store_cart_details as detail', 'detail.cart_id', '=', 'cart.id')
            ->leftJoin('items as item', 'item.id', '=', 'detail.item_id')
            ->leftJoin('product_prices as price', 'price.id', '=', 'detail.product_price_id')
            ->leftJoin('products as product', function ($join) {
                $join->on('product.id', '=', DB::raw('COALESCE(item.product_id, price.product_id)'));
            })
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->whereNull('store.deleted_at')
            ->whereRaw("COALESCE(detail.void_status, '') NOT IN ('void', 'voided', 'gimmick')")
            ->whereNotNull('product.id')
            ->whereRaw('paid_visits.paid_at::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($query) => $query->where('visit.store_id', $filters['store_id']))
            ->selectRaw('
                store.id AS store_id,
                store.code AS store_code,
                store.name AS store_name,
                product.id AS product_id,
                product.code AS product_code,
                product.name AS product_name,
                category.name AS category_name,
                visit.id AS transaction_id,
                1::int AS qty
            ');

        $ecommerce = DB::table('ecommerce_orders as eo')
            ->join('ecommerce_order_details as detail', 'detail.ecommerce_order_id', '=', 'eo.id')
            ->join('payments as p', 'p.id', '=', 'eo.payment_id')
            ->join('products as product', 'product.id', '=', 'detail.product_id')
            ->leftJoin('product_categories as category', 'category.id', '=', 'product.category_id')
            ->leftJoin('stores as store', 'store.id', '=', 'eo.handled_by_store_id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->whereRaw('COALESCE(p.completed_at, eo.created_at)::date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']])
            ->when($filters['store_id'], fn ($query) => $query->where('eo.handled_by_store_id', $filters['store_id']))
            ->selectRaw('
                eo.handled_by_store_id AS store_id,
                store.code AS store_code,
                COALESCE(store.name, \'Ecommerce tanpa cabang\') AS store_name,
                product.id AS product_id,
                product.code AS product_code,
                product.name AS product_name,
                category.name AS category_name,
                eo.id AS transaction_id,
                GREATEST(COALESCE(detail.quantity, 0), 0)::int AS qty
            ');

        $productSales = match ($filters['channel']) {
            'store' => $pos,
            'ecommerce' => $ecommerce,
            default => $pos->unionAll($ecommerce),
        };

        return DB::query()->fromSub($productSales, 'product_sales');
    }

    private function statusRows(Builder $query, array $labels): array
    {
        $rows = $query->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'label' => $labels[$row->status] ?? $this->humanizeStatus((string) $row->status),
                'count' => (int) $row->total,
            ])
            ->values();

        $total = max(1, (int) $rows->sum('count'));

        return $rows
            ->map(function (array $row) use ($total) {
                $row['share'] = (int) round(($row['count'] / $total) * 100);

                return $row;
            })
            ->all();
    }

    private function attentionRows(array $filters): array
    {
        $from = $this->startOfDay($filters['date_from']);
        $to = $this->endOfDay($filters['date_to']);
        $items = collect();

        if (in_array($filters['channel'], ['all', 'ecommerce'], true)) {
            $items = $items->merge(
                DB::table('ecommerce_orders as eo')
                    ->leftJoin('stores as s', 's.id', '=', 'eo.handled_by_store_id')
                    ->leftJoin('warehouses as w', 'w.id', '=', 'eo.handled_by_warehouse_id')
                    ->whereBetween('eo.created_at', [$from, $to])
                    ->whereIn(DB::raw('LOWER(eo.status::text)'), ['paid', 'processed', 'packing', 'shipped', 'on_hold'])
                    ->where('eo.created_at', '<', now()->subDay())
                    ->when($filters['store_id'], fn ($query) => $query->where('eo.handled_by_store_id', $filters['store_id']))
                    ->when($filters['warehouse_id'], fn ($query) => $query->where('eo.handled_by_warehouse_id', $filters['warehouse_id']))
                    ->selectRaw("
                        'Pesanan Ecommerce'::text AS type,
                        COALESCE(eo.order_number, eo.id::text) AS reference,
                        eo.status::text AS status,
                        ROUND(EXTRACT(EPOCH FROM (NOW() - eo.created_at)) / 3600)::int AS age_hours,
                        COALESCE(eo.grand_total, 0)::numeric(18,2) AS amount,
                        COALESCE(s.name, w.name, '-') AS partner
                    ")
                    ->orderBy('eo.created_at')
                    ->limit(8)
                    ->get()
            );
        }

        if (in_array($filters['channel'], ['all', 'store'], true)) {
            $items = $items->merge(
                DB::table('store_orders as so')
                    ->leftJoin('stores as s', 's.id', '=', 'so.store_id')
                    ->leftJoin('warehouses as w', 'w.id', '=', 'so.warehouse_id')
                    ->whereBetween('so.created_at', [$from, $to])
                    ->whereNotIn(DB::raw('LOWER(so.status::text)'), ['received', 'cancelled', 'rejected'])
                    ->where('so.created_at', '<', now()->subDay())
                    ->when($filters['store_id'], fn ($query) => $query->where('so.store_id', $filters['store_id']))
                    ->when($filters['warehouse_id'], fn ($query) => $query->where('so.warehouse_id', $filters['warehouse_id']))
                    ->selectRaw("
                        'Permintaan Store'::text AS type,
                        COALESCE(so.order_number, so.id::text) AS reference,
                        so.status::text AS status,
                        ROUND(EXTRACT(EPOCH FROM (NOW() - so.created_at)) / 3600)::int AS age_hours,
                        NULL::numeric AS amount,
                        COALESCE(s.name, w.name, '-') AS partner
                    ")
                    ->orderBy('so.created_at')
                    ->limit(8)
                    ->get()
            );

            $items = $items->merge(
                DB::table('store_returns as sr')
                    ->leftJoin('stores as s', 's.id', '=', 'sr.store_id')
                    ->leftJoin('warehouses as w', 'w.id', '=', 'sr.warehouse_id')
                    ->whereBetween('sr.created_at', [$from, $to])
                    ->whereNotIn(DB::raw('LOWER(sr.status::text)'), ['received', 'rejected', 'close', 'cancelled'])
                    ->where('sr.created_at', '<', now()->subDay())
                    ->when($filters['store_id'], fn ($query) => $query->where('sr.store_id', $filters['store_id']))
                    ->when($filters['warehouse_id'], fn ($query) => $query->where('sr.warehouse_id', $filters['warehouse_id']))
                    ->selectRaw("
                        'Retur Store'::text AS type,
                        COALESCE(sr.return_number, sr.id::text) AS reference,
                        sr.status::text AS status,
                        ROUND(EXTRACT(EPOCH FROM (NOW() - sr.created_at)) / 3600)::int AS age_hours,
                        NULL::numeric AS amount,
                        COALESCE(s.name, w.name, '-') AS partner
                    ")
                    ->orderBy('sr.created_at')
                    ->limit(8)
                    ->get()
            );
        }

        return $items
            ->map(function ($row) {
                $status = (string) $row->status;

                return [
                    'type' => (string) $row->type,
                    'reference' => (string) $row->reference,
                    'status' => $status,
                    'status_label' => self::ECOMMERCE_STATUS_LABELS[$status]
                        ?? self::STORE_ORDER_STATUS_LABELS[$status]
                        ?? self::RETURN_STATUS_LABELS[$status]
                        ?? $this->humanizeStatus($status),
                    'age_hours' => (int) $row->age_hours,
                    'amount' => $row->amount === null ? null : (float) $row->amount,
                    'partner' => (string) $row->partner,
                ];
            })
            ->sortByDesc('age_hours')
            ->take(8)
            ->values()
            ->all();
    }

    private function lowStockQuery(string $type, array $filters): Builder
    {
        $warehouse = $type === 'warehouse';

        $reserved = DB::table('stock_reservations')
            ->where('handler_type', $warehouse ? 'warehouse' : 'store')
            ->where('status', 'active')
            ->selectRaw('handler_id, product_id, SUM(quantity)::int AS qty')
            ->groupBy('handler_id', 'product_id');

        if ($warehouse) {
            return DB::table('warehouse_items as wi')
                ->join('items as i', 'i.id', '=', 'wi.item_id')
                ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
                ->join('products as p', 'p.id', '=', 'i.product_id')
                ->join('warehouse_stock_barriers as b', function ($join) {
                    $join->on('b.warehouse_id', '=', 'wi.warehouse_id')
                        ->on('b.product_id', '=', 'i.product_id');
                })
                ->leftJoinSub($reserved, 'r', function ($join) {
                    $join->on('r.handler_id', '=', 'wi.warehouse_id')
                        ->on('r.product_id', '=', 'i.product_id');
                })
                ->whereNull('w.deleted_at')
                ->whereRaw("LOWER(i.status::text) = 'warehouse_stock'")
                ->when($filters['warehouse_id'], fn ($query) => $query->where('wi.warehouse_id', $filters['warehouse_id']))
                ->selectRaw("
                    'Gudang'::text AS location_type,
                    w.name AS location_name,
                    p.code AS product_code,
                    p.name AS product_name,
                    COUNT(i.id)::int AS on_hand,
                    COALESCE(MAX(r.qty), 0)::int AS reserved,
                    GREATEST(COUNT(i.id) - COALESCE(MAX(r.qty), 0), 0)::int AS available,
                    b.stock_barrier,
                    CEIL(b.stock_barrier * (1 + COALESCE(b.tolerance_percentage, 0) / 100))::int AS threshold
                ")
                ->groupBy('w.id', 'w.name', 'p.id', 'p.code', 'p.name', 'b.stock_barrier', 'b.tolerance_percentage')
                ->havingRaw('GREATEST(COUNT(i.id) - COALESCE(MAX(r.qty), 0), 0) <= CEIL(b.stock_barrier * (1 + COALESCE(b.tolerance_percentage, 0) / 100))');
        }

        return DB::table('store_items as si')
            ->join('items as i', 'i.id', '=', 'si.item_id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('store_stock_barriers as b', function ($join) {
                $join->on('b.store_id', '=', 'si.store_id')
                    ->on('b.product_id', '=', 'i.product_id');
            })
            ->leftJoinSub($reserved, 'r', function ($join) {
                $join->on('r.handler_id', '=', 'si.store_id')
                    ->on('r.product_id', '=', 'i.product_id');
            })
            ->whereNull('s.deleted_at')
            ->whereIn(DB::raw('LOWER(i.status::text)'), ['store_stock', 'store_display'])
            ->when($filters['store_id'], fn ($query) => $query->where('si.store_id', $filters['store_id']))
            ->selectRaw("
                'Cabang'::text AS location_type,
                s.name AS location_name,
                p.code AS product_code,
                p.name AS product_name,
                COUNT(i.id)::int AS on_hand,
                COALESCE(MAX(r.qty), 0)::int AS reserved,
                GREATEST(COUNT(i.id) - COALESCE(MAX(r.qty), 0), 0)::int AS available,
                b.stock_barrier,
                CEIL(b.stock_barrier * (1 + COALESCE(b.tolerance_percentage, 0) / 100))::int AS threshold
            ")
            ->groupBy('s.id', 's.name', 'p.id', 'p.code', 'p.name', 'b.stock_barrier', 'b.tolerance_percentage')
            ->havingRaw('GREATEST(COUNT(i.id) - COALESCE(MAX(r.qty), 0), 0) <= CEIL(b.stock_barrier * (1 + COALESCE(b.tolerance_percentage, 0) / 100))');
    }

    private function lowStockSample(Builder $query): array
    {
        $rows = DB::query()
            ->fromSub($query, 'low')
            ->selectRaw('*, (threshold - available)::int AS shortfall, COUNT(*) OVER()::int AS total_matches')
            ->orderByDesc('shortfall')
            ->limit(6)
            ->get();

        return [
            'total' => (int) ($rows->first()->total_matches ?? 0),
            'rows' => $rows->map(fn ($row) => [
                'location_type' => (string) $row->location_type,
                'location_name' => (string) $row->location_name,
                'product_code' => (string) $row->product_code,
                'product_name' => (string) $row->product_name,
                'on_hand' => (int) $row->on_hand,
                'reserved' => (int) $row->reserved,
                'available' => (int) $row->available,
                'stock_barrier' => (int) $row->stock_barrier,
                'threshold' => (int) $row->threshold,
                'shortfall' => (int) $row->shortfall,
            ])
                ->all(),
        ];
    }

    private function logisticsActivity(array $filters): array
    {
        $from = $this->startOfDay($filters['date_from']);
        $to = $this->endOfDay($filters['date_to']);
        $warehouse = $filters['warehouse_id'] ? ['warehouse_id' => $filters['warehouse_id']] : [];
        $store = $filters['store_id'] ? ['store_id' => $filters['store_id']] : [];

        return [
            'warehouse_inbound' => $this->documentSummary('warehouse_inbounds', 'quantity_item', $from, $to, $warehouse),
            'warehouse_outbound' => $this->documentSummary('warehouse_outbounds', 'quantity_item', $from, $to, $warehouse),
            'store_inbound' => $this->documentSummary('store_inbounds', 'quantity_item', $from, $to, $store),
            'store_outbound' => $this->documentSummary('store_outbounds', 'item_quantity', $from, $to, $store),
            'store_return' => $this->documentSummary('store_returns', null, $from, $to, $store + $warehouse),
        ];
    }

    private function documentSummary(string $table, ?string $quantityColumn, string $from, string $to, array $conditions = []): array
    {
        $query = DB::table($table)->whereBetween('created_at', [$from, $to]);

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        $row = $query->selectRaw(
            'COUNT(*)::int AS documents'.($quantityColumn ? ", COALESCE(SUM({$quantityColumn}), 0)::int AS units" : ', 0::int AS units')
        )->first();

        return [
            'documents' => (int) ($row->documents ?? 0),
            'units' => (int) ($row->units ?? 0),
        ];
    }

    private function pendingQueues(array $filters): array
    {
        $storeTransferOutbound = DB::table('warehouse_outbound_details')
            ->selectRaw('warehouse_outbound_id, COUNT(*)::int as outbound_qty')
            ->groupBy('warehouse_outbound_id');

        $storeTransferReceived = DB::table('store_inbounds as si')
            ->join('store_inbound_details as sid', 'sid.store_inbound_id', '=', 'si.id')
            ->whereNotNull('si.order_id')
            ->whereNotNull('si.received_date')
            ->selectRaw('si.order_id, COUNT(sid.id)::int as received_qty')
            ->groupBy('si.order_id');

        return [
            'warehouse_outbound_unreleased' => DB::table('warehouse_outbounds')
                ->whereNull('released_at')
                ->when($filters['warehouse_id'], fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
                ->count(),
            'store_inbound_pending_qc' => DB::table('store_inbounds')
                ->whereRaw('LOWER(qc_status::text) = ?', ['pending'])
                ->when($filters['store_id'], fn ($query) => $query->where('store_id', $filters['store_id']))
                ->count(),
            'store_inbound_partial_qc' => DB::table('store_inbounds')
                ->whereRaw('LOWER(qc_status::text) = ?', ['partial_passed'])
                ->when($filters['store_id'], fn ($query) => $query->where('store_id', $filters['store_id']))
                ->count(),
            'store_transfer_not_recorded' => DB::table('warehouse_outbounds as wo')
                ->join('store_orders as so', 'so.id', '=', 'wo.order_id')
                ->leftJoinSub($storeTransferOutbound, 'outbound', 'outbound.warehouse_outbound_id', '=', 'wo.id')
                ->leftJoinSub($storeTransferReceived, 'received', 'received.order_id', '=', 'so.id')
                ->whereRaw("(LOWER(wo.outbound_type::text) = 'store' OR LOWER(wo.order_ref::text) = 'store')")
                ->whereNotNull('wo.released_at')
                ->whereRaw('COALESCE(received.received_qty, 0) < COALESCE(outbound.outbound_qty, 0)')
                ->when($filters['warehouse_id'], fn ($query) => $query->where('wo.warehouse_id', $filters['warehouse_id']))
                ->when($filters['store_id'], fn ($query) => $query->where('so.store_id', $filters['store_id']))
                ->count(),
        ];
    }

    private function startOfDay(string $date): string
    {
        return CarbonImmutable::parse($date)->startOfDay()->toDateTimeString();
    }

    private function endOfDay(string $date): string
    {
        return CarbonImmutable::parse($date)->endOfDay()->toDateTimeString();
    }

    private function humanizeStatus(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
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
