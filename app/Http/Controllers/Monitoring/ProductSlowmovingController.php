<?php

namespace App\Http\Controllers\Monitoring;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class ProductSlowmovingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-product-slowmoving')
            ->only(['index', 'exportSlow', 'exportDead']);
    }

    public function index(Request $request)
    {
        $mode = $request->query('mode', 'store');

        if (!in_array($mode, ['store', 'warehouse'], true)) {
            $mode = 'store';
        }

        $user = $request->user();

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $filters = [
            'mode'         => $mode,
            'store_id'     => $mode === 'store'
                ? $request->query('store_id', $user->store_id ?? null)
                : null,
            'warehouse_id' => $mode === 'warehouse'
                ? $request->query('warehouse_id', $user->warehouse_id ?? null)
                : null,
            'start_date'   => $startDate,
            'end_date'     => $endDate,
        ];

        $stores = DB::table('stores')
            ->when(Schema::hasColumn('stores', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $warehouses = DB::table('warehouses')
            ->when(Schema::hasColumn('warehouses', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $slow = collect();
        $dead = collect();

        if ($mode === 'store' && !empty($filters['store_id'])) {
            $slow = $this->storeSlowMoving($filters['store_id'], $startDate, $endDate);
            $dead = $this->storeDeadStock($filters['store_id'], $startDate);
        }

        if ($mode === 'warehouse' && !empty($filters['warehouse_id'])) {
            $slow = $this->warehouseSlowMoving($filters['warehouse_id'], $startDate, $endDate);
            $dead = $this->warehouseDeadStock($filters['warehouse_id'], $startDate);
        }

        $summary = [
            'slow_count'          => $slow->count(),
            'dead_count'          => $dead->count(),
            'slow_qty'            => (int) $slow->sum('quantity'),
            'dead_qty'            => (int) $dead->sum('quantity'),
            'total_qty'           => (int) $slow->sum('quantity') + (int) $dead->sum('quantity'),
            'slow_total_nominal'  => (float) $slow->sum('total_nominal'),
            'dead_total_nominal'  => (float) $dead->sum('total_nominal'),
            'total_nominal'       => (float) $slow->sum('total_nominal') + (float) $dead->sum('total_nominal'),
        ];

        $chartProductsSlow = $this->buildTopProductChart($slow);
        $chartProductsDead = $this->buildTopProductChart($dead);
        $chartStatus = $this->buildStatusChart($slow, $dead);

        return Inertia::render('Monitoring/Report', [
            'title' => 'Product Slowmoving',
            'description' => 'Laporan monitoring.',
            'data' => [
            'mode'              => $mode,
            'filters'           => $filters,
            'stores'            => $stores,
            'warehouses'        => $warehouses,
            'slow'              => $slow,
            'dead'              => $dead,
            'summary'           => $summary,
            'chartProductsSlow' => $chartProductsSlow,
            'chartProductsDead' => $chartProductsDead,
            'chartStatus'       => $chartStatus,
            'modeOptions'       => [
                ['value' => 'store', 'label' => 'Store'],
                ['value' => 'warehouse', 'label' => 'Warehouse'],
            ],
            'lastUpdate'        => now()->format('d-m-Y H:i:s'),
        ],
            'filters' => request()->query(),
            'urls' => [
                'index' => url()->current(),
                'exports' => [
                    'slow' => route('monitoring.product-slowmoving.export-slow'),
                    'dead' => route('monitoring.product-slowmoving.export-dead'),
                ],
            ],
        ]);
    }

    public function exportSlow(Request $request)
    {
        return $this->export($request, 'slow');
    }

    public function exportDead(Request $request)
    {
        return $this->export($request, 'dead');
    }

    private function export(Request $request, string $type)
    {
        $mode = $request->query('mode', 'store');

        if (!in_array($mode, ['store', 'warehouse'], true)) {
            $mode = 'store';
        }

        $user = $request->user();

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->startOfMonth()->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $storeId = $request->query('store_id', $user->store_id ?? null);
        $warehouseId = $request->query('warehouse_id', $user->warehouse_id ?? null);

        if ($mode === 'store' && !$storeId) {
            return back()->with('error', 'Toko harus dipilih terlebih dahulu.');
        }

        if ($mode === 'warehouse' && !$warehouseId) {
            return back()->with('error', 'Gudang harus dipilih terlebih dahulu.');
        }

        if ($mode === 'store') {
            $rows = $type === 'slow'
                ? $this->storeSlowMoving($storeId, $startDate, $endDate)
                : $this->storeDeadStock($storeId, $startDate);

            $locationName = DB::table('stores')
                ->where('id', $storeId)
                ->value('name') ?? '-';
        } else {
            $rows = $type === 'slow'
                ? $this->warehouseSlowMoving($warehouseId, $startDate, $endDate)
                : $this->warehouseDeadStock($warehouseId, $startDate);

            $locationName = DB::table('warehouses')
                ->where('id', $warehouseId)
                ->value('name') ?? '-';
        }

        $typeName = $type === 'slow' ? 'slow-moving' : 'deadstock';

        $filename = 'produk-' . $typeName . '-' . $mode . '-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new class($rows, [
                'mode'          => $mode,
                'type'          => $type,
                'location_name' => $locationName,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
            ]) implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize {
                private int $no = 0;

                public function __construct(
                    private Collection $rows,
                    private array $meta
                ) {
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
                        'Area',
                        'Nama Lokasi',
                        'Tanggal Dari',
                        'Tanggal Sampai',
                        'Kode Produk',
                        'Nama Produk',
                        'Kondisi',
                        'Status Stock',
                        'Lokasi Barang',
                        'Price Type',
                        'Harga Produk',
                        'Qty',
                        'Total Nominal',
                    ];
                }

                public function map($row): array
                {
                    $this->no++;

                    $location = $row->room_name ?? '-';

                    if (!empty($row->rack_name)) {
                        $location .= ' / ' . $row->rack_name;
                    }

                    if (!empty($row->layer_name)) {
                        $location .= ' / ' . $row->layer_name;
                    }

                    return [
                        $this->no,
                        $this->meta['type'] === 'slow' ? 'Slow Moving' : 'Deadstock',
                        ucfirst($this->meta['mode']),
                        $this->meta['location_name'],
                        $this->meta['start_date'],
                        $this->meta['end_date'],
                        $row->product_code ?? '-',
                        $row->product_name ?? '-',
                        $row->item_condition ?? $row->status ?? '-',
                        $row->stock_status ?? '-',
                        $location,
                        $row->price_type ?? '-',
                        (float) ($row->product_price ?? 0),
                        (int) ($row->quantity ?? 0),
                        (float) ($row->total_nominal ?? 0),
                    ];
                }
            },
            $filename
        );
    }

    private function storeSlowMoving(string $storeId, string $startDate, string $endDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $stockStatusCase = "
            CASE
                WHEN i.status::text = 'store_display' THEN 'Display'
                WHEN i.status::text = 'store_stock' THEN 'Stock'
                ELSE i.status::text
            END
        ";

        $itemConditionCase = "
            CASE
                WHEN ic.name = 'Good' THEN 'Good'
                ELSE 'Bad'
            END
        ";

        return DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->whereIn('i.status', ['store_display', 'store_stock']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoinSub($this->productPriceQuery('store'), 'pp', function ($join) {
                $join->on('pp.product_id', '=', 'p.id');
            })
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('si.store_id', $storeId)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->whereNull('i.old_item_id')
                        ->whereBetween('inh.received_date', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereBetween('i.created_at', [$start, $end]);
                });
            })
            ->select([
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                DB::raw("{$itemConditionCase} as item_condition"),
                DB::raw("{$stockStatusCase} as stock_status"),
                'rm.name as room_name',
                'rk.name as rack_name',
                'lr.name as layer_name',
                DB::raw("COALESCE(pp.price_type, 'store') as price_type"),
                DB::raw("COALESCE(pp.price, 0)::numeric(18, 2) as product_price"),
                DB::raw('COUNT(si.id)::int as quantity'),
                DB::raw('(COALESCE(pp.price, 0) * COUNT(si.id))::numeric(18, 2) as total_nominal'),
            ])
            ->groupBy([
                'p.id',
                'p.code',
                'p.name',
                DB::raw($itemConditionCase),
                DB::raw($stockStatusCase),
                'rm.name',
                'rk.name',
                'lr.name',
                'pp.price_type',
                'pp.price',
            ])
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
    }

    private function storeDeadStock(string $storeId, string $startDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();

        $stockStatusCase = "
            CASE
                WHEN i.status::text = 'store_display' THEN 'Display'
                WHEN i.status::text = 'store_stock' THEN 'Stock'
                ELSE i.status::text
            END
        ";

        $itemConditionCase = "
            CASE
                WHEN ic.name = 'Good' THEN 'Good'
                ELSE 'Bad'
            END
        ";

        return DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->whereIn('i.status', ['store_display', 'store_stock']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoinSub($this->productPriceQuery('store'), 'pp', function ($join) {
                $join->on('pp.product_id', '=', 'p.id');
            })
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('si.store_id', $storeId)
            ->where(function ($q) use ($start) {
                $q->where(function ($sub) use ($start) {
                    $sub->whereNull('i.old_item_id')
                        ->whereDate('inh.received_date', '<=', $start->toDateString());
                })->orWhere(function ($sub) use ($start) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereDate('i.created_at', '<=', $start->toDateString());
                });
            })
            ->select([
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                DB::raw("{$itemConditionCase} as item_condition"),
                DB::raw("{$stockStatusCase} as stock_status"),
                'rm.name as room_name',
                'rk.name as rack_name',
                'lr.name as layer_name',
                DB::raw("COALESCE(pp.price_type, 'store') as price_type"),
                DB::raw("COALESCE(pp.price, 0)::numeric(18, 2) as product_price"),
                DB::raw('COUNT(si.id)::int as quantity'),
                DB::raw('(COALESCE(pp.price, 0) * COUNT(si.id))::numeric(18, 2) as total_nominal'),
            ])
            ->groupBy([
                'p.id',
                'p.code',
                'p.name',
                DB::raw($itemConditionCase),
                DB::raw($stockStatusCase),
                'rm.name',
                'rk.name',
                'lr.name',
                'pp.price_type',
                'pp.price',
            ])
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
    }

    private function warehouseSlowMoving(string $warehouseId, string $startDate, string $endDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        $conditionCase = "(CASE WHEN ic.name = 'Good' THEN 'Good' ELSE 'Bad' END)";

        return DB::table('warehouse_items as wi')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wi.item_id')
                    ->whereIn('i.status', ['warehouse_stock', 'warehouse_processing']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoinSub($this->productPriceQuery('base'), 'pp', function ($join) {
                $join->on('pp.product_id', '=', 'p.id');
            })
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('wi.warehouse_id', $warehouseId)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->whereNull('i.old_item_id')
                        ->whereBetween('inh.received_date', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereBetween('i.created_at', [$start, $end]);
                });
            })
            ->select([
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                DB::raw("{$conditionCase} as item_condition"),
                DB::raw("'Warehouse' as stock_status"),
                'rm.name as room_name',
                'rk.name as rack_name',
                'lr.name as layer_name',
                DB::raw("COALESCE(pp.price_type, 'base') as price_type"),
                DB::raw("COALESCE(pp.price, 0)::numeric(18, 2) as product_price"),
                DB::raw('COUNT(wi.id)::int as quantity'),
                DB::raw('(COALESCE(pp.price, 0) * COUNT(wi.id))::numeric(18, 2) as total_nominal'),
            ])
            ->groupBy([
                'p.id',
                'p.code',
                'p.name',
                DB::raw($conditionCase),
                'rm.name',
                'rk.name',
                'lr.name',
                'pp.price_type',
                'pp.price',
            ])
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
    }

    private function warehouseDeadStock(string $warehouseId, string $startDate)
    {
        $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();

        $conditionCase = "(CASE WHEN ic.name = 'Good' THEN 'Good' ELSE 'Bad' END)";

        return DB::table('warehouse_items as wi')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wi.item_id')
                    ->whereIn('i.status', ['warehouse_stock', 'warehouse_processing']);
            })
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoinSub($this->productPriceQuery('base'), 'pp', function ($join) {
                $join->on('pp.product_id', '=', 'p.id');
            })
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->leftJoin('locations as lr', 'lr.id', '=', 'i.current_location_id')
            ->leftJoin('locations as rk', 'rk.id', '=', 'lr.location_parent_id')
            ->leftJoin('locations as rm', 'rm.id', '=', 'rk.location_parent_id')
            ->leftJoin('warehouse_inbound_details as ind', 'ind.id', '=', 'i.warehouse_inbound_detail_id')
            ->leftJoin('warehouse_inbounds as inh', 'inh.id', '=', 'ind.warehouse_inbound_id')
            ->where('wi.warehouse_id', $warehouseId)
            ->where(function ($q) use ($start) {
                $q->where(function ($sub) use ($start) {
                    $sub->whereNull('i.old_item_id')
                        ->whereDate('inh.received_date', '<=', $start->toDateString());
                })->orWhere(function ($sub) use ($start) {
                    $sub->whereNotNull('i.old_item_id')
                        ->whereDate('i.created_at', '<=', $start->toDateString());
                });
            })
            ->select([
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                DB::raw("{$conditionCase} as item_condition"),
                DB::raw("'Warehouse' as stock_status"),
                'rm.name as room_name',
                'rk.name as rack_name',
                'lr.name as layer_name',
                DB::raw("COALESCE(pp.price_type, 'base') as price_type"),
                DB::raw("COALESCE(pp.price, 0)::numeric(18, 2) as product_price"),
                DB::raw('COUNT(wi.id)::int as quantity'),
                DB::raw('(COALESCE(pp.price, 0) * COUNT(wi.id))::numeric(18, 2) as total_nominal'),
            ])
            ->groupBy([
                'p.id',
                'p.code',
                'p.name',
                DB::raw($conditionCase),
                'rm.name',
                'rk.name',
                'lr.name',
                'pp.price_type',
                'pp.price',
            ])
            ->orderBy('quantity', 'asc')
            ->orderBy('p.name', 'asc')
            ->get();
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

    private function buildTopProductChart(Collection $rows): Collection
    {
        return $rows
            ->groupBy('product_id')
            ->map(function ($items) {
                $first = $items->first();

                return (object) [
                    'product_code'   => $first->product_code ?? '-',
                    'product_name'   => $first->product_name ?? '-',
                    'quantity'       => (int) $items->sum('quantity'),
                    'total_nominal'  => (float) $items->sum('total_nominal'),
                ];
            })
            ->sortByDesc('quantity')
            ->take(12)
            ->values();
    }

    private function buildStatusChart(Collection $slow, Collection $dead): Collection
    {
        return collect([
            (object) [
                'label' => 'Slow Moving',
                'value' => (int) $slow->sum('quantity'),
                'nominal' => (float) $slow->sum('total_nominal'),
            ],
            (object) [
                'label' => 'Deadstock',
                'value' => (int) $dead->sum('quantity'),
                'nominal' => (float) $dead->sum('total_nominal'),
            ],
        ]);
    }

    private function normalizeDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        $date = trim($date);

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
}
