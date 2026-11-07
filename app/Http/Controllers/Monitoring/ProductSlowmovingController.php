<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class ProductSlowmovingController extends Controller
{
    private const SLOW_MAX_QTY_DEFAULT = 5;

    private ?Collection $accessibleStores = null;

    private ?Collection $accessibleWarehouses = null;

    public function __construct()
    {
        $this->middleware('permission:view monitoring-product-slowmoving')
            ->only(['index', 'exportSlow', 'exportFast', 'exportDead', 'exportExpiring']);
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $rows = $this->movementRows($filters);

        $slow = $rows->where('movement_status', 'Slow Moving')->values();
        $fast = $rows->where('movement_status', 'Fast Moving')->values();
        $dead = $rows->where('movement_status', 'Deadstock')->values();
        $expiring = $this->expiringRows($filters);

        $summary = [
            'slow_count' => $slow->count(),
            'fast_count' => $fast->count(),
            'dead_count' => $dead->count(),
            'expiring_count' => $expiring->count(),
            'slow_qty' => (int) $slow->sum('qty_stock'),
            'dead_qty' => (int) $dead->sum('qty_stock'),
            'expiring_qty' => (int) $expiring->sum('qty_stock'),
            'expired_qty' => (int) $expiring->where('expiry_status', 'Kedaluwarsa')->sum('qty_stock'),
        ];

        return Inertia::render('Monitoring/Report', [
            'title' => 'Slow Moving & Kedaluwarsa',
            'description' => 'Pergerakan produk dan risiko kedaluwarsa pada periode terpilih.',
            'data' => [
                'mode' => $filters['mode'],
                'filters' => $filters,
                'activeDataset' => $this->activeDataset($request),
                'stores' => $this->stores(),
                'warehouses' => $this->warehouses(),
                'categories' => $this->categories(),
                'slow' => $slow,
                'fast' => $fast,
                'dead' => $dead,
                'expiring' => $expiring,
                'summary' => $summary,
                'chartProductsSlow' => $this->buildTopProductChart($slow, 'qty_stock'),
                'chartProductsFast' => $this->buildTopProductChart($fast, 'qty_sold'),
                'chartProductsDead' => $this->buildTopProductChart($dead, 'qty_stock'),
                'chartProductsExpiring' => $this->buildTopProductChart($expiring, 'qty_stock'),
                'chartStatus' => collect([
                    (object) [
                        'label' => 'Slow Moving',
                        'value' => $slow->count(),
                        'nominal' => (float) $slow->sum('stock_value'),
                    ],
                    (object) [
                        'label' => 'Fast Moving',
                        'value' => $fast->count(),
                        'nominal' => (float) $fast->sum('stock_value'),
                    ],
                    (object) [
                        'label' => 'Deadstock',
                        'value' => $dead->count(),
                        'nominal' => (float) $dead->sum('stock_value'),
                    ],
                    (object) [
                        'label' => 'Mendekati Kedaluwarsa',
                        'value' => $expiring->count(),
                        'nominal' => (float) $expiring->sum('stock_value'),
                    ],
                ]),
                'modeOptions' => [
                    ['value' => 'all', 'label' => 'Semua Lokasi'],
                    ['value' => 'store', 'label' => 'Store'],
                    ['value' => 'warehouse', 'label' => 'Warehouse'],
                ],
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => [
                'index' => url()->current(),
                'exports' => [
                    'slow' => route('monitoring.product-slowmoving.export-slow'),
                    'fast' => route('monitoring.product-slowmoving.export-fast'),
                    'dead' => route('monitoring.product-slowmoving.export-dead'),
                    'expiring' => route('monitoring.product-slowmoving.export-expiring'),
                ],
            ],
        ]);
    }

    public function exportSlow(Request $request)
    {
        return $this->export($request, 'Slow Moving');
    }

    public function exportFast(Request $request)
    {
        return $this->export($request, 'Fast Moving');
    }

    public function exportDead(Request $request)
    {
        return $this->export($request, 'Deadstock');
    }

    public function exportExpiring(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->expiringRows($filters);

        $filename = 'produk-kedaluwarsa-'.$filters['mode'].'-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new class($rows, ['mode' => $filters['mode'], 'location_name' => $this->locationName($filters), 'expiry_days' => $filters['expiry_days']]) implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
            {
                private int $no = 0;

                public function __construct(
                    private Collection $rows,
                    private array $meta
                ) {}

                public function collection()
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'No',
                        'Area',
                        'Tipe Lokasi',
                        'Nama Lokasi',
                        'Kategori',
                        'Kode Produk',
                        'Nama Produk',
                        'Tanggal Kedaluwarsa',
                        'Sisa Hari',
                        'Status Kedaluwarsa',
                        'Qty Stok',
                        'Harga Produk',
                        'Nilai Stok',
                    ];
                }

                public function map($row): array
                {
                    $this->no++;

                    return [
                        $this->no,
                        $this->meta['mode'] === 'all' ? 'Semua' : ucfirst($this->meta['mode']),
                        $row->location_type ?? '-',
                        $row->location_name ?? $this->meta['location_name'],
                        $row->category_name ?? '-',
                        $row->product_code ?? '-',
                        $row->product_name ?? '-',
                        $row->expired_date ?? '-',
                        (int) ($row->days_to_expiry ?? 0),
                        $row->expiry_status ?? '-',
                        (int) ($row->qty_stock ?? 0),
                        (float) ($row->product_price ?? 0),
                        (float) ($row->stock_value ?? 0),
                    ];
                }
            },
            $filename
        );
    }

    private function export(Request $request, string $status)
    {
        $filters = $this->filters($request);

        $rows = $this->movementRows($filters)
            ->where('movement_status', $status)
            ->values();

        $locationName = $this->locationName($filters);

        $typeName = match ($status) {
            'Slow Moving' => 'slow-moving',
            'Fast Moving' => 'fast-moving',
            default => 'deadstock',
        };

        $filename = 'produk-'.$typeName.'-'.$filters['mode'].'-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new class($rows, ['mode' => $filters['mode'], 'status' => $status, 'location_name' => $locationName, 'start_date' => $filters['start_date'], 'end_date' => $filters['end_date']]) implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
            {
                private int $no = 0;

                public function __construct(
                    private Collection $rows,
                    private array $meta
                ) {}

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
                        'Tipe Lokasi',
                        'Nama Lokasi',
                        'Tanggal Dari',
                        'Tanggal Sampai',
                        'Kategori',
                        'Kode Produk',
                        'Nama Produk',
                        'Qty Terjual / Keluar',
                        'Qty Stok',
                        'Harga Produk',
                        'Nilai Stok',
                        'Terakhir Terjual / Keluar',
                        'Status Pergerakan',
                    ];
                }

                public function map($row): array
                {
                    $this->no++;

                    return [
                        $this->no,
                        $this->meta['status'],
                        $this->meta['mode'] === 'all' ? 'Semua' : ucfirst($this->meta['mode']),
                        $row->location_type ?? '-',
                        $row->location_name ?? $this->meta['location_name'],
                        $this->meta['start_date'],
                        $this->meta['end_date'],
                        $row->category_name ?? '-',
                        $row->product_code ?? '-',
                        $row->product_name ?? '-',
                        (int) ($row->qty_sold ?? 0),
                        (int) ($row->qty_stock ?? 0),
                        (float) ($row->product_price ?? 0),
                        (float) ($row->stock_value ?? 0),
                        $row->last_sold_at ?? '-',
                        $row->movement_status ?? '-',
                    ];
                }
            },
            $filename
        );
    }

    private function filters(Request $request): array
    {
        $mode = $request->query('mode', 'all');

        if (! in_array($mode, ['all', 'store', 'warehouse'], true)) {
            $mode = 'all';
        }

        $startDate = $this->normalizeDate($request->query('start_date'))
            ?: now()->subDays(29)->format('Y-m-d');

        $endDate = $this->normalizeDate($request->query('end_date'))
            ?: now()->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $slowMaxQty = (int) $request->query('slow_max_qty', self::SLOW_MAX_QTY_DEFAULT);

        if ($slowMaxQty < 1) {
            $slowMaxQty = self::SLOW_MAX_QTY_DEFAULT;
        }

        $slowMaxQty = min($slowMaxQty, 1000);

        $expiryDays = (int) $request->query('expiry_days', 365);

        if ($expiryDays < 0) {
            $expiryDays = 365;
        }

        $expiryDays = min($expiryDays, 3650);

        $storeId = null;

        if ($mode === 'store') {
            $storeId = $this->nullable($request->query('store_id'));

            if ($storeId && ! $this->accessibleStores()->contains('id', $storeId)) {
                $storeId = null;
            }

            if (! $storeId && $this->accessibleStores()->count() === 1) {
                $storeId = $this->accessibleStores()->first()->id;
            }
        }

        $warehouseId = null;

        if ($mode === 'warehouse') {
            $warehouseId = $this->nullable($request->query('warehouse_id'));

            if ($warehouseId && ! $this->accessibleWarehouses()->contains('id', $warehouseId)) {
                $warehouseId = null;
            }

            if (! $warehouseId && $this->accessibleWarehouses()->count() === 1) {
                $warehouseId = $this->accessibleWarehouses()->first()->id;
            }
        }

        return [
            'mode' => $mode,
            'store_id' => $storeId,
            'warehouse_id' => $warehouseId,
            'category_id' => $this->nullable($request->query('category_id')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'slow_max_qty' => $slowMaxQty,
            'expiry_days' => $expiryDays,
        ];
    }

    private function activeDataset(Request $request): ?string
    {
        $dataset = (string) $request->query('dataset', '');

        return in_array($dataset, ['slow', 'fast', 'dead', 'expiring'], true) ? $dataset : null;
    }

    private function movementRows(array $filters): Collection
    {
        if ($filters['mode'] === 'warehouse') {
            return $this->warehouseRows($filters);
        }

        if ($filters['mode'] === 'all') {
            return $this->storeRows($filters)
                ->concat($this->warehouseRows($filters))
                ->values();
        }

        return $this->storeRows($filters);
    }

    private function storeRows(array $filters): Collection
    {
        $sales = $this->storeSales($filters)
            ->get()
            ->concat($this->ecommerceSales($filters)->get())
            ->groupBy(fn ($row) => ($row->store_id ?? '').'|'.$row->product_id)
            ->map(function (Collection $items) {
                $first = $items->first();

                return (object) [
                    'store_id' => $first->store_id,
                    'product_id' => $first->product_id,
                    'qty_sold' => (int) $items->sum('qty_sold'),
                    'last_sold_at' => $items->max('last_sold_at'),
                ];
            });

        $stock = $this->storeStock($filters['store_id'])
            ->get()
            ->keyBy(fn ($row) => (string) $row->store_id.'|'.$row->product_id);

        $products = $this->productMeta();
        $prices = $this->priceMap('store');
        $storeNames = DB::table('stores')->pluck('name', 'id');

        return $sales->keys()
            ->merge($stock->keys())
            ->unique()
            ->map(function (string $key) use ($sales, $stock, $products, $prices, $storeNames, $filters) {
                [$storeId, $productId] = explode('|', $key, 2);

                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                if ($filters['category_id'] && (string) $product->category_id !== $filters['category_id']) {
                    return null;
                }

                $sale = $sales->get($key);
                $stockRow = $stock->get($key);

                $qtySold = (int) ($sale->qty_sold ?? 0);
                $qtyStock = (int) ($stockRow->qty_stock ?? 0);

                $status = $this->movementStatus($qtySold, $qtyStock, $filters['slow_max_qty']);

                if (! $status) {
                    return null;
                }

                $price = (float) ($prices[$productId] ?? 0);

                return (object) [
                    'product_id' => $productId,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category_name ?? '-',
                    'store_id' => $storeId !== '' ? $storeId : null,
                    'location_type' => $storeId !== '' ? 'Store' : 'Ecommerce',
                    'location_name' => $storeId !== '' ? ($storeNames[$storeId] ?? '-') : 'Ecommerce',
                    'qty_sold' => $qtySold,
                    'qty_stock' => $qtyStock,
                    'product_price' => $price,
                    'stock_value' => round($qtyStock * $price, 2),
                    'last_sold_at' => $sale->last_sold_at ?? null,
                    'movement_status' => $status,
                ];
            })
            ->filter()
            ->values();
    }

    private function warehouseRows(array $filters): Collection
    {
        $sales = $this->warehouseSales($filters)
            ->get()
            ->groupBy(fn ($row) => $row->warehouse_id.'|'.$row->product_id)
            ->map(function (Collection $items) {
                $first = $items->first();

                return (object) [
                    'warehouse_id' => $first->warehouse_id,
                    'product_id' => $first->product_id,
                    'qty_sold' => (int) $items->sum('qty_sold'),
                    'last_sold_at' => $items->max('last_sold_at'),
                ];
            });

        $stock = $this->warehouseStock($filters['warehouse_id'])
            ->get()
            ->keyBy(fn ($row) => $row->warehouse_id.'|'.$row->product_id);

        $products = $this->productMeta();
        $prices = $this->priceMap('base');
        $warehouseNames = DB::table('warehouses')->pluck('name', 'id');

        return $sales->keys()
            ->merge($stock->keys())
            ->unique()
            ->map(function (string $key) use ($sales, $stock, $products, $prices, $warehouseNames, $filters) {
                [$warehouseId, $productId] = explode('|', $key, 2);

                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                if ($filters['category_id'] && (string) $product->category_id !== $filters['category_id']) {
                    return null;
                }

                $sale = $sales->get($key);
                $stockRow = $stock->get($key);

                $qtySold = (int) ($sale->qty_sold ?? 0);
                $qtyStock = (int) ($stockRow->qty_stock ?? 0);

                $status = $this->movementStatus($qtySold, $qtyStock, $filters['slow_max_qty']);

                if (! $status) {
                    return null;
                }

                $price = (float) ($prices[$productId] ?? 0);

                return (object) [
                    'product_id' => $productId,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category_name ?? '-',
                    'warehouse_id' => $warehouseId,
                    'location_type' => 'Warehouse',
                    'location_name' => $warehouseNames[$warehouseId] ?? '-',
                    'qty_sold' => $qtySold,
                    'qty_stock' => $qtyStock,
                    'product_price' => $price,
                    'stock_value' => round($qtyStock * $price, 2),
                    'last_sold_at' => $sale->last_sold_at ?? null,
                    'movement_status' => $status,
                ];
            })
            ->filter()
            ->values();
    }

    private function expiringRows(array $filters): Collection
    {
        $storeRows = $this->storeExpiringStock($filters)->get();
        $warehouseRows = $this->warehouseExpiringStock($filters)->get();

        $rows = match ($filters['mode']) {
            'warehouse' => $warehouseRows,
            'all' => $storeRows->concat($warehouseRows),
            default => $storeRows,
        };

        $products = $this->productMeta();
        $storePrices = $this->priceMap('store');
        $basePrices = $this->priceMap('base');
        $storeNames = DB::table('stores')->pluck('name', 'id');
        $warehouseNames = DB::table('warehouses')->pluck('name', 'id');

        return $rows
            ->map(function ($row) use ($products, $storePrices, $basePrices, $storeNames, $warehouseNames, $filters) {
                $product = $products->get($row->product_id);

                if (! $product) {
                    return null;
                }

                if ($filters['category_id'] && (string) $product->category_id !== $filters['category_id']) {
                    return null;
                }

                $expiredDate = Carbon::parse($row->expired_date)->startOfDay();
                $daysToExpiry = (int) now()->startOfDay()->diffInDays($expiredDate, false);
                $isWarehouse = $row->scope_type === 'warehouse';
                $prices = $isWarehouse ? $basePrices : $storePrices;
                $locationNames = $isWarehouse ? $warehouseNames : $storeNames;
                $price = (float) ($prices[$row->product_id] ?? 0);
                $quantity = (int) $row->qty_stock;

                return (object) [
                    'product_id' => $row->product_id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category_name ?? '-',
                    'location_type' => $isWarehouse ? 'Warehouse' : 'Store',
                    'location_id' => $row->location_id,
                    'location_name' => $locationNames[$row->location_id] ?? '-',
                    'expired_date' => $expiredDate->toDateString(),
                    'days_to_expiry' => $daysToExpiry,
                    'expiry_status' => $this->expiryStatus($daysToExpiry),
                    'qty_stock' => $quantity,
                    'product_price' => $price,
                    'stock_value' => round($quantity * $price, 2),
                ];
            })
            ->filter()
            ->values()
            ->sortBy('expired_date')
            ->values();
    }

    private function expiryStatus(int $daysToExpiry): string
    {
        if ($daysToExpiry < 0) {
            return 'Kedaluwarsa';
        }

        if ($daysToExpiry <= 7) {
            return 'Mendesak';
        }

        if ($daysToExpiry <= 30) {
            return 'Segera';
        }

        return 'Aman';
    }

    private function storeExpiringStock(array $filters)
    {
        $query = DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->where('i.status', '=', 'store_stock');
            })
            ->where('si.category', 'inventory')
            ->whereNotNull('i.expired_date')
            ->whereDate('i.expired_date', '<=', now()->addDays($filters['expiry_days'])->toDateString());

        $this->applyStoreScope($query, $filters['store_id'], 'si.store_id');

        return $query
            ->selectRaw("
                'store'::text as scope_type,
                si.store_id as location_id,
                i.product_id,
                i.expired_date::date as expired_date,
                COUNT(DISTINCT si.id)::int as qty_stock
            ")
            ->groupByRaw('si.store_id, i.product_id, i.expired_date::date');
    }

    private function warehouseExpiringStock(array $filters)
    {
        $query = DB::table('warehouse_items as wi')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wi.item_id')
                    ->where('i.status', '=', 'warehouse_stock');
            })
            ->whereNotNull('i.expired_date')
            ->whereDate('i.expired_date', '<=', now()->addDays($filters['expiry_days'])->toDateString());

        $this->applyWarehouseScope($query, $filters['warehouse_id'], 'wi.warehouse_id');

        return $query
            ->selectRaw("
                'warehouse'::text as scope_type,
                wi.warehouse_id as location_id,
                i.product_id,
                i.expired_date::date as expired_date,
                COUNT(DISTINCT wi.id)::int as qty_stock
            ")
            ->groupByRaw('wi.warehouse_id, i.product_id, i.expired_date::date');
    }

    private function movementStatus(int $qtySold, int $qtyStock, int $slowMaxQty): ?string
    {
        if ($qtySold <= 0) {
            return $qtyStock > 0 ? 'Deadstock' : null;
        }

        return $qtySold <= $slowMaxQty ? 'Slow Moving' : 'Fast Moving';
    }

    private function storeSales(array $filters)
    {
        $paidVisits = $this->paidVisits();

        $query = DB::table('store_customer_visits as visit')
            ->joinSub($paidVisits, 'paid_visits', 'paid_visits.visit_id', '=', 'visit.id')
            ->join('stores as store', 'store.id', '=', 'visit.store_id')
            ->join('store_carts as cart', 'cart.visit_id', '=', 'visit.id')
            ->join('store_cart_details as detail', 'detail.cart_id', '=', 'cart.id')
            ->leftJoin('items as item', 'item.id', '=', 'detail.item_id')
            ->leftJoin('product_prices as price', 'price.id', '=', 'detail.product_price_id')
            ->join('products as product', function ($join) {
                $join->on('product.id', '=', 'item.product_id')
                    ->orOn('product.id', '=', 'price.product_id');
            })
            ->whereNull('store.deleted_at')
            ->where(function ($query) {
                $query->whereNull('detail.void_status')->orWhere('detail.void_status', '!=', 'void');
            })
            ->whereDate('paid_visits.paid_at', '>=', $filters['start_date'])
            ->whereDate('paid_visits.paid_at', '<=', $filters['end_date']);

        $this->applyStoreScope($query, $filters['store_id'], 'visit.store_id');

        return $query
            ->selectRaw('
                visit.store_id,
                product.id as product_id,
                COUNT(detail.id)::int as qty_sold,
                MAX(paid_visits.paid_at) as last_sold_at
            ')
            ->groupBy('visit.store_id', 'product.id');
    }

    private function ecommerceSales(array $filters)
    {
        $query = DB::table('ecommerce_order_details as eod')
            ->join('ecommerce_orders as eo', 'eo.id', '=', 'eod.ecommerce_order_id')
            ->join('payments as payment', 'payment.id', '=', 'eo.payment_id')
            ->join('products as product', 'product.id', '=', 'eod.product_id')
            ->whereNull('payment.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('payment.completed_at')
                    ->orWhereIn(DB::raw('LOWER(payment.status::text)'), ['paid', 'success', 'completed', 'settlement', 'capture']);
            })
            ->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
            ->whereRaw('COALESCE(payment.completed_at, eo.created_at)::date BETWEEN ? AND ?', [
                $filters['start_date'],
                $filters['end_date'],
            ]);

        $this->applyStoreScope($query, $filters['store_id'], 'eo.handled_by_store_id');

        return $query
            ->selectRaw("
                COALESCE(eo.handled_by_store_id::text, '') as store_id,
                product.id as product_id,
                SUM(COALESCE(eod.quantity, 1))::int as qty_sold,
                MAX(COALESCE(payment.completed_at, eo.created_at)) as last_sold_at
            ")
            ->groupByRaw("COALESCE(eo.handled_by_store_id::text, ''), product.id");
    }

    private function warehouseSales(array $filters)
    {
        $query = DB::table('warehouse_outbounds as wo')
            ->join('warehouse_outbound_details as detail', 'detail.warehouse_outbound_id', '=', 'wo.id')
            ->join('items as item', 'item.id', '=', 'detail.item_id')
            ->whereRaw('COALESCE(wo.released_at, wo.shipment_date, wo.created_at)::date BETWEEN ? AND ?', [
                $filters['start_date'],
                $filters['end_date'],
            ]);

        $this->applyWarehouseScope($query, $filters['warehouse_id'], 'wo.warehouse_id');

        return $query
            ->selectRaw('
                wo.warehouse_id,
                item.product_id as product_id,
                COUNT(detail.id)::int as qty_sold,
                MAX(COALESCE(wo.released_at, wo.shipment_date, wo.created_at)) as last_sold_at
            ')
            ->groupBy('wo.warehouse_id', 'item.product_id');
    }

    private function storeStock(?string $storeId)
    {
        $query = DB::table('store_items as si')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->whereIn('i.status', ['store_display', 'store_stock']);
            });

        $this->applyStoreScope($query, $storeId, 'si.store_id');

        return $query
            ->selectRaw('si.store_id, i.product_id, COUNT(si.id)::int as qty_stock')
            ->groupBy('si.store_id', 'i.product_id');
    }

    private function warehouseStock(?string $warehouseId)
    {
        $query = DB::table('warehouse_items as wi')
            ->join('items as i', function ($join) {
                $join->on('i.id', '=', 'wi.item_id')
                    ->whereIn('i.status', ['warehouse_stock', 'warehouse_processing']);
            });

        $this->applyWarehouseScope($query, $warehouseId, 'wi.warehouse_id');

        return $query
            ->selectRaw('wi.warehouse_id, i.product_id, COUNT(wi.id)::int as qty_stock')
            ->groupBy('wi.warehouse_id', 'i.product_id');
    }

    private function applyStoreScope($query, ?string $storeId, string $column): void
    {
        if ($storeId) {
            $query->where($column, $storeId);

            return;
        }

        if ($this->isBackoffice()) {
            return;
        }

        $ids = $this->accessibleStores()->pluck('id');

        if ($ids->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($column, $ids);
    }

    private function applyWarehouseScope($query, ?string $warehouseId, string $column): void
    {
        if ($warehouseId) {
            $query->where($column, $warehouseId);

            return;
        }

        if ($this->isBackoffice()) {
            return;
        }

        $ids = $this->accessibleWarehouses()->pluck('id');

        if ($ids->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($column, $ids);
    }

    private function paidVisits()
    {
        return DB::table('store_visit_payments as svp')
            ->join('payments as payment', 'payment.id', '=', 'svp.payment_id')
            ->whereNull('payment.deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('payment.completed_at')
                    ->orWhereIn(DB::raw('LOWER(payment.status::text)'), ['paid', 'success', 'completed', 'settlement']);
            })
            ->selectRaw('svp.visit_id, MIN(COALESCE(payment.completed_at, payment.created_at)) as paid_at')
            ->groupBy('svp.visit_id');
    }

    private function productMeta(): Collection
    {
        return DB::table('products as p')
            ->leftJoin('product_categories as category', 'category.id', '=', 'p.category_id')
            ->whereNull('p.deleted_at')
            ->get([
                'p.id',
                'p.code',
                'p.name',
                'p.category_id',
                'category.name as category_name',
            ])
            ->keyBy('id');
    }

    private function priceMap(string $priceType): Collection
    {
        return $this->productPriceQuery($priceType)->get()->pluck('price', 'product_id');
    }

    private function stores(): Collection
    {
        return $this->accessibleStores();
    }

    private function warehouses(): Collection
    {
        return $this->accessibleWarehouses();
    }

    private function isBackoffice(): bool
    {
        $user = auth()->user();

        return ! $user || (bool) $user->backoffice_access;
    }

    private function accessibleStores(): Collection
    {
        if ($this->accessibleStores !== null) {
            return $this->accessibleStores;
        }

        $user = auth()->user();

        if (! $user || $user->backoffice_access) {
            return $this->accessibleStores = DB::table('stores')
                ->when(Schema::hasColumn('stores', 'deleted_at'), function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $this->accessibleStores = $user->stores()
            ->when(Schema::hasColumn('stores', 'deleted_at'), function ($query) {
                $query->whereNull('stores.deleted_at');
            })
            ->orderBy('stores.name')
            ->get(['stores.id', 'stores.name']);
    }

    private function accessibleWarehouses(): Collection
    {
        if ($this->accessibleWarehouses !== null) {
            return $this->accessibleWarehouses;
        }

        $user = auth()->user();

        if (! $user || $user->backoffice_access) {
            return $this->accessibleWarehouses = DB::table('warehouses')
                ->when(Schema::hasColumn('warehouses', 'deleted_at'), function ($query) {
                    $query->whereNull('deleted_at');
                })
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return $this->accessibleWarehouses = $user->warehouses()
            ->when(Schema::hasColumn('warehouses', 'deleted_at'), function ($query) {
                $query->whereNull('warehouses.deleted_at');
            })
            ->orderBy('warehouses.name')
            ->get(['warehouses.id', 'warehouses.name']);
    }

    private function categories(): Collection
    {
        return DB::table('product_categories')
            ->when(Schema::hasColumn('product_categories', 'deleted_at'), function ($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function locationName(array $filters): string
    {
        if ($filters['mode'] === 'all') {
            return 'Semua Lokasi';
        }

        if ($filters['mode'] === 'warehouse') {
            return $filters['warehouse_id']
                ? (DB::table('warehouses')->where('id', $filters['warehouse_id'])->value('name') ?? '-')
                : 'Semua Gudang';
        }

        return $filters['store_id']
            ? (DB::table('stores')->where('id', $filters['store_id'])->value('name') ?? '-')
            : 'Semua Cabang';
    }

    private function buildTopProductChart(Collection $rows, string $quantityKey): Collection
    {
        return $rows
            ->groupBy('product_id')
            ->map(function (Collection $items) use ($quantityKey) {
                $first = $items->first();

                return (object) [
                    'product_code' => $first->product_code ?? '-',
                    'product_name' => $first->product_name ?? '-',
                    'quantity' => (int) $items->sum($quantityKey),
                    'total_nominal' => (float) $items->sum('stock_value'),
                ];
            })
            ->sortByDesc('quantity')
            ->take(12)
            ->values();
    }

    private function productPriceQuery(string $priceType)
    {
        if (
            ! Schema::hasTable('product_prices') ||
            ! Schema::hasColumn('product_prices', 'product_id') ||
            ! Schema::hasColumn('product_prices', 'price_type') ||
            ! Schema::hasColumn('product_prices', 'price')
        ) {
            return DB::table('products')
                ->selectRaw('
                    id as product_id,
                    ?::text as price_type,
                    0::numeric(18, 2) as price
                ', [$priceType])
                ->whereRaw('1 = 0');
        }

        return DB::table('product_prices as pp')
            ->selectRaw('
                DISTINCT ON (pp.product_id)
                pp.product_id,
                pp.price_type::text AS price_type,
                COALESCE(pp.price, 0)::numeric(18, 2) AS price
            ')
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

    private function normalizeDate(?string $date): ?string
    {
        if (! $date) {
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

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
