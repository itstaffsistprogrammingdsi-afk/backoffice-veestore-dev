<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class Analytics extends Controller
{
    public function index(Request $request)
    {
        $roleName = $this->getRoleName();

        $filters = $this->filters($request);

        $fromTs = Carbon::parse($filters['from'])->startOfDay()->toDateTimeString();
        $toTs = Carbon::parse($filters['to'])->endOfDay()->toDateTimeString();

        $paidStatuses = [
            'paid',
            'processed',
            'shipped',
            'received',
            'completed',
            'complete',
            'success',
            'settlement',
            'done',
        ];

        $stores = $this->activeStores();
        $warehouses = $this->activeWarehouses();

        $ordersAll = $this->ordersUnionQuery($fromTs, $toTs, $filters);

        $salesSummary = (clone $ordersAll)
            ->whereIn('x.status', $paidStatuses)
            ->selectRaw("
                COUNT(*)::int AS order_count,
                COUNT(*) FILTER (WHERE x.channel = 'store')::int AS store_order_count,
                COUNT(*) FILTER (WHERE x.channel = 'ecommerce')::int AS ecommerce_order_count,
                COUNT(DISTINCT x.customer_id) FILTER (WHERE x.customer_id IS NOT NULL AND x.customer_id <> '')::int AS buyers,
                COALESCE(SUM(x.grand_total), 0)::numeric AS gmv,
                COALESCE(SUM(x.grand_total) FILTER (WHERE x.channel = 'store'), 0)::numeric AS store_gmv,
                COALESCE(SUM(x.grand_total) FILTER (WHERE x.channel = 'ecommerce'), 0)::numeric AS ecommerce_gmv
            ")
            ->first();

        $orderCount = (int) ($salesSummary->order_count ?? 0);
        $gmv = (float) ($salesSummary->gmv ?? 0);
        $aov = $orderCount > 0 ? round($gmv / $orderCount, 2) : 0;
        $buyers = (int) ($salesSummary->buyers ?? 0);

        $channelSalesRows = (clone $ordersAll)
            ->whereIn('x.status', $paidStatuses)
            ->selectRaw("
                CASE
                    WHEN x.channel = 'store' THEN 'Store'
                    WHEN x.channel = 'ecommerce' THEN 'Ecommerce'
                    ELSE 'Lainnya'
                END AS label,
                COUNT(*)::int AS orders,
                COALESCE(SUM(x.grand_total), 0)::numeric AS gmv
            ")
            ->groupBy('label')
            ->orderByDesc('gmv')
            ->get();

        $gmvPerStore = $this->gmvPerStore($ordersAll, $paidStatuses);

        $orderStatusRows = (clone $ordersAll)
            ->selectRaw("
                x.channel,
                COALESCE(NULLIF(x.status, ''), 'unknown') AS status,
                COUNT(*)::int AS total
            ")
            ->groupBy('x.channel', 'x.status')
            ->orderBy('x.channel')
            ->orderByDesc('total')
            ->get();

        $memberSummary = DB::query()
            ->fromSub($this->customerBaseQuery($filters, $fromTs, $toTs), 'c')
            ->selectRaw("
                COUNT(*)::int AS total_member,
                COUNT(*) FILTER (WHERE c.registration_channel = 'store')::int AS total_store,
                COUNT(*) FILTER (WHERE c.registration_channel = 'ecommerce')::int AS total_ecommerce
            ")
            ->first();

        $visitSummary = $this->visitSummary($fromTs, $toTs, $filters);
        $wms = $this->wmsSummary($fromTs, $toTs, $filters);
        $lowStockWarehouse = $this->lowStockWarehouse($filters);
        $lowStockStore = $this->lowStockStore($filters);

        $actionItems = [];

        if ($lowStockStore->isNotEmpty()) {
            $actionItems[] = [
                'type' => 'warning',
                'title' => 'Stok cabang rendah',
                'message' => number_format($lowStockStore->count(), 0, ',', '.').' item store berada pada kondisi stok rendah.',
            ];
        }

        if ($lowStockWarehouse->isNotEmpty()) {
            $actionItems[] = [
                'type' => 'warning',
                'title' => 'Stok gudang rendah',
                'message' => number_format($lowStockWarehouse->count(), 0, ',', '.').' item warehouse berada pada kondisi stok rendah.',
            ];
        }

        $insights = [];

        if ($gmv > 0) {
            $insights[] = [
                'title' => 'Performa penjualan',
                'message' => 'Periode ini menghasilkan GMV Rp'.number_format($gmv, 0, ',', '.').' dari '.number_format($orderCount, 0, ',', '.').' order dengan AOV Rp'.number_format($aov, 0, ',', '.').'.',
            ];
        }

        if ($gmvPerStore->isNotEmpty()) {
            $best = $gmvPerStore->first();
            $insights[] = [
                'title' => 'Store terbaik',
                'message' => ($best->store_name ?? 'Store Tidak Diketahui').' memimpin dengan GMV Rp'.number_format((float) $best->gmv, 0, ',', '.').'.',
            ];
        }

        if ((int) ($memberSummary->total_member ?? 0) > 0) {
            $insights[] = [
                'title' => 'Basis member',
                'message' => number_format((int) $memberSummary->total_member, 0, ',', '.').' member terdaftar pada periode filter aktif.',
            ];
        }

        return Inertia::render('Dashboard/Index', [
            'roleName' => $roleName,
            'filters' => $filters,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'storeId' => $filters['store_id'],
            'warehouseId' => $filters['warehouse_id'],
            'channel' => $filters['channel'],
            'autoRefresh' => $filters['auto_refresh'],
            'stores' => $stores,
            'warehouses' => $warehouses,
            'salesSummary' => $salesSummary,
            'orderCount' => $orderCount,
            'gmv' => $gmv,
            'aov' => $aov,
            'buyers' => $buyers,
            'channelSalesRows' => $channelSalesRows,
            'gmvPerStore' => $gmvPerStore,
            'orderStatusRows' => $orderStatusRows,
            'memberSummary' => $memberSummary,
            'wms' => $wms,
            'lowStockWarehouse' => $lowStockWarehouse,
            'lowStockStore' => $lowStockStore,
            'visitSummary' => $visitSummary,
            'actionItems' => $actionItems,
            'insights' => $insights,
            'urls' => ['index' => route('dashboard-analytics')],
        ]);
    }

    private function getRoleName(): ?string
    {
        $roleName = null;

        if (! Auth::check()) {
            return null;
        }

        $roleName = session('user_role_name');

        if ($roleName) {
            return $roleName;
        }

        try {
            $tableNames = config('permission.table_names');

            if (! $tableNames || ! isset($tableNames['model_has_roles'], $tableNames['roles'])) {
                return null;
            }

            $roleName = DB::connection(config('permission.connection', 'immostore_backoffice'))
                ->table($tableNames['model_has_roles'].' as mr')
                ->join($tableNames['roles'].' as r', 'r.id', '=', 'mr.role_id')
                ->where('mr.model_type', User::class)
                ->where('mr.model_id', Auth::id())
                ->orderBy('r.created_at')
                ->value('r.name');

            session(['user_role_name' => $roleName]);
        } catch (\Throwable $e) {
            $roleName = null;
        }

        return $roleName;
    }

    private function filters(Request $request): array
    {
        $from = $request->input('from') ?: now()->toDateString();
        $to = $request->input('to') ?: now()->toDateString();

        try {
            $from = Carbon::parse($from)->toDateString();
        } catch (\Throwable $e) {
            $from = now()->toDateString();
        }

        try {
            $to = Carbon::parse($to)->toDateString();
        } catch (\Throwable $e) {
            $to = now()->toDateString();
        }

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        $channel = $request->input('channel');
        if (! in_array($channel, ['all', 'store', 'ecommerce'], true)) {
            $channel = 'all';
        }

        return [
            'from' => $from,
            'to' => $to,
            'store_id' => $request->input('store_id') ?: null,
            'warehouse_id' => $request->input('warehouse_id') ?: null,
            'channel' => $channel,
            'auto_refresh' => $request->boolean('auto_refresh'),
        ];
    }

    private function activeStores()
    {
        if (! $this->hasTable('stores')) {
            return collect();
        }

        return DB::table('stores')
            ->select('id', 'name')
            ->when($this->hasColumn('stores', 'code'), fn ($q) => $q->addSelect('code'))
            ->when($this->hasColumn('stores', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->get();
    }

    private function activeWarehouses()
    {
        if (! $this->hasTable('warehouses')) {
            return collect();
        }

        return DB::table('warehouses')
            ->select('id', 'name')
            ->when($this->hasColumn('warehouses', 'code'), fn ($q) => $q->addSelect('code'))
            ->when($this->hasColumn('warehouses', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->get();
    }

    private static ?array $tables = null;

    private static ?array $columns = null;

    private function hasTable(string $table): bool
    {
        if (self::$tables === null) {
            self::$tables = [];

            foreach (DB::select("select table_name from information_schema.tables where table_schema = current_schema() and table_type = 'BASE TABLE'") as $row) {
                self::$tables[$row->table_name] = true;
            }
        }

        return isset(self::$tables[$table]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (self::$columns === null) {
            self::$columns = [];

            foreach (DB::select('select table_name, column_name from information_schema.columns where table_schema = current_schema()') as $row) {
                self::$columns[$row->table_name][$row->column_name] = true;
            }
        }

        return isset(self::$columns[$table][$column]);
    }

    private function amountExpr(string $table, string $alias): string
    {
        $candidates = [
            'grand_total',
            'total_amount',
            'net_total',
            'total',
            'amount',
            'final_total',
            'order_total',
            'subtotal',
        ];

        $cols = [];

        foreach ($candidates as $col) {
            if ($this->hasColumn($table, $col)) {
                $cols[] = "{$alias}.{$col}::numeric";
            }
        }

        if (! $cols) {
            return '0::numeric';
        }

        return 'COALESCE('.implode(', ', $cols).', 0)::numeric';
    }

    private function emptyOrdersQuery()
    {
        return DB::query()->fromRaw('
            (
                SELECT
                    NULL::text AS id,
                    NULL::text AS store_id,
                    NULL::text AS customer_id,
                    NULL::text AS channel,
                    NULL::text AS status,
                    0::numeric AS grand_total,
                    NULL::timestamp AS created_at,
                    CURRENT_DATE AS d
                WHERE false
            ) AS empty_orders
        ');
    }

    private function ordersUnionQuery(string $fromTs, string $toTs, array $filters)
    {
        $queries = [];

        if ($this->hasTable('store_orders') && in_array($filters['channel'], ['all', 'store'], true)) {
            $amount = $this->amountExpr('store_orders', 'o');

            $customerExpr = 'NULL::text';
            if ($this->hasColumn('store_orders', 'customer_id')) {
                $customerExpr = 'o.customer_id::text';
            } elseif ($this->hasColumn('store_orders', 'user_id')) {
                $customerExpr = 'o.user_id::text';
            }

            $queries[] = DB::table('store_orders as o')
                ->selectRaw("
                    o.id::text AS id,
                    o.store_id::text AS store_id,
                    {$customerExpr} AS customer_id,
                    'store'::text AS channel,
                    LOWER((o.status)::text) AS status,
                    {$amount} AS grand_total,
                    o.created_at AS created_at,
                    DATE(o.created_at) AS d
                ")
                ->whereBetween('o.created_at', [$fromTs, $toTs])
                ->when($filters['store_id'], fn ($q) => $q->where('o.store_id', $filters['store_id']));
        }

        if ($this->hasTable('ecommerce_orders') && in_array($filters['channel'], ['all', 'ecommerce'], true)) {
            $amount = $this->amountExpr('ecommerce_orders', 'o');

            $customerExpr = 'NULL::text';
            if ($this->hasColumn('ecommerce_orders', 'customer_id')) {
                $customerExpr = 'o.customer_id::text';
            } elseif ($this->hasColumn('ecommerce_orders', 'user_id')) {
                $customerExpr = 'o.user_id::text';
            }

            $queries[] = DB::table('ecommerce_orders as o')
                ->selectRaw("
                    o.id::text AS id,
                    NULL::text AS store_id,
                    {$customerExpr} AS customer_id,
                    'ecommerce'::text AS channel,
                    LOWER((o.status)::text) AS status,
                    {$amount} AS grand_total,
                    o.created_at AS created_at,
                    DATE(o.created_at) AS d
                ")
                ->whereBetween('o.created_at', [$fromTs, $toTs]);
        }

        if (! $queries) {
            return DB::query()->fromSub($this->emptyOrdersQuery(), 'x');
        }

        $union = array_shift($queries);

        foreach ($queries as $q) {
            $union->unionAll($q);
        }

        return DB::query()->fromSub($union, 'x');
    }

    private function gmvPerStore($ordersAll, array $paidStatuses)
    {
        if (! $this->hasTable('stores')) {
            return collect();
        }

        return DB::query()
            ->fromSub(clone $ordersAll, 'x')
            ->leftJoin('stores as s', DB::raw('s.id::text'), '=', 'x.store_id')
            ->where('x.channel', 'store')
            ->whereIn('x.status', $paidStatuses)
            ->selectRaw("
                x.store_id,
                COALESCE(s.name, 'Store Tidak Diketahui') AS store_name,
                COUNT(*)::int AS orders,
                COUNT(DISTINCT x.customer_id) FILTER (WHERE x.customer_id IS NOT NULL AND x.customer_id <> '')::int AS buyers,
                COALESCE(SUM(x.grand_total), 0)::numeric AS gmv
            ")
            ->groupBy('x.store_id', 's.name')
            ->orderByDesc('gmv')
            ->limit(15)
            ->get();
    }

    private function branchRevenueAchievement($ordersAll, array $paidStatuses, array $filters)
    {
        if (! $this->hasTable('stores')) {
            return collect();
        }

        $revenueByStore = DB::query()
            ->fromSub(clone $ordersAll, 'x')
            ->where('x.channel', 'store')
            ->whereIn('x.status', $paidStatuses)
            ->selectRaw("
                x.store_id,
                COUNT(*)::int AS orders,
                COUNT(DISTINCT x.customer_id) FILTER (
                    WHERE x.customer_id IS NOT NULL AND x.customer_id <> ''
                )::int AS buyers,
                COALESCE(SUM(x.grand_total), 0)::numeric AS revenue
            ")
            ->groupBy('x.store_id');

        $rows = DB::table('stores as s')
            ->leftJoinSub($revenueByStore, 'sales', DB::raw('sales.store_id'), '=', DB::raw('s.id::text'))
            ->when($this->hasColumn('stores', 'deleted_at'), fn ($q) => $q->whereNull('s.deleted_at'))
            ->when($filters['store_id'], fn ($q) => $q->where('s.id', $filters['store_id']))
            ->selectRaw('
                s.id::text AS store_id,
                s.name AS store_name,
                COALESCE(sales.orders, 0)::int AS orders,
                COALESCE(sales.buyers, 0)::int AS buyers,
                COALESCE(sales.revenue, 0)::numeric AS revenue
            ')
            ->orderByDesc('revenue')
            ->orderBy('s.name')
            ->get();

        $totalRevenue = (float) $rows->sum('revenue');
        $benchmark = (float) ($rows->max('revenue') ?? 0);

        return $rows->map(function ($row) use ($totalRevenue, $benchmark) {
            $revenue = (float) $row->revenue;
            $row->revenue = $revenue;
            $row->achievement_percent = $benchmark > 0
                ? round(($revenue / $benchmark) * 100, 1)
                : 0;
            $row->contribution_percent = $totalRevenue > 0
                ? round(($revenue / $totalRevenue) * 100, 1)
                : 0;
            $row->average_order_value = (int) $row->orders > 0
                ? round($revenue / (int) $row->orders, 2)
                : 0;

            return $row;
        });
    }

    private function emptyCustomerQuery()
    {
        return DB::query()->fromRaw('
            (
                SELECT
                    NULL::text AS id,
                    NULL::text AS name,
                    NULL::text AS email,
                    NULL::text AS wa_number,
                    NULL::text AS gender,
                    NULL::date AS date_of_birth,
                    NULL::text AS registration_channel,
                    NULL::text AS registration_store_id,
                    NULL::text AS registration_store_name,
                    NULL::text AS address,
                    NULL::text AS postal_code,
                    NULL::text AS province_code,
                    NULL::text AS regency_code,
                    NULL::text AS province_name,
                    NULL::text AS regency_name,
                    NULL::text AS district_name,
                    NULL::text AS village_name,
                    NULL::numeric AS village_latitude,
                    NULL::numeric AS village_longitude,
                    NULL::timestamp AS created_at
                WHERE false
            ) AS empty_customers
        ');
    }

    private function customerBaseQuery(
        array $filters,
        string $fromTs,
        string $toTs,
        bool $includeOtherChannels = false
    ) {
        if (! $this->hasTable('customers')) {
            return $this->emptyCustomerQuery();
        }

        return DB::table('customers as c')
            ->leftJoin('stores as rs', function ($join) {
                $join->whereRaw('rs.id::text = c.registration_source_id');
            })
            ->leftJoin('provinces as p', 'p.code', '=', 'c.province_code')
            ->leftJoin('regencies as r', 'r.code', '=', 'c.regency_code')
            ->leftJoin('districts as d', 'd.code', '=', 'c.district_code')
            ->leftJoin('villages as v', 'v.code', '=', 'c.village_code')
            ->selectRaw('
                c.id::text AS id,
                c.name,
                c.email,
                c.wa_number,
                c.gender,
                c.date_of_birth,
                c.address,
                c.postal_code,
                c.registration_channel,
                c.registration_source_id::text AS registration_store_id,
                rs.name AS registration_store_name,
                c.province_code::text AS province_code,
                c.regency_code::text AS regency_code,
                p.name AS province_name,
                r.name AS regency_name,
                d.name AS district_name,
                v.name AS village_name,
                v.latitude AS village_latitude,
                v.longitude AS village_longitude,
                c.created_at
            ')
            ->whereBetween('c.created_at', [$fromTs, $toTs])
            ->when($this->hasColumn('customers', 'deleted_at'), fn ($q) => $q->whereNull('c.deleted_at'))
            ->when(! $includeOtherChannels, fn ($q) => $q->whereIn('c.registration_channel', ['store', 'ecommerce']))
            ->when($filters['channel'] !== 'all', fn ($q) => $q->where('c.registration_channel', $filters['channel']))
            ->when($filters['store_id'], function ($q) use ($filters) {
                $q->where('c.registration_channel', 'store')
                    ->where('c.registration_source_id', (string) $filters['store_id']);
            });
    }

    private function customerGeographySummary($customerQuery): array
    {
        $provinceCentroids = [
            '11' => [4.70, 96.75], '12' => [2.12, 99.55], '13' => [-0.74, 100.80],
            '14' => [0.51, 101.45], '15' => [-1.61, 103.61], '16' => [-3.32, 104.91],
            '17' => [-3.79, 102.26], '18' => [-4.56, 105.41], '19' => [-2.74, 106.44],
            '21' => [3.95, 108.14], '31' => [-6.20, 106.85], '32' => [-6.91, 107.61],
            '33' => [-7.15, 110.14], '34' => [-7.80, 110.37], '35' => [-7.54, 112.24],
            '36' => [-6.41, 106.06], '51' => [-8.34, 115.09], '52' => [-8.65, 117.36],
            '53' => [-8.66, 121.08], '61' => [-0.28, 111.48], '62' => [-1.68, 113.38],
            '63' => [-3.09, 115.28], '64' => [0.54, 116.42], '65' => [3.07, 116.04],
            '71' => [0.62, 123.98], '72' => [-1.43, 121.45], '73' => [-3.67, 119.97],
            '74' => [-4.14, 122.17], '75' => [0.70, 122.45], '76' => [-2.84, 119.23],
            '81' => [-3.24, 130.15], '82' => [1.57, 127.81], '91' => [-4.27, 138.08],
            '92' => [0.78, 128.04], '93' => [-6.50, 139.20], '94' => [-3.80, 137.60],
            '95' => [-5.50, 140.40], '96' => [-2.60, 133.50],
        ];

        $provinces = DB::query()
            ->fromSub(clone $customerQuery, 'c')
            ->selectRaw("
                c.province_code,
                COALESCE(c.province_name, 'Lokasi Belum Diisi') AS province_name,
                COUNT(*)::int AS total,
                COUNT(*) FILTER (WHERE c.registration_channel = 'store')::int AS store_total,
                COUNT(*) FILTER (WHERE c.registration_channel = 'ecommerce')::int AS ecommerce_total
            ")
            ->groupBy('c.province_code', 'c.province_name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($provinceCentroids) {
                $centroid = $provinceCentroids[(string) $row->province_code] ?? null;
                $row->latitude = $centroid[0] ?? null;
                $row->longitude = $centroid[1] ?? null;

                return $row;
            });

        $regencies = DB::query()
            ->fromSub(clone $customerQuery, 'c')
            ->selectRaw("
                c.province_code,
                c.regency_code,
                COALESCE(c.province_name, 'Lokasi Belum Diisi') AS province_name,
                COALESCE(c.regency_name, 'Kab/Kota Belum Diisi') AS regency_name,
                COUNT(*)::int AS total,
                COUNT(*) FILTER (WHERE c.registration_channel = 'store')::int AS store_total,
                COUNT(*) FILTER (WHERE c.registration_channel = 'ecommerce')::int AS ecommerce_total
            ")
            ->groupBy('c.province_code', 'c.regency_code', 'c.province_name', 'c.regency_name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $customerRows = DB::query()
            ->fromSub(clone $customerQuery, 'c')
            ->select([
                'c.id',
                'c.name',
                'c.email',
                'c.wa_number',
                'c.gender',
                'c.date_of_birth',
                'c.address',
                'c.postal_code',
                'c.registration_channel',
                'c.province_code',
                'c.regency_code',
                'c.province_name',
                'c.regency_name',
                'c.district_name',
                'c.village_name',
                'c.village_latitude',
                'c.village_longitude',
                'c.registration_store_id',
                'c.registration_store_name',
            ])
            ->orderByDesc('c.created_at')
            ->limit(1500)
            ->get();

        $exactLocations = collect();
        if (
            $customerRows->isNotEmpty()
            && $this->hasTable('ecommerce_shipping_addresses')
            && $this->hasColumn('ecommerce_shipping_addresses', 'geom')
        ) {
            $exactLocations = DB::table('ecommerce_shipping_addresses')
                ->whereIn('customer_id', $customerRows->pluck('id')->all())
                ->whereNotNull('geom')
                ->selectRaw('
                    customer_id::text AS customer_id,
                    ST_Y(geom)::float8 AS latitude,
                    ST_X(geom)::float8 AS longitude,
                    COALESCE(detail, other_detail) AS address,
                    is_main,
                    created_at
                ')
                ->orderByDesc('is_main')
                ->orderByDesc('created_at')
                ->get()
                ->unique('customer_id')
                ->keyBy('customer_id');
        }

        $points = $customerRows
            ->map(function ($row) use ($exactLocations) {
                $exact = $exactLocations->get((string) $row->id);
                $centroid = $provinceCentroids[(string) $row->province_code] ?? null;

                if ($exact) {
                    $latitude = (float) $exact->latitude;
                    $longitude = (float) $exact->longitude;
                    $accuracy = 'exact';
                    $address = $exact->address;
                } elseif ($row->village_latitude && $row->village_longitude) {
                    // Sebarkan tipis secara deterministik di sekitar pusat kelurahan
                    // agar customer pada kelurahan yang sama tetap terlihat individual.
                    $seed = (int) sprintf('%u', crc32((string) $row->id));
                    $angle = deg2rad($seed % 360);
                    $distance = 0.0015 + (($seed % 100) / 100) * 0.004;
                    $latitude = (float) $row->village_latitude + (sin($angle) * $distance);
                    $longitude = (float) $row->village_longitude + (cos($angle) * $distance);
                    $accuracy = 'village';
                    $address = null;
                } else {
                    return null;
                }

                return [
                    'id' => (string) $row->id,
                    'name' => $row->name ?: 'Customer',
                    'email' => $row->email,
                    'wa_number' => $row->wa_number,
                    'gender' => $row->gender,
                    'date_of_birth' => $row->date_of_birth,
                    'address' => $row->address,
                    'postal_code' => $row->postal_code,
                    'channel' => $row->registration_channel ?: 'tidak diketahui',
                    'store_total' => $row->registration_channel === 'store' ? 1 : 0,
                    'ecommerce_total' => $row->registration_channel === 'ecommerce' ? 1 : 0,
                    'store_id' => $row->registration_store_id,
                    'store_name' => $row->registration_store_name ?: 'Tanpa Store',
                    'province_name' => $row->province_name ?: 'Provinsi belum diisi',
                    'regency_name' => $row->regency_name ?: 'Kab/Kota belum diisi',
                    'district_name' => $row->district_name ?: 'Kecamatan belum diisi',
                    'village_name' => $row->village_name ?: 'Kelurahan belum diisi',
                    'latitude' => round($latitude, 6),
                    'longitude' => round($longitude, 6),
                    'accuracy' => $accuracy,
                    'address' => $address,
                ];
            })
            ->filter()
            ->values();

        $exactPointCount = $points->where('accuracy', 'exact')->count();
        $regionalPointCount = $points->where('accuracy', 'village')->count();
        $total = (int) $provinces->sum('total');
        $located = (int) $provinces
            ->whereNotNull('province_code')
            ->sum('total');
        $topProvince = $provinces->firstWhere('province_code', '!=', null);
        $topRegency = $regencies->firstWhere('regency_code', '!=', null);
        $topThree = (int) $provinces->whereNotNull('province_code')->take(3)->sum('total');

        $insights = [];
        if ($topProvince) {
            $share = $total > 0 ? round(((int) $topProvince->total / $total) * 100, 1) : 0;
            $insights[] = "Provinsi {$topProvince->province_name} menjadi kantong customer terbesar dengan "
                .number_format((int) $topProvince->total, 0, ',', '.')." customer ({$share}% dari basis data terfilter).";
        }
        if ($topRegency) {
            $insights[] = "{$topRegency->regency_name}, {$topRegency->province_name} adalah kabupaten/kota teratas dengan "
                .number_format((int) $topRegency->total, 0, ',', '.').' customer.';
        }
        if ($total > 0 && $topThree > 0) {
            $concentration = round(($topThree / $total) * 100, 1);
            $insights[] = "Tiga provinsi teratas menyumbang {$concentration}% customer; gunakan angka ini untuk prioritas campaign dan ekspansi.";
        }
        if ($total > $located) {
            $missingRate = round((($total - $located) / $total) * 100, 1);
            $insights[] = number_format($total - $located, 0, ',', '.')." customer ({$missingRate}%) belum memiliki data provinsi yang lengkap.";
        }

        return [
            'total' => $total,
            'located' => $located,
            'coverage_rate' => $total > 0 ? round(($located / $total) * 100, 1) : 0,
            'provinces' => $provinces,
            'regencies' => $regencies,
            'top_province' => $topProvince,
            'top_regency' => $topRegency,
            'points' => $points,
            'exact_point_count' => $exactPointCount,
            'regional_point_count' => $regionalPointCount,
            'insights' => $insights,
        ];
    }

    private function storeMapSummary(string $fromTs, string $toTs): array
    {
        if (! $this->hasTable('stores')) {
            return [
                'total' => 0,
                'mapped' => 0,
                'unmapped' => 0,
                'points' => collect(),
            ];
        }

        $points = DB::table('stores as s')
            ->whereNull('s.deleted_at')
            ->selectRaw('
                s.id::text AS id,
                s.name,
                s.code,
                s.address,
                s.postal_code,
                s.phone,
                s.image_url,
                CASE WHEN s.geom IS NOT NULL THEN ST_Y(s.geom)::float8 END AS latitude,
                CASE WHEN s.geom IS NOT NULL THEN ST_X(s.geom)::float8 END AS longitude,
                (
                    SELECT COUNT(*)::int
                    FROM customers c
                    WHERE c.deleted_at IS NULL
                      AND c.registration_source_id = s.id::text
                ) AS customer_count,
                (
                    SELECT COUNT(*)::int
                    FROM store_items si
                    WHERE si.store_id = s.id
                ) AS item_count,
                (
                    SELECT COUNT(*)::int
                    FROM store_customer_visits scv
                    WHERE scv.store_id = s.id
                      AND scv.created_at BETWEEN ? AND ?
                ) AS visit_count
            ', [$fromTs, $toTs])
            ->orderBy('s.name')
            ->get();

        $mapped = $points
            ->filter(fn ($row) => $row->latitude !== null && $row->longitude !== null)
            ->count();

        return [
            'total' => $points->count(),
            'mapped' => $mapped,
            'unmapped' => $points->count() - $mapped,
            'points' => $points,
        ];
    }

    private function warehouseMapSummary(string $fromTs, string $toTs): array
    {
        if (! $this->hasTable('warehouses')) {
            return [
                'total' => 0,
                'mapped' => 0,
                'unmapped' => 0,
                'points' => collect(),
            ];
        }

        $points = DB::table('warehouses as w')
            ->whereNull('w.deleted_at')
            ->selectRaw('
                w.id::text AS id,
                w.name,
                w.code,
                w.address,
                w.postal_code,
                CASE WHEN w.geom IS NOT NULL THEN ST_Y(w.geom)::float8 END AS latitude,
                CASE WHEN w.geom IS NOT NULL THEN ST_X(w.geom)::float8 END AS longitude,
                (
                    SELECT COUNT(*)::int
                    FROM warehouse_items wi
                    WHERE wi.warehouse_id = w.id
                ) AS item_count,
                (
                    SELECT COUNT(*)::int
                    FROM warehouse_inbounds wi
                    WHERE wi.warehouse_id = w.id
                      AND wi.created_at BETWEEN ? AND ?
                ) AS inbound_count,
                (
                    SELECT COUNT(*)::int
                    FROM warehouse_outbounds wo
                    WHERE wo.warehouse_id = w.id
                      AND wo.created_at BETWEEN ? AND ?
                ) AS outbound_count
            ', [$fromTs, $toTs, $fromTs, $toTs])
            ->orderBy('w.name')
            ->get();

        $mapped = $points
            ->filter(fn ($row) => $row->latitude !== null && $row->longitude !== null)
            ->count();

        return [
            'total' => $points->count(),
            'mapped' => $mapped,
            'unmapped' => $points->count() - $mapped,
            'points' => $points,
        ];
    }

    private function countTableRows(string $table, ?string $dateColumn, string $fromTs, string $toTs, ?callable $extra = null): int
    {
        if (! $this->hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($dateColumn && $this->hasColumn($table, $dateColumn)) {
            $query->whereBetween($dateColumn, [$fromTs, $toTs]);
        }

        if ($extra) {
            $extra($query);
        }

        return (int) $query->count();
    }

    private function growthRate(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((((float) $current - (float) $previous) / abs((float) $previous)) * 100, 1);
    }

    private function statusCount(
        string $table,
        array $statuses,
        string $fromTs,
        string $toTs,
        ?callable $scope = null
    ): int {
        if (! $this->hasTable($table) || ! $this->hasColumn($table, 'status')) {
            return 0;
        }

        $query = DB::table($table)
            ->whereIn(DB::raw('LOWER((status)::text)'), $statuses);

        if ($this->hasColumn($table, 'created_at')) {
            $query->whereBetween('created_at', [$fromTs, $toTs]);
        }

        if ($scope) {
            $scope($query);
        }

        return (int) $query->count();
    }

    private function warehouseOperationalSummary(string $fromTs, string $toTs, array $filters): array
    {
        $warehouseScope = function (string $table) use ($filters) {
            return function ($query) use ($filters, $table) {
                if ($filters['warehouse_id'] && $this->hasColumn($table, 'warehouse_id')) {
                    $query->where('warehouse_id', $filters['warehouse_id']);
                }
            };
        };

        $inbound = $this->countTableRows(
            'warehouse_inbounds',
            'created_at',
            $fromTs,
            $toTs,
            $warehouseScope('warehouse_inbounds')
        );
        $outbound = $this->countTableRows(
            'warehouse_outbounds',
            'created_at',
            $fromTs,
            $toTs,
            $warehouseScope('warehouse_outbounds')
        );
        $pendingInbound = $this->statusCount(
            'warehouse_inbounds',
            ['draft', 'pending', 'requested', 'process', 'processing'],
            $fromTs,
            $toTs,
            $warehouseScope('warehouse_inbounds')
        );
        $pendingOutbound = $this->statusCount(
            'warehouse_outbounds',
            ['draft', 'pending', 'requested', 'process', 'processing', 'packing', 'staging'],
            $fromTs,
            $toTs,
            $warehouseScope('warehouse_outbounds')
        );

        $staging = $this->countTableRows(
            'warehouse_staging_outbounds',
            'created_at',
            $fromTs,
            $toTs,
            $warehouseScope('warehouse_staging_outbounds')
        );
        $qcIssues = 0;
        if ($this->hasTable('warehouse_qc')) {
            $qcIssues = $this->statusCount(
                'warehouse_qc',
                ['bad', 'rejected', 'reject', 'failed', 'damaged'],
                $fromTs,
                $toTs,
                $warehouseScope('warehouse_qc')
            );
        }

        return [
            'inbound' => $inbound,
            'outbound' => $outbound,
            'pending_inbound' => $pendingInbound,
            'pending_outbound' => $pendingOutbound,
            'staging' => $staging,
            'qc_issues' => $qcIssues,
            'backlog' => $pendingInbound + $pendingOutbound + $staging,
            'flow_ratio' => $inbound > 0 ? round(($outbound / $inbound) * 100, 1) : 0,
        ];
    }

    private function storeOperationalSummary(
        string $fromTs,
        string $toTs,
        array $filters,
        array $visitSummary
    ): array {
        $storeScope = function ($query) use ($filters) {
            if ($filters['store_id'] && $this->hasColumn('cashier_sessions', 'store_id')) {
                $query->where('store_id', $filters['store_id']);
            }
        };

        $orders = $this->countTableRows('store_orders', 'created_at', $fromTs, $toTs, function ($query) use ($filters) {
            if ($filters['store_id'] && $this->hasColumn('store_orders', 'store_id')) {
                $query->where('store_id', $filters['store_id']);
            }
        });
        $pendingOrders = $this->statusCount(
            'store_orders',
            ['draft', 'pending', 'requested', 'request', 'process', 'processing'],
            $fromTs,
            $toTs,
            function ($query) use ($filters) {
                if ($filters['store_id'] && $this->hasColumn('store_orders', 'store_id')) {
                    $query->where('store_id', $filters['store_id']);
                }
            }
        );
        $returns = $this->countTableRows('store_returns', 'created_at', $fromTs, $toTs, function ($query) use ($filters) {
            if ($filters['store_id'] && $this->hasColumn('store_returns', 'store_id')) {
                $query->where('store_id', $filters['store_id']);
            }
        });
        $openSessions = $this->statusCount(
            'cashier_sessions',
            ['open', 'opened', 'active', 'running'],
            $fromTs,
            $toTs,
            $storeScope
        );
        $paidTransactions = $this->countTableRows(
            'store_visit_payments',
            'created_at',
            $fromTs,
            $toTs,
            function ($query) use ($filters) {
                if ($filters['store_id'] && $this->hasColumn('store_visit_payments', 'store_id')) {
                    $query->where('store_id', $filters['store_id']);
                }
            }
        );
        $visits = (int) ($visitSummary['total'] ?? 0);

        return [
            'orders' => $orders,
            'pending_orders' => $pendingOrders,
            'returns' => $returns,
            'open_sessions' => $openSessions,
            'transactions' => $paidTransactions,
            'visits' => $visits,
            'visit_conversion' => $visits > 0 ? round(($paidTransactions / $visits) * 100, 1) : 0,
        ];
    }

    private function ecommerceOperationalSummary(string $fromTs, string $toTs): array
    {
        $total = $this->countTableRows('ecommerce_orders', 'created_at', $fromTs, $toTs);
        $pending = $this->statusCount(
            'ecommerce_orders',
            ['draft', 'pending', 'waiting_payment', 'unpaid', 'requested'],
            $fromTs,
            $toTs
        );
        $processing = $this->statusCount(
            'ecommerce_orders',
            ['paid', 'process', 'processed', 'processing', 'packing', 'ready_to_ship'],
            $fromTs,
            $toTs
        );
        $shipped = $this->statusCount(
            'ecommerce_orders',
            ['shipped', 'shipping', 'in_delivery'],
            $fromTs,
            $toTs
        );
        $completed = $this->statusCount(
            'ecommerce_orders',
            ['received', 'completed', 'complete', 'done', 'success'],
            $fromTs,
            $toTs
        );
        $cancelled = $this->statusCount(
            'ecommerce_orders',
            ['cancelled', 'canceled', 'failed', 'expired', 'rejected', 'refunded'],
            $fromTs,
            $toTs
        );

        return [
            'orders' => $total,
            'pending_orders' => $pending,
            'processing_orders' => $processing,
            'shipped_orders' => $shipped,
            'completed_orders' => $completed,
            'cancelled_orders' => $cancelled,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
        ];
    }

    private function buildActionItems(
        array $warehouse,
        array $store,
        array $ecommerce,
        array $opname,
        int $lowStockWarehouse,
        int $lowStockStore
    ): array {
        $items = [];
        $push = function (string $severity, string $area, string $title, string $detail, int $score) use (&$items) {
            $items[] = compact('severity', 'area', 'title', 'detail', 'score');
        };

        if ($warehouse['backlog'] > 0) {
            $push('warning', 'Warehouse', 'Backlog proses gudang', "{$warehouse['backlog']} dokumen inbound/outbound/staging masih perlu diselesaikan.", 80 + min($warehouse['backlog'], 19));
        }
        if ($warehouse['qc_issues'] > 0) {
            $push('danger', 'Warehouse', 'Temuan QC', "{$warehouse['qc_issues']} hasil QC bermasalah pada periode ini.", 95);
        }
        if ($lowStockWarehouse > 0) {
            $push('danger', 'Warehouse', 'Stok gudang rendah', "{$lowStockWarehouse} kombinasi produk-gudang menyentuh batas stok.", 92);
        }
        if ($store['pending_orders'] > 0) {
            $push('warning', 'Store', 'Permintaan cabang tertunda', "{$store['pending_orders']} order cabang belum selesai diproses.", 82);
        }
        if ($store['open_sessions'] > 0) {
            $push('info', 'Store', 'Sesi kasir aktif', "{$store['open_sessions']} sesi kasir masih terbuka pada periode terpilih.", 55);
        }
        if ($lowStockStore > 0) {
            $push('danger', 'Store', 'Stok cabang rendah', "{$lowStockStore} kombinasi produk-store membutuhkan replenishment.", 94);
        }
        if ($ecommerce['pending_orders'] > 0) {
            $push('warning', 'E-commerce', 'Order menunggu pembayaran', "{$ecommerce['pending_orders']} order masih pending atau belum dibayar.", 78);
        }
        if ($ecommerce['processing_orders'] > 0) {
            $push('info', 'E-commerce', 'Antrean fulfillment', "{$ecommerce['processing_orders']} order berstatus paid/processing/packing.", 70);
        }
        if ($ecommerce['cancellation_rate'] >= 5) {
            $push('danger', 'E-commerce', 'Pembatalan tinggi', "Cancellation rate {$ecommerce['cancellation_rate']}% perlu ditelusuri.", 96);
        }

        $opnameIssues = (int) ($opname['warehouse_issue'] ?? 0) + (int) ($opname['store_issue'] ?? 0);
        if ($opnameIssues > 0) {
            $push('danger', 'Inventory', 'Selisih stock opname', "{$opnameIssues} item berstatus missing/extra/mismatch.", 100);
        }

        usort($items, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($items, 0, 10);
    }

    private function wmsSummary(string $fromTs, string $toTs, array $filters): array
    {
        $sku = 0;
        $onHand = 0;
        $warehouseStockQty = 0;
        $storeStockQty = 0;

        if ($this->hasTable('items')) {
            $sku = (int) DB::table('items')->distinct('product_id')->count('product_id');

            if ($this->hasColumn('items', 'status')) {
                $warehouseStockQty = (int) DB::table('items')
                    ->whereIn(DB::raw('LOWER((status)::text)'), ['warehouse_stock'])
                    ->count();

                $storeStockQty = (int) DB::table('items')
                    ->whereIn(DB::raw('LOWER((status)::text)'), ['store_stock', 'display_store'])
                    ->count();

                $onHand = $warehouseStockQty + $storeStockQty;
            } else {
                $onHand = (int) DB::table('items')->count();
            }
        } elseif ($this->hasTable('inventory_stocks')) {
            $qtyCol = $this->hasColumn('inventory_stocks', 'qty_on_hand')
                ? 'qty_on_hand'
                : ($this->hasColumn('inventory_stocks', 'qty') ? 'qty' : null);

            if ($qtyCol) {
                $sku = (int) DB::table('inventory_stocks')->distinct('product_id')->count('product_id');
                $onHand = (int) DB::table('inventory_stocks')->sum($qtyCol);
            }
        }

        $inbound = 0;
        $outbound = 0;

        if ($this->hasTable('warehouse_inbounds')) {
            $inbound += $this->countTableRows('warehouse_inbounds', 'created_at', $fromTs, $toTs);
        } elseif ($this->hasTable('warehouse_receipts')) {
            $inbound += $this->countTableRows('warehouse_receipts', 'created_at', $fromTs, $toTs);
        }

        if ($this->hasTable('warehouse_outbounds')) {
            $outbound += $this->countTableRows('warehouse_outbounds', 'created_at', $fromTs, $toTs);
        } elseif ($this->hasTable('warehouse_shipments')) {
            $outbound += $this->countTableRows('warehouse_shipments', 'created_at', $fromTs, $toTs);
        }

        if ($this->hasTable('inventory_movements')) {
            $typeCol = $this->hasColumn('inventory_movements', 'type')
                ? 'type'
                : ($this->hasColumn('inventory_movements', 'movement_type') ? 'movement_type' : null);

            $createdCol = $this->hasColumn('inventory_movements', 'created_at')
                ? 'created_at'
                : ($this->hasColumn('inventory_movements', 'moved_at') ? 'moved_at' : null);

            if ($typeCol && $createdCol) {
                $inbound += (int) DB::table('inventory_movements')
                    ->whereBetween($createdCol, [$fromTs, $toTs])
                    ->whereIn(DB::raw("LOWER(($typeCol)::text)"), ['in', 'inbound', 'receive', 'receipt'])
                    ->count();

                $outbound += (int) DB::table('inventory_movements')
                    ->whereBetween($createdCol, [$fromTs, $toTs])
                    ->whereIn(DB::raw("LOWER(($typeCol)::text)"), ['out', 'outbound', 'issue', 'shipment', 'ship'])
                    ->count();
            }
        }

        $byWarehouse = collect();

        if ($this->hasTable('items') && $this->hasTable('warehouse_items') && $this->hasTable('warehouses')) {
            $byWarehouse = DB::table('items as i')
                ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
                ->leftJoin('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
                ->whereIn(DB::raw('LOWER((i.status)::text)'), ['warehouse_stock'])
                ->when($filters['warehouse_id'], fn ($q) => $q->where('wi.warehouse_id', $filters['warehouse_id']))
                ->selectRaw("
                    wi.warehouse_id,
                    COALESCE(w.name, 'Warehouse Tidak Diketahui') AS warehouse_name,
                    COUNT(*)::int AS qty
                ")
                ->groupBy('wi.warehouse_id', 'w.name')
                ->orderByDesc('qty')
                ->limit(12)
                ->get();
        }

        return [
            'sku' => $sku,
            'on_hand' => $onHand,
            'warehouse_stock_qty' => $warehouseStockQty,
            'store_stock_qty' => $storeStockQty,
            'inbound' => $inbound,
            'outbound' => $outbound,
            'by_wh' => $byWarehouse,
        ];
    }

    private function stockOpnameSummary(string $fromTs, string $toTs, array $filters): array
    {
        $warehouseTotal = $this->countTableRows('warehouse_stock_opnames', 'created_at', $fromTs, $toTs, function ($q) use ($filters) {
            if ($filters['warehouse_id'] && $this->hasColumn('warehouse_stock_opnames', 'warehouse_id')) {
                $q->where('warehouse_id', $filters['warehouse_id']);
            }
        });

        $storeTotal = $this->countTableRows('store_stock_opnames', 'created_at', $fromTs, $toTs, function ($q) use ($filters) {
            if ($filters['store_id'] && $this->hasColumn('store_stock_opnames', 'store_id')) {
                $q->where('store_id', $filters['store_id']);
            }
        });

        $warehouseIssue = 0;
        if ($this->hasTable('warehouse_stock_opname_details')) {
            $warehouseIssue = (int) DB::table('warehouse_stock_opname_details as d')
                ->join('warehouse_stock_opnames as o', 'o.id', '=', 'd.warehouse_stock_opname_id')
                ->whereBetween('o.created_at', [$fromTs, $toTs])
                ->whereIn(DB::raw('LOWER((d.status)::text)'), ['missing', 'extra', 'mismatch'])
                ->when($filters['warehouse_id'], fn ($q) => $q->where('o.warehouse_id', $filters['warehouse_id']))
                ->count();
        }

        $storeIssue = 0;
        if ($this->hasTable('store_stock_opname_details')) {
            $storeIssue = (int) DB::table('store_stock_opname_details as d')
                ->join('store_stock_opnames as o', 'o.id', '=', 'd.store_stock_opname_id')
                ->whereBetween('o.created_at', [$fromTs, $toTs])
                ->whereIn(DB::raw('LOWER((d.status)::text)'), ['missing', 'extra', 'mismatch'])
                ->when($filters['store_id'], fn ($q) => $q->where('o.store_id', $filters['store_id']))
                ->count();
        }

        return [
            'warehouse_total' => $warehouseTotal,
            'store_total' => $storeTotal,
            'warehouse_issue' => $warehouseIssue,
            'store_issue' => $storeIssue,
        ];
    }

    private function lowStockWarehouse(array $filters)
    {
        if (! $this->hasTable('items') || ! $this->hasTable('warehouse_items') || ! $this->hasTable('warehouses')) {
            return collect();
        }

        $stockQuery = DB::table('items as i')
            ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
            ->whereIn(DB::raw('LOWER((i.status)::text)'), ['warehouse_stock'])
            ->selectRaw('wi.warehouse_id, i.product_id, COUNT(*)::int AS qty')
            ->groupBy('wi.warehouse_id', 'i.product_id');

        if ($this->hasTable('warehouse_stock_barriers') && DB::table('warehouse_stock_barriers')->exists()) {
            return DB::table('warehouse_stock_barriers as b')
                ->leftJoinSub($stockQuery, 'stock', function ($join) {
                    $join->on('stock.product_id', '=', 'b.product_id')
                        ->on('stock.warehouse_id', '=', 'b.warehouse_id');
                })
                ->leftJoin('warehouses as w', 'w.id', '=', 'b.warehouse_id')
                ->leftJoin('products as p', 'p.id', '=', 'b.product_id')
                ->when($filters['warehouse_id'], fn ($q) => $q->where('b.warehouse_id', $filters['warehouse_id']))
                ->whereRaw('COALESCE(stock.qty, 0) <= CEIL(b.stock_barrier * (1 + COALESCE(b.tolerance_percentage, 0) / 100.0))')
                ->selectRaw("
                b.warehouse_id,
                COALESCE(w.name, 'Warehouse Tidak Diketahui') AS warehouse_name,
                b.product_id,
                COALESCE(p.name, b.product_id::text) AS product_name,
                COALESCE(stock.qty, 0)::int AS qty,
                b.stock_barrier::int AS stock_barrier,
                COALESCE(b.tolerance_percentage, 0)::numeric AS tolerance_percentage,
                TRUE AS barrier_configured
            ")
                ->orderBy('qty')
                ->limit(100)
                ->get();
        }

        return DB::query()
            ->fromSub($stockQuery, 'stock')
            ->leftJoin('warehouses as w', 'w.id', '=', 'stock.warehouse_id')
            ->leftJoin('products as p', 'p.id', '=', 'stock.product_id')
            ->when($filters['warehouse_id'], fn ($q) => $q->where('stock.warehouse_id', $filters['warehouse_id']))
            ->selectRaw("
                stock.warehouse_id,
                COALESCE(w.name, 'Warehouse Tidak Diketahui') AS warehouse_name,
                stock.product_id,
                COALESCE(p.name, stock.product_id::text) AS product_name,
                stock.qty::int AS qty,
                NULL::int AS stock_barrier,
                0::numeric AS tolerance_percentage,
                FALSE AS barrier_configured
            ")
            ->orderBy('qty')
            ->limit(50)
            ->get();
    }

    private function lowStockStore(array $filters)
    {
        if (! $this->hasTable('items') || ! $this->hasTable('store_items') || ! $this->hasTable('stores')) {
            return collect();
        }

        $barrierTable = null;
        foreach (['store_stock_barriers', 'branch_stock_barriers', 'store_barrier_stocks'] as $candidate) {
            if ($this->hasTable($candidate)) {
                $barrierTable = $candidate;
                break;
            }
        }

        $stockQuery = DB::table('items as i')
            ->join('store_items as si', 'si.item_id', '=', 'i.id')
            ->selectRaw("
                si.store_id,
                i.product_id,
                COUNT(*) FILTER (
                    WHERE LOWER((i.status)::text) IN ('store_stock', 'store_display', 'display_store')
                )::int AS qty
            ")
            ->groupBy('si.store_id', 'i.product_id');

        if ($barrierTable && DB::table($barrierTable)->exists()) {
            return DB::table($barrierTable.' as b')
                ->leftJoinSub($stockQuery, 'stock', function ($join) {
                    $join->on('stock.product_id', '=', 'b.product_id')
                        ->on('stock.store_id', '=', 'b.store_id');
                })
                ->leftJoin('stores as s', 's.id', '=', 'b.store_id')
                ->leftJoin('products as p', 'p.id', '=', 'b.product_id')
                ->when($filters['store_id'], fn ($q) => $q->where('b.store_id', $filters['store_id']))
                ->whereRaw('COALESCE(stock.qty, 0) <= CEIL(b.stock_barrier * (1 + COALESCE(b.tolerance_percentage, 0) / 100.0))')
                ->selectRaw("
                b.store_id,
                COALESCE(s.name, 'Store Tidak Diketahui') AS store_name,
                b.product_id,
                COALESCE(p.name, b.product_id::text) AS product_name,
                COALESCE(stock.qty, 0)::int AS qty,
                b.stock_barrier::int AS stock_barrier,
                COALESCE(b.tolerance_percentage, 0)::numeric AS tolerance_percentage,
                TRUE AS barrier_configured
            ")
                ->orderBy('qty')
                ->limit(100)
                ->get();
        }

        return DB::query()
            ->fromSub($stockQuery, 'stock')
            ->leftJoin('stores as s', 's.id', '=', 'stock.store_id')
            ->leftJoin('products as p', 'p.id', '=', 'stock.product_id')
            ->when($filters['store_id'], fn ($q) => $q->where('stock.store_id', $filters['store_id']))
            ->selectRaw("
                stock.store_id,
                COALESCE(s.name, 'Store Tidak Diketahui') AS store_name,
                stock.product_id,
                COALESCE(p.name, stock.product_id::text) AS product_name,
                stock.qty::int AS qty,
                NULL::int AS stock_barrier,
                0::numeric AS tolerance_percentage,
                FALSE AS barrier_configured
            ")
            ->orderBy('qty')
            ->limit(50)
            ->get();
    }

    private function visitSummary(string $fromTs, string $toTs, array $filters): array
    {
        $total = 0;
        $uniqueCustomer = 0;

        if ($this->hasTable('store_customer_visits')) {
            $query = DB::table('store_customer_visits')
                ->whereBetween('check_in_at', [$fromTs, $toTs])
                ->when($filters['store_id'], fn ($q) => $q->where('store_id', $filters['store_id']));

            $total = (int) (clone $query)->count();
            $uniqueCustomer = (int) (clone $query)->distinct('customer_id')->count('customer_id');
        }

        return [
            'total' => $total,
            'unique_customer' => $uniqueCustomer,
        ];
    }

    private function visitsByStore(string $fromTs, string $toTs, array $filters)
    {
        if (! $this->hasTable('store_customer_visits') || ! $this->hasTable('stores')) {
            return collect();
        }

        return DB::table('store_customer_visits as v')
            ->leftJoin('stores as s', 's.id', '=', 'v.store_id')
            ->whereBetween('v.check_in_at', [$fromTs, $toTs])
            ->when($filters['store_id'], fn ($q) => $q->where('v.store_id', $filters['store_id']))
            ->selectRaw("
                v.store_id,
                COALESCE(s.name, 'Store Tidak Diketahui') AS store_name,
                COUNT(*)::int AS visits,
                COUNT(DISTINCT v.customer_id)::int AS unique_customer
            ")
            ->groupBy('v.store_id', 's.name')
            ->orderByDesc('visits')
            ->limit(12)
            ->get();
    }

    private function buildInsights(array $data): array
    {
        $insights = [];

        if ($data['order_count'] > 0) {
            $growthMessage = $data['gmv_growth'] === null
                ? ' Periode sebelumnya belum memiliki GMV sebagai pembanding.'
                : ' Pertumbuhan GMV '.($data['gmv_growth'] >= 0 ? 'naik ' : 'turun ')
                    .number_format(abs($data['gmv_growth']), 1, ',', '.').'% dibanding periode sebelumnya.';

            $insights[] = [
                'type' => 'success',
                'title' => 'Penjualan periode ini',
                'message' => 'Periode ini menghasilkan '.number_format($data['order_count'], 0, ',', '.').' order dengan GMV Rp'.number_format($data['gmv'], 0, ',', '.').'. AOV berada di Rp'.number_format($data['aov'], 0, ',', '.').'.'.$growthMessage,
                'action' => $data['gmv_growth'] !== null && $data['gmv_growth'] < 0
                    ? 'Tinjau channel dan cabang yang mengalami penurunan, lalu bandingkan produk terlarisnya.'
                    : 'Pertahankan produk dan channel yang memberi kontribusi terbesar.',
            ];
        } else {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Belum ada penjualan',
                'message' => 'Belum ada order paid/success pada periode terpilih. Periksa filter tanggal, channel, atau status order.',
                'action' => 'Pastikan filter sudah benar, lalu periksa order yang masih pending dan status pembayarannya.',
            ];
        }

        if ($data['best_store']) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Cabang terbaik',
                'message' => 'Cabang dengan GMV tertinggi adalah '.($data['best_store']->store_name ?? 'Store Tidak Diketahui').' dengan GMV Rp'.number_format((float) $data['best_store']->gmv, 0, ',', '.').'.',
                'action' => 'Gunakan pola penjualan cabang ini sebagai acuan untuk cabang lain.',
            ];
        }

        $insights[] = [
            'type' => 'primary',
            'title' => 'Member baru hari ini',
            'message' => 'Hari ini ada '.number_format($data['new_member_today'], 0, ',', '.').' member baru. Store: '.number_format($data['new_member_store_today'], 0, ',', '.').', Ecommerce: '.number_format($data['new_member_ecommerce_today'], 0, ',', '.').'.',
            'action' => 'Pastikan data kontak dan wilayah member lengkap agar dapat digunakan untuk campaign.',
        ];

        if ($data['low_stock_warehouse'] + $data['low_stock_store'] > 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Perhatian stok',
                'message' => 'Terdapat '.number_format($data['low_stock_warehouse'], 0, ',', '.').' item warehouse dan '.number_format($data['low_stock_store'], 0, ',', '.').' item store yang berada pada kondisi stok rendah / paling rendah.',
                'action' => 'Prioritaskan replenishment untuk barang dengan penjualan tertinggi dan stok di bawah batas.',
            ];
        }

        if ($data['opname_issue'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Selisih stock opname',
                'message' => 'Ditemukan '.number_format($data['opname_issue'], 0, ',', '.').' detail stock opname dengan status missing/extra/mismatch pada periode ini.',
                'action' => 'Cocokkan RFID dan histori perpindahan item sebelum melakukan penyesuaian stok.',
            ];
        } else {
            $insights[] = [
                'type' => 'success',
                'title' => 'Stock opname aman',
                'message' => 'Tidak ada issue missing/extra/mismatch yang terdeteksi dari data stock opname pada periode ini.',
                'action' => 'Lanjutkan jadwal opname dan pemantauan rutin.',
            ];
        }

        if ($data['warehouse_backlog'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Beban operasional warehouse',
                'message' => number_format($data['warehouse_backlog'], 0, ',', '.').' proses inbound, outbound, atau staging masih berada dalam antrean.',
                'action' => 'Selesaikan antrean paling lama dan order dengan jadwal kirim terdekat terlebih dahulu.',
            ];
        }

        if ($data['ecommerce_cancelled'] > 0 || $data['ecommerce_pending'] > 0) {
            $insights[] = [
                'type' => $data['ecommerce_cancelled'] > 0 ? 'danger' : 'warning',
                'title' => 'Perhatian e-commerce',
                'message' => number_format($data['ecommerce_pending'], 0, ',', '.').' order menunggu pembayaran dan '
                    .number_format($data['ecommerce_cancelled'], 0, ',', '.').' order batal/gagal pada periode ini.',
                'action' => 'Tindak lanjuti pembayaran pending dan periksa penyebab pembatalan yang berulang.',
            ];
        }

        $totalLocations = $data['store_total'] + $data['warehouse_total'];
        $unmappedLocations = $data['store_unmapped'] + $data['warehouse_unmapped'];
        $locationCoverage = $totalLocations > 0
            ? round((($totalLocations - $unmappedLocations) / $totalLocations) * 100, 1)
            : 0;

        $insights[] = [
            'type' => $unmappedLocations > 0 ? 'warning' : 'success',
            'title' => 'Cakupan lokasi operasional',
            'message' => number_format($totalLocations - $unmappedLocations, 0, ',', '.')
                .' dari '.number_format($totalLocations, 0, ',', '.')
                ." lokasi Store/Warehouse sudah terpetakan ({$locationCoverage}%).",
            'impact' => $unmappedLocations > 0
                ? 'Lokasi tanpa koordinat tidak terlihat pada peta dan menyulitkan analisis jangkauan operasional.'
                : 'Seluruh lokasi dapat dibandingkan secara geografis pada dashboard.',
            'action' => $unmappedLocations > 0
                ? 'Lengkapi latitude dan longitude pada Master Store atau Master Warehouse yang belum dipetakan.'
                : 'Pertahankan validasi koordinat ketika lokasi baru dibuat.',
        ];

        if ($data['inactive_stores'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Store tanpa aktivitas terdeteksi',
                'message' => number_format($data['inactive_stores'], 0, ',', '.')
                    .' store tidak memiliki stok item maupun kunjungan pada periode aktif.',
                'impact' => 'Cabang tersebut berpotensi belum beroperasi, belum sinkron, atau tidak mencatat aktivitas dengan benar.',
                'action' => 'Periksa status operasional cabang, sinkronisasi POS, dan ketersediaan stoknya.',
            ];
        }

        if ($data['warehouse_stock_total'] > 0 && $data['warehouse_top_stock'] > 0) {
            $warehouseConcentration = round(
                ($data['warehouse_top_stock'] / $data['warehouse_stock_total']) * 100,
                1
            );

            $insights[] = [
                'type' => $warehouseConcentration >= 80 ? 'warning' : 'info',
                'title' => 'Konsentrasi stok warehouse',
                'message' => "Warehouse dengan stok terbesar menampung {$warehouseConcentration}% dari seluruh item warehouse.",
                'impact' => $warehouseConcentration >= 80
                    ? 'Stok sangat terpusat sehingga distribusi ke cabang lain dapat bergantung pada satu warehouse.'
                    : 'Distribusi stok antar-warehouse relatif tidak terlalu terpusat.',
                'action' => $warehouseConcentration >= 80
                    ? 'Evaluasi kebutuhan redistribusi berdasarkan permintaan dan kecepatan penjualan tiap wilayah.'
                    : 'Pantau perubahan konsentrasi bersamaan dengan kebutuhan replenishment store.',
            ];
        }

        if ($data['customer_location_coverage'] < 80) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Alamat customer belum lengkap',
                'message' => 'Cakupan customer yang memiliki data provinsi baru '
                    .number_format($data['customer_location_coverage'], 1, ',', '.').'%.',
                'impact' => 'Analisis wilayah customer dan penargetan campaign belum mewakili seluruh basis customer.',
                'action' => 'Wajibkan Provinsi sampai Kelurahan pada proses tambah dan edit customer, lalu lengkapi data lama.',
            ];
        }

        return $insights;
    }
}
