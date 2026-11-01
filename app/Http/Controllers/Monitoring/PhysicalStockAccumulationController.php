<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class PhysicalStockAccumulationController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'all');

        if (!in_array($mode, ['all', 'store', 'warehouse'], true)) {
            $mode = 'all';
        }

        $filters = $this->filters($request, $mode);

        $rows = $this->buildRowsQuery($filters)
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = DB::query()
            ->fromSub($this->stockUnionQuery($filters), 'x')
            ->selectRaw("
                COALESCE(SUM(x.total_qty), 0)::int AS total_qty,

                COALESCE(SUM(CASE WHEN x.location_type = 'store' THEN x.total_qty ELSE 0 END), 0)::int AS total_store,
                COALESCE(SUM(CASE WHEN x.location_type = 'warehouse' THEN x.total_qty ELSE 0 END), 0)::int AS total_warehouse,

                COALESCE(SUM(x.qty_store_stock), 0)::int AS total_store_stock,
                COALESCE(SUM(x.qty_store_display), 0)::int AS total_store_display,
                COALESCE(SUM(x.qty_warehouse_stock), 0)::int AS total_warehouse_stock,

                COALESCE(SUM(x.qty_good), 0)::int AS total_good,
                COALESCE(SUM(x.qty_good_after_repair), 0)::int AS total_good_after_repair,
                COALESCE(SUM(x.qty_bad), 0)::int AS total_bad,

                COALESCE(SUM(x.total_nominal), 0)::numeric(18, 2) AS total_nominal
            ")
            ->first();

        $chartByLocation = DB::query()
            ->fromSub($this->stockUnionQuery($filters), 'x')
            ->selectRaw("
                x.location_type,
                x.location_name,
                COALESCE(SUM(x.total_qty), 0)::int AS total_qty,
                COALESCE(SUM(x.total_nominal), 0)::numeric(18, 2) AS total_nominal
            ")
            ->groupBy('x.location_type', 'x.location_name')
            ->orderByDesc('total_qty')
            ->limit(12)
            ->get();

        $chartByProduct = DB::query()
            ->fromSub($this->stockUnionQuery($filters), 'x')
            ->selectRaw("
                x.product_code,
                x.product_name,
                COALESCE(SUM(x.total_qty), 0)::int AS total_qty,
                COALESCE(SUM(x.total_nominal), 0)::numeric(18, 2) AS total_nominal
            ")
            ->groupBy('x.product_code', 'x.product_name')
            ->orderByDesc('total_qty')
            ->limit(12)
            ->get();

        $locations = $this->locationOptions($mode);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Physical Stocks',
            'description' => 'Laporan monitoring.',
            'data' => [
            'rows'            => $rows,
            'filters'         => $filters,
            'summary'         => $summary,
            'locations'       => $locations,
            'chartByLocation' => $chartByLocation,
            'chartByProduct'  => $chartByProduct,
            'mode'            => $mode,
            'lastUpdate'      => now()->format('d-m-Y H:i:s'),
        ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function export(Request $request)
    {
        $mode = $request->query('mode', 'all');

        if (!in_array($mode, ['all', 'store', 'warehouse'], true)) {
            $mode = 'all';
        }

        $filters = $this->filters($request, $mode);

        $rows = $this->buildRowsQuery($filters)
            ->get()
            ->values();

        $modeName = match ($mode) {
            'store' => 'store',
            'warehouse' => 'warehouse',
            default => 'all-stok',
        };

        $filename = 'akumulasi-stok-fisik-' . $modeName . '-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new class($rows) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize {
                public function __construct(private Collection $rows)
                {
                }

                public function collection()
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'No',
                        'Tipe',
                        'Lokasi',
                        'Kode Lokasi',
                        'Kode Produk',
                        'Nama Produk',
                        'Satuan',
                        'Price Type',
                        'Harga Produk',
                        'Store Stock',
                        'Store Display',
                        'Warehouse Stock',
                        'Good',
                        'Good After Repair',
                        'Bad',
                        'Total Qty',
                        'Total Nominal',
                    ];
                }

                public function map($row): array
                {
                    static $no = 0;
                    $no++;

                    return [
                        $no,
                        ucfirst((string) $row->location_type),
                        $row->location_name,
                        $row->location_code,
                        $row->product_code,
                        $row->product_name,
                        $row->unit_name ?: '-',
                        $row->price_type ?: '-',
                        (float) $row->product_price,
                        (int) $row->qty_store_stock,
                        (int) $row->qty_store_display,
                        (int) $row->qty_warehouse_stock,
                        (int) $row->qty_good,
                        (int) $row->qty_good_after_repair,
                        (int) $row->qty_bad,
                        (int) $row->total_qty,
                        (float) $row->total_nominal,
                    ];
                }
            },
            $filename
        );
    }

    private function filters(Request $request, string $mode): array
    {
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'mode'         => $mode,
            'q'            => trim((string) $request->query('q', '')),
            'location_key' => trim((string) $request->query('location_key', '')),
            'min'          => (int) $request->query('min', 0),
            'per_page'     => $perPage,
        ];
    }

    private function buildRowsQuery(array $filters)
    {
        return DB::query()
            ->fromSub($this->stockUnionQuery($filters), 'x')
            ->selectRaw("
                x.location_type,
                x.location_id,
                x.location_name,
                x.location_code,
                x.product_id,
                x.product_code,
                x.product_name,
                x.unit_name,
                x.price_type,

                COALESCE(MAX(x.product_price), 0)::numeric(18, 2) AS product_price,

                SUM(x.qty_store_stock)::int AS qty_store_stock,
                SUM(x.qty_store_display)::int AS qty_store_display,
                SUM(x.qty_warehouse_stock)::int AS qty_warehouse_stock,

                SUM(x.qty_good)::int AS qty_good,
                SUM(x.qty_good_after_repair)::int AS qty_good_after_repair,
                SUM(x.qty_bad)::int AS qty_bad,

                SUM(x.total_qty)::int AS total_qty,
                COALESCE(SUM(x.total_nominal), 0)::numeric(18, 2) AS total_nominal
            ")
            ->groupBy(
                'x.location_type',
                'x.location_id',
                'x.location_name',
                'x.location_code',
                'x.product_id',
                'x.product_code',
                'x.product_name',
                'x.unit_name',
                'x.price_type'
            )
            ->when(($filters['min'] ?? 0) > 0, function ($query) use ($filters) {
                $query->havingRaw('SUM(x.total_qty) >= ?', [(int) $filters['min']]);
            })
            ->orderBy('x.location_type')
            ->orderBy('x.location_name')
            ->orderByDesc('total_qty')
            ->orderBy('x.product_name');
    }

    private function stockUnionQuery(array $filters)
    {
        $mode = $filters['mode'] ?? 'all';

        $queries = [];

        if ($mode === 'all' || $mode === 'store') {
            $queries[] = $this->storeStockQuery($filters);
        }

        if ($mode === 'all' || $mode === 'warehouse') {
            $queries[] = $this->warehouseStockQuery($filters);
        }

        if (empty($queries)) {
            return $this->emptyStockQuery();
        }

        $base = array_shift($queries);

        foreach ($queries as $query) {
            $base->unionAll($query);
        }

        return $base;
    }

    private function storeStockQuery(array $filters)
    {
        $q = $filters['q'] ?? '';
        $locationKey = $filters['location_key'] ?? '';

        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');
        $storeHasCode = Schema::hasColumn('stores', 'code');

        $query = DB::table('items as i')
            ->join('store_items as si', 'si.item_id', '=', 'i.id')
            ->join('stores as l', 'l.id', '=', 'si.store_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('store_qc as sq', 'sq.item_id', '=', 'i.id')
            ->leftJoin('item_conditions as c', 'c.id', '=', 'sq.item_condition_id')
            ->leftJoinSub($this->productPriceQuery('store'), 'pp', function ($join) {
                $join->on('pp.product_id', '=', 'p.id');
            })
            ->when($hasUnits, fn ($query) => $query->leftJoin('units as u', 'u.id', '=', 'p.unit_id'))
            ->when(
                Schema::hasColumn('stores', 'deleted_at'),
                fn ($query) => $query->whereNull('l.deleted_at')
            )
            ->whereIn('i.status', ['store_stock', 'store_display'])
            ->when($locationKey !== '' && str_starts_with($locationKey, 'store:'), function ($query) use ($locationKey) {
                $storeId = substr($locationKey, strlen('store:'));
                $query->where('si.store_id', $storeId);
            })
            ->when($q !== '', function ($query) use ($q, $storeHasCode) {
                $like = '%' . strtolower($q) . '%';

                $query->where(function ($sub) use ($like, $storeHasCode) {
                    $sub->whereRaw("LOWER(COALESCE(l.name, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(p.code, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(p.name, '')) LIKE ?", [$like]);

                    if ($storeHasCode) {
                        $sub->orWhereRaw("LOWER(COALESCE(l.code, '')) LIKE ?", [$like]);
                    }
                });
            })
            ->selectRaw("
                'store' AS location_type,
                si.store_id::text AS location_id,
                l.name AS location_name
            ")
            ->when(
                $storeHasCode,
                fn ($query) => $query->selectRaw("COALESCE(l.code, '') AS location_code"),
                fn ($query) => $query->selectRaw("'' AS location_code")
            )
            ->selectRaw("
                p.id::text AS product_id,
                p.code AS product_code,
                p.name AS product_name
            ")
            ->when(
                $hasUnits,
                fn ($query) => $query->selectRaw("COALESCE(u.name, '') AS unit_name"),
                fn ($query) => $query->selectRaw("'' AS unit_name")
            )
            ->selectRaw("
                COALESCE(pp.price_type, 'store') AS price_type,
                COALESCE(pp.price, 0)::numeric(18, 2) AS product_price,

                COUNT(DISTINCT CASE WHEN i.status = 'store_stock' THEN i.id END)::int AS qty_store_stock,
                COUNT(DISTINCT CASE WHEN i.status = 'store_display' THEN i.id END)::int AS qty_store_display,
                0::int AS qty_warehouse_stock,

                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(c.status::text, '')) = 'good' THEN i.id END)::int AS qty_good,
                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(c.status::text, '')) = 'good_after_repair' THEN i.id END)::int AS qty_good_after_repair,
                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(c.status::text, '')) = 'bad' THEN i.id END)::int AS qty_bad,

                COUNT(DISTINCT i.id)::int AS total_qty,
                (COALESCE(pp.price, 0) * COUNT(DISTINCT i.id))::numeric(18, 2) AS total_nominal
            ")
            ->groupBy(
                'si.store_id',
                'l.name',
                'p.id',
                'p.code',
                'p.name',
                'pp.price_type',
                'pp.price',
                ...($storeHasCode ? ['l.code'] : []),
                ...($hasUnits ? ['u.name'] : [])
            );

        return $query;
    }

    private function warehouseStockQuery(array $filters)
    {
        $q = $filters['q'] ?? '';
        $locationKey = $filters['location_key'] ?? '';

        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');
        $warehouseHasCode = Schema::hasColumn('warehouses', 'code');
        $conditionExpr = $this->resolveItemConditionExpression('i');

        if (
            Schema::hasTable('warehouse_items') &&
            Schema::hasColumn('warehouse_items', 'item_id') &&
            Schema::hasColumn('warehouse_items', 'warehouse_id')
        ) {
            $query = DB::table('items as i')
                ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
                ->join('warehouses as l', 'l.id', '=', 'wi.warehouse_id');
        } elseif (Schema::hasColumn('items', 'warehouse_id')) {
            $query = DB::table('items as i')
                ->join('warehouses as l', 'l.id', '=', 'i.warehouse_id')
                ->whereNotNull('i.warehouse_id');
        } else {
            return $this->emptyStockQuery();
        }

        $query
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoinSub($this->productPriceQuery('base'), 'pp', function ($join) {
                $join->on('pp.product_id', '=', 'p.id');
            })
            ->when($hasUnits, fn ($query) => $query->leftJoin('units as u', 'u.id', '=', 'p.unit_id'))
            ->when(
                Schema::hasColumn('warehouses', 'deleted_at'),
                fn ($query) => $query->whereNull('l.deleted_at')
            )
            ->when($locationKey !== '' && str_starts_with($locationKey, 'warehouse:'), function ($query) use ($locationKey) {
                $warehouseId = substr($locationKey, strlen('warehouse:'));
                $query->where('l.id', $warehouseId);
            })
            ->when($q !== '', function ($query) use ($q, $warehouseHasCode) {
                $like = '%' . strtolower($q) . '%';

                $query->where(function ($sub) use ($like, $warehouseHasCode) {
                    $sub->whereRaw("LOWER(COALESCE(l.name, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(p.code, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(p.name, '')) LIKE ?", [$like]);

                    if ($warehouseHasCode) {
                        $sub->orWhereRaw("LOWER(COALESCE(l.code, '')) LIKE ?", [$like]);
                    }
                });
            })
            ->selectRaw("
                'warehouse' AS location_type,
                l.id::text AS location_id,
                l.name AS location_name
            ")
            ->when(
                $warehouseHasCode,
                fn ($query) => $query->selectRaw("COALESCE(l.code, '') AS location_code"),
                fn ($query) => $query->selectRaw("'' AS location_code")
            )
            ->selectRaw("
                p.id::text AS product_id,
                p.code AS product_code,
                p.name AS product_name
            ")
            ->when(
                $hasUnits,
                fn ($query) => $query->selectRaw("COALESCE(u.name, '') AS unit_name"),
                fn ($query) => $query->selectRaw("'' AS unit_name")
            )
            ->selectRaw("
                COALESCE(pp.price_type, 'base') AS price_type,
                COALESCE(pp.price, 0)::numeric(18, 2) AS product_price,

                0::int AS qty_store_stock,
                0::int AS qty_store_display,
                COUNT(DISTINCT i.id)::int AS qty_warehouse_stock,

                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(($conditionExpr)::text, '')) = 'good' THEN i.id END)::int AS qty_good,
                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(($conditionExpr)::text, '')) = 'good_after_repair' THEN i.id END)::int AS qty_good_after_repair,
                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(($conditionExpr)::text, '')) = 'bad' THEN i.id END)::int AS qty_bad,

                COUNT(DISTINCT i.id)::int AS total_qty,
                (COALESCE(pp.price, 0) * COUNT(DISTINCT i.id))::numeric(18, 2) AS total_nominal
            ")
            ->groupBy(
                'l.id',
                'l.name',
                'p.id',
                'p.code',
                'p.name',
                'pp.price_type',
                'pp.price',
                ...($warehouseHasCode ? ['l.code'] : []),
                ...($hasUnits ? ['u.name'] : [])
            );

        return $query;
    }

    private function productPriceQuery(string $priceType)
    {
        if (
            !Schema::hasTable('product_prices') ||
            !Schema::hasColumn('product_prices', 'product_id') ||
            !Schema::hasColumn('product_prices', 'price_type') ||
            !Schema::hasColumn('product_prices', 'price')
        ) {
            return DB::table('products')
                ->selectRaw("
                    id as product_id,
                    ?::text as price_type,
                    0::numeric(18, 2) as price
                ", [$priceType])
                ->whereRaw('1 = 0');
        }

        return DB::table('product_prices as pp')
            ->selectRaw("
                DISTINCT ON (pp.product_id)
                pp.product_id,
                pp.price_type::text AS price_type,
                COALESCE(pp.price, 0)::numeric(18, 2) AS price
            ")
            ->whereRaw('pp.price_type::text = ?', [$priceType])
            ->when(Schema::hasColumn('product_prices', 'valid_from'), function ($query) {
                $query->where(function ($sub) {
                    $sub->whereNull('pp.valid_from')
                        ->orWhereDate('pp.valid_from', '<=', now()->toDateString());
                });
            })
            ->when(Schema::hasColumn('product_prices', 'valid_to'), function ($query) {
                $query->where(function ($sub) {
                    $sub->whereNull('pp.valid_to')
                        ->orWhereDate('pp.valid_to', '>=', now()->toDateString());
                });
            })
            ->orderBy('pp.product_id')
            ->when(
                Schema::hasColumn('product_prices', 'valid_from'),
                fn ($query) => $query->orderByDesc('pp.valid_from')
            )
            ->when(
                Schema::hasColumn('product_prices', 'created_at'),
                fn ($query) => $query->orderByDesc('pp.created_at')
            )
            ->orderByDesc('pp.id');
    }

    private function emptyStockQuery()
    {
        return DB::table('items as i')
            ->selectRaw("
                'empty' AS location_type,
                '' AS location_id,
                '' AS location_name,
                '' AS location_code,
                '' AS product_id,
                '' AS product_code,
                '' AS product_name,
                '' AS unit_name,
                '' AS price_type,
                0::numeric(18, 2) AS product_price,

                0::int AS qty_store_stock,
                0::int AS qty_store_display,
                0::int AS qty_warehouse_stock,

                0::int AS qty_good,
                0::int AS qty_good_after_repair,
                0::int AS qty_bad,

                0::int AS total_qty,
                0::numeric(18, 2) AS total_nominal
            ")
            ->whereRaw('1 = 0');
    }

    private function locationOptions(string $mode)
    {
        $locations = collect();

        if ($mode === 'all' || $mode === 'store') {
            if (Schema::hasTable('stores')) {
                $storeHasCode = Schema::hasColumn('stores', 'code');

                $stores = DB::table('stores')
                    ->when(
                        Schema::hasColumn('stores', 'deleted_at'),
                        fn ($query) => $query->whereNull('deleted_at')
                    )
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        ...($storeHasCode ? ['code'] : []),
                    ])
                    ->map(function ($row) use ($storeHasCode) {
                        $code = $storeHasCode ? ($row->code ?? '') : '';

                        return (object) [
                            'key'   => 'store:' . $row->id,
                            'type'  => 'Store',
                            'name'  => $row->name,
                            'code'  => $code,
                            'label' => 'Store - ' . $row->name . ($code !== '' ? ' (' . $code . ')' : ''),
                        ];
                    });

                $locations = $locations->merge($stores);
            }
        }

        if ($mode === 'all' || $mode === 'warehouse') {
            if (Schema::hasTable('warehouses')) {
                $warehouseHasCode = Schema::hasColumn('warehouses', 'code');

                $warehouses = DB::table('warehouses')
                    ->when(
                        Schema::hasColumn('warehouses', 'deleted_at'),
                        fn ($query) => $query->whereNull('deleted_at')
                    )
                    ->orderBy('name')
                    ->get([
                        'id',
                        'name',
                        ...($warehouseHasCode ? ['code'] : []),
                    ])
                    ->map(function ($row) use ($warehouseHasCode) {
                        $code = $warehouseHasCode ? ($row->code ?? '') : '';

                        return (object) [
                            'key'   => 'warehouse:' . $row->id,
                            'type'  => 'Warehouse',
                            'name'  => $row->name,
                            'code'  => $code,
                            'label' => 'Warehouse - ' . $row->name . ($code !== '' ? ' (' . $code . ')' : ''),
                        ];
                    });

                $locations = $locations->merge($warehouses);
            }
        }

        return $locations;
    }

    private function resolveItemConditionExpression(string $itemAlias): string
    {
        if (Schema::hasColumn('items', 'condition')) {
            return $itemAlias . '.condition';
        }

        if (Schema::hasColumn('items', 'item_condition')) {
            return $itemAlias . '.item_condition';
        }

        if (Schema::hasColumn('items', 'barang_condition')) {
            return $itemAlias . '.barang_condition';
        }

        if (Schema::hasColumn('items', 'item_status')) {
            return $itemAlias . '.item_status';
        }

        if (Schema::hasColumn('items', 'status')) {
            return $itemAlias . '.status';
        }

        return "''";
    }
}