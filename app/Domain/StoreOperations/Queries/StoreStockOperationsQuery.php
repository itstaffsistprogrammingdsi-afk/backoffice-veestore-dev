<?php

namespace App\Domain\StoreOperations\Queries;

use App\Services\StockReservationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreStockOperationsQuery
{
    private const STATUS_LABELS = [
        'below' => 'Di bawah barrier',
        'near' => 'Mendekati barrier',
        'safe' => 'Aman',
        'no_barrier' => 'Tanpa barrier',
    ];

    private const STATUS_PRIORITIES = [
        'below' => 1,
        'near' => 2,
        'safe' => 3,
        'no_barrier' => 4,
    ];

    public function build(string $q, ?string $storeId): array
    {
        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'inventory_stock_treshold', 'display_stock_treshold']);

        $rows = $this->stockRows($q, $storeId);
        $barriers = $this->barrierRows($q, $storeId);

        $map = [];

        foreach ($rows as $row) {
            $map[$this->key($row->store_id, $row->product_id)] = [
                'store_id' => $row->store_id,
                'store_name' => $row->store_name,
                'store_code' => $row->store_code,
                'product_id' => $row->product_id,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'unit_name' => $row->unit_name,
                'on_hand' => (int) $row->on_hand,
                'inventory_qty' => (int) $row->inventory_qty,
                'display_qty' => (int) $row->display_qty,
                'reserved' => (int) $row->reserved,
                'available' => max(0, (int) $row->on_hand - (int) $row->reserved),
                'oldest_stock_at' => $row->oldest_stock_at,
                'earliest_expired_date' => $row->earliest_expired_date,
                'qty_0_30' => (int) $row->qty_0_30,
                'qty_31_90' => (int) $row->qty_31_90,
                'qty_91_180' => (int) $row->qty_91_180,
                'qty_181_365' => (int) $row->qty_181_365,
                'qty_gt_365' => (int) $row->qty_gt_365,
                'stock_barrier' => null,
                'tolerance_percentage' => null,
                'threshold' => null,
                'status' => 'no_barrier',
                'status_label' => self::STATUS_LABELS['no_barrier'],
                'status_priority' => self::STATUS_PRIORITIES['no_barrier'],
                'suggested_import_qty' => 0,
            ];
        }

        foreach ($barriers as $barrier) {
            $key = $this->key($barrier->store_id, $barrier->product_id);
            $base = $map[$key] ?? [
                'store_id' => $barrier->store_id,
                'store_name' => $barrier->store_name,
                'store_code' => $barrier->store_code,
                'product_id' => $barrier->product_id,
                'product_code' => $barrier->product_code,
                'product_name' => $barrier->product_name,
                'unit_name' => $barrier->unit_name,
                'on_hand' => 0,
                'inventory_qty' => 0,
                'display_qty' => 0,
                'reserved' => 0,
                'available' => 0,
                'oldest_stock_at' => null,
                'earliest_expired_date' => null,
                'qty_0_30' => 0,
                'qty_31_90' => 0,
                'qty_91_180' => 0,
                'qty_181_365' => 0,
                'qty_gt_365' => 0,
            ];

            $barrierQty = (int) $barrier->stock_barrier;
            $tolerance = (float) ($barrier->tolerance_percentage ?? 0);
            $threshold = (int) ceil($barrierQty * (1 + ($tolerance / 100)));
            $available = (int) $base['available'];
            $nearLimit = $threshold + max(1, (int) ceil($threshold * 0.25));

            if ($available <= $threshold) {
                $status = 'below';
                $suggested = max(1, $threshold - $available);
            } elseif ($available <= $nearLimit) {
                $status = 'near';
                $suggested = 0;
            } else {
                $status = 'safe';
                $suggested = 0;
            }

            $map[$key] = [
                ...$base,
                'stock_barrier' => $barrierQty,
                'tolerance_percentage' => $tolerance,
                'threshold' => $threshold,
                'status' => $status,
                'status_label' => self::STATUS_LABELS[$status],
                'status_priority' => self::STATUS_PRIORITIES[$status],
                'suggested_import_qty' => $suggested,
            ];
        }

        $velocity = $this->outboundVelocity($storeId);

        foreach ($map as $key => $row) {
            $outbound = (int) ($velocity[$key] ?? 0);
            $ageDays = $this->ageDays($row['oldest_stock_at']);
            $daysToExpiry = $this->daysToExpiry($row['earliest_expired_date']);
            $fefo = $this->fefoMeta($daysToExpiry);
            $movement = $this->movementMeta((int) $row['on_hand'], $outbound);

            $map[$key] = [
                ...$row,
                'oldest_age_days' => $ageDays,
                'days_to_expiry' => $daysToExpiry,
                'fefo_status' => $fefo['status'],
                'fefo_label' => $fefo['label'],
                'outbound_30d' => $outbound,
                'movement_category' => $movement['category'],
                'movement_label' => $movement['label'],
                'cover_days' => $movement['cover_days'],
            ];
        }

        $flatRows = collect(array_values($map))
            ->sortBy(fn (array $row) => [
                self::STATUS_PRIORITIES[$row['status']],
                mb_strtolower((string) $row['product_name']),
                mb_strtolower((string) $row['store_name']),
            ])
            ->values();

        $products = $flatRows
            ->groupBy('product_id')
            ->map(function (Collection $group) {
                $first = $group->first();
                $ordered = $group
                    ->sortBy(fn (array $row) => [
                        self::STATUS_PRIORITIES[$row['status']],
                        mb_strtolower((string) $row['store_name']),
                    ])
                    ->values();
                $worst = $ordered->first();

                return [
                    'product_id' => $first['product_id'],
                    'product_code' => $first['product_code'],
                    'product_name' => $first['product_name'],
                    'unit_name' => $first['unit_name'],
                    'total_on_hand' => $group->sum('on_hand'),
                    'total_inventory_qty' => $group->sum('inventory_qty'),
                    'total_display_qty' => $group->sum('display_qty'),
                    'total_reserved' => $group->sum('reserved'),
                    'total_available' => $group->sum('available'),
                    'store_count' => $group->where('on_hand', '>', 0)->count(),
                    'below_count' => $group->where('status', 'below')->count(),
                    'near_count' => $group->where('status', 'near')->count(),
                    'worst_status' => $worst['status'],
                    'worst_status_label' => $worst['status_label'],
                    'status_priority' => $worst['status_priority'],
                    'total_outbound_30d' => $group->sum('outbound_30d'),
                    'movement_category' => $this->movementMeta($group->sum('available'), $group->sum('outbound_30d'))['category'],
                    'movement_label' => $this->movementMeta($group->sum('available'), $group->sum('outbound_30d'))['label'],
                    'oldest_age_days' => $group->max('oldest_age_days'),
                    'earliest_expired_date' => $ordered->pluck('earliest_expired_date')->filter()->sort()->first(),
                    'fefo_status' => $ordered->sortBy(fn (array $row) => $this->fefoPriority($row['fefo_status']))->first()['fefo_status'],
                    'fefo_label' => $ordered->sortBy(fn (array $row) => $this->fefoPriority($row['fefo_status']))->first()['fefo_label'],
                    'stores' => $ordered->all(),
                ];
            })
            ->sortBy(fn (array $product) => [
                $product['status_priority'],
                mb_strtolower((string) $product['product_name']),
            ])
            ->values();

        $importNeeds = $flatRows
            ->whereIn('status', ['below', 'near'])
            ->sortBy(fn (array $row) => [
                self::STATUS_PRIORITIES[$row['status']],
                -$row['suggested_import_qty'],
                mb_strtolower((string) $row['product_name']),
                mb_strtolower((string) $row['store_name']),
            ])
            ->values();

        $storeThresholds = $this->storeThresholds($stores, $flatRows);
        $missingStock = $this->missingStock($q, $storeId);
        $expiring90 = $flatRows
            ->where('on_hand', '>', 0)
            ->filter(fn (array $row) => $row['days_to_expiry'] !== null && $row['days_to_expiry'] <= 90)
            ->count();
        $expired = $flatRows
            ->where('on_hand', '>', 0)
            ->filter(fn (array $row) => $row['days_to_expiry'] !== null && $row['days_to_expiry'] < 0)
            ->count();
        $showThreshold = $flatRows->contains(
            fn (array $row) => $row['threshold'] !== null && (int) $row['threshold'] !== (int) $row['stock_barrier']
        );

        return [
            'stores' => $stores,
            'products' => $products->all(),
            'importNeeds' => $importNeeds->all(),
            'missingStock' => $missingStock->all(),
            'storeThresholds' => $storeThresholds,
            'showThreshold' => $showThreshold,
            'summary' => [
                'sku_total' => $products->count(),
                'store_stock_count' => $flatRows
                    ->where('on_hand', '>', 0)
                    ->pluck('store_id')
                    ->unique()
                    ->count(),
                'below_barrier_count' => $flatRows->where('status', 'below')->count(),
                'near_barrier_count' => $flatRows->where('status', 'near')->count(),
                'import_product_count' => $importNeeds->pluck('product_id')->unique()->count(),
                'slow_product_count' => $products->where('movement_category', 'slow')->count(),
                'fast_product_count' => $products->where('movement_category', 'fast')->count(),
                'normal_product_count' => $products->where('movement_category', 'normal')->count(),
                'expiring_90_count' => $expiring90,
                'expired_count' => $expired,
                'missing_stock_qty' => (int) $missingStock->sum('qty'),
                'store_threshold_alert_count' => collect($storeThresholds)->where('status', 'below')->count(),
                'aging_buckets' => [
                    'bucket_0_30' => $flatRows->sum('qty_0_30'),
                    'bucket_31_90' => $flatRows->sum('qty_31_90'),
                    'bucket_91_180' => $flatRows->sum('qty_91_180'),
                    'bucket_181_365' => $flatRows->sum('qty_181_365'),
                    'bucket_gt_365' => $flatRows->sum('qty_gt_365'),
                ],
                'reconciliation' => $this->reconciliation($products, $storeId),
            ],
        ];
    }

    private function outboundVelocity(?string $storeId): array
    {
        return DB::table('store_outbound_details as wod')
            ->join('store_outbounds as wo', 'wo.id', '=', 'wod.store_outbound_id')
            ->join('items as i', 'i.id', '=', 'wod.item_id')
            ->where('wo.created_at', '>=', now()->subDays(30))
            ->when($storeId, fn ($query) => $query->where('wo.store_id', $storeId))
            ->selectRaw('wo.store_id, i.product_id, COUNT(wod.id)::int as qty')
            ->groupBy('wo.store_id', 'i.product_id')
            ->get()
            ->mapWithKeys(fn (object $row) => [
                $row->store_id.'|'.$row->product_id => (int) $row->qty,
            ])
            ->all();
    }

    private function ageDays(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        return max(0, (int) Carbon::parse($value)->startOfDay()->diffInDays(now()->startOfDay()));
    }

    private function daysToExpiry(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::parse($value)->startOfDay(), false);
    }

    private function fefoMeta(?int $daysToExpiry): array
    {
        if ($daysToExpiry === null) {
            return ['status' => 'no_expiry', 'label' => 'Tanpa expiry'];
        }

        if ($daysToExpiry < 0) {
            return ['status' => 'expired', 'label' => 'Expired'];
        }

        if ($daysToExpiry <= 30) {
            return ['status' => 'critical', 'label' => '≤30 hari'];
        }

        if ($daysToExpiry <= 90) {
            return ['status' => 'warning', 'label' => '≤90 hari'];
        }

        return ['status' => 'safe', 'label' => 'Aman'];
    }

    private function fefoPriority(string $status): int
    {
        return match ($status) {
            'expired' => 1,
            'critical' => 2,
            'warning' => 3,
            'safe' => 4,
            default => 5,
        };
    }

    private function movementMeta(int|float $available, int|float $outbound): array
    {
        $available = (int) $available;
        $outbound = (int) $outbound;

        if ($available <= 0) {
            return ['category' => 'no_stock', 'label' => 'Tidak ada stok', 'cover_days' => null];
        }

        if ($outbound <= 0) {
            return ['category' => 'slow', 'label' => 'Slow Moving', 'cover_days' => null];
        }

        $coverDays = (int) round(($available * 30) / $outbound);

        if ($coverDays <= 30) {
            return ['category' => 'fast', 'label' => 'Fast Moving', 'cover_days' => $coverDays];
        }

        if ($coverDays >= 90) {
            return ['category' => 'slow', 'label' => 'Slow Moving', 'cover_days' => $coverDays];
        }

        return ['category' => 'normal', 'label' => 'Normal', 'cover_days' => $coverDays];
    }

    private function storeThresholds(Collection $stores, Collection $flatRows): array
    {
        $storeMap = $stores->keyBy('id');
        $rows = [];

        foreach ($flatRows->groupBy('store_id') as $storeId => $items) {
            $store = $storeMap->get($storeId);

            if (! $store) {
                continue;
            }

            $inventoryQty = (int) $items->sum('inventory_qty');
            $displayQty = (int) $items->sum('display_qty');
            $inventoryThreshold = (int) ($store->inventory_stock_treshold ?? 0);
            $displayThreshold = (int) ($store->display_stock_treshold ?? 0);
            $inventoryBelow = $inventoryThreshold > 0 && $inventoryQty <= $inventoryThreshold;
            $displayBelow = $displayThreshold > 0 && $displayQty <= $displayThreshold;

            $rows[] = [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'store_code' => $store->code,
                'inventory_qty' => $inventoryQty,
                'inventory_threshold' => $inventoryThreshold,
                'display_qty' => $displayQty,
                'display_threshold' => $displayThreshold,
                'status' => ($inventoryBelow || $displayBelow) ? 'below' : 'safe',
                'status_label' => ($inventoryBelow || $displayBelow) ? 'Di bawah threshold' : 'Aman',
            ];
        }

        usort($rows, fn (array $a, array $b) => strcmp($a['store_name'], $b['store_name']));

        return $rows;
    }

    private function missingStock(string $q, ?string $storeId): Collection
    {
        return DB::table('items as i')
            ->leftJoin('store_items as wi', 'wi.item_id', '=', 'i.id')
            ->leftJoin('stores as w', 'w.id', '=', 'wi.store_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereRaw("LOWER(i.status::text) = 'missing'")
            ->when($storeId, fn ($query) => $query->where('wi.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.code', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                wi.store_id,
                COALESCE(w.name, \'Tanpa store\') as store_name,
                w.code as store_code,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(i.id)::int as qty,
                MAX(i.updated_at) as last_seen_at
            ')
            ->groupBy('wi.store_id', 'w.name', 'w.code', 'p.id', 'p.code', 'p.name')
            ->orderByDesc('qty')
            ->orderBy('p.name')
            ->get();
    }

    private function reconciliation(Collection $products, ?string $storeId): array
    {
        $stockProducts = $products
            ->filter(fn (array $product) => (int) $product['total_on_hand'] > 0)
            ->keyBy('product_id');

        $inboundProducts = DB::table('store_inbound_details as sid')
            ->join('store_inbounds as si', 'si.id', '=', 'sid.store_inbound_id')
            ->join('stores as s', 's.id', '=', 'si.store_id')
            ->join('items as i', 'i.id', '=', 'sid.item_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->whereNull('s.deleted_at')
            ->when($storeId, fn ($query) => $query->where('si.store_id', $storeId))
            ->selectRaw('
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                COUNT(sid.id)::int as inbound_qty,
                COUNT(DISTINCT sid.store_inbound_id)::int as document_count,
                MAX(si.received_date) as last_inbound_at
            ')
            ->groupBy('p.id', 'p.code', 'p.name')
            ->get()
            ->keyBy('product_id');

        $inboundOnly = $inboundProducts
            ->reject(fn (object $row) => $stockProducts->has($row->product_id))
            ->values()
            ->map(fn (object $row) => [
                'product_id' => $row->product_id,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'inbound_qty' => (int) $row->inbound_qty,
                'document_count' => (int) $row->document_count,
                'last_inbound_at' => $row->last_inbound_at,
            ])
            ->all();

        $stockOnly = $stockProducts
            ->reject(fn (array $row, string $id) => $inboundProducts->has($id))
            ->values()
            ->map(fn (array $row) => [
                'product_id' => $row['product_id'],
                'product_code' => $row['product_code'],
                'product_name' => $row['product_name'],
                'stock_qty' => (int) $row['total_on_hand'],
            ])
            ->all();

        return [
            'stock_sku_total' => $stockProducts->count(),
            'inbound_sku_total' => $inboundProducts->count(),
            'inbound_only_count' => count($inboundOnly),
            'stock_only_count' => count($stockOnly),
            'inbound_only' => $inboundOnly,
            'stock_only' => $stockOnly,
        ];
    }

    private function stockRows(string $q, ?string $storeId): LazyCollection
    {
        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');

        $query = DB::table('items as i')
            ->join('store_items as wi', 'wi.item_id', '=', 'i.id')
            ->join('stores as w', 'w.id', '=', 'wi.store_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('store_inbound_details as ind', 'ind.item_id', '=', 'i.id')
            ->leftJoin('store_inbounds as inh', 'inh.id', '=', 'ind.store_inbound_id')
            ->when($hasUnits, fn ($query) => $query->leftJoin('units as u', 'u.id', '=', 'p.unit_id'))
            ->leftJoinSub(
                app(StockReservationService::class)->remainingByLocation('store'),
                'sr',
                function ($join) {
                    $join->on('sr.location_id', '=', 'wi.store_id')
                        ->on('sr.product_id', '=', 'i.product_id');
                }
            )
            ->whereNull('w.deleted_at')
            ->whereIn(DB::raw('LOWER(i.status::text)'), ['store_stock', 'store_display'])
            ->when($storeId, fn ($query) => $query->where('wi.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.code', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                wi.store_id,
                w.name as store_name,
                w.code as store_code,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name
            ')
            ->when(
                $hasUnits,
                fn ($query) => $query->selectRaw("COALESCE(u.name, '') as unit_name"),
                fn ($query) => $query->selectRaw("'' as unit_name")
            )
            ->selectRaw('
                COUNT(i.id)::int as on_hand,
                SUM(CASE WHEN LOWER(i.status::text) = \'store_stock\' THEN 1 ELSE 0 END)::int as inventory_qty,
                SUM(CASE WHEN LOWER(i.status::text) = \'store_display\' THEN 1 ELSE 0 END)::int as display_qty,
                COALESCE(MAX(sr.qty_reserved), 0)::int as reserved,
                MIN(COALESCE(inh.received_date, i.created_at)) as oldest_stock_at,
                MIN(i.expired_date) as earliest_expired_date,
                SUM(CASE WHEN COALESCE(inh.received_date, i.created_at) >= NOW() - INTERVAL \'30 days\' THEN 1 ELSE 0 END)::int as qty_0_30,
                SUM(CASE WHEN COALESCE(inh.received_date, i.created_at) < NOW() - INTERVAL \'30 days\' AND COALESCE(inh.received_date, i.created_at) >= NOW() - INTERVAL \'90 days\' THEN 1 ELSE 0 END)::int as qty_31_90,
                SUM(CASE WHEN COALESCE(inh.received_date, i.created_at) < NOW() - INTERVAL \'90 days\' AND COALESCE(inh.received_date, i.created_at) >= NOW() - INTERVAL \'180 days\' THEN 1 ELSE 0 END)::int as qty_91_180,
                SUM(CASE WHEN COALESCE(inh.received_date, i.created_at) < NOW() - INTERVAL \'180 days\' AND COALESCE(inh.received_date, i.created_at) >= NOW() - INTERVAL \'365 days\' THEN 1 ELSE 0 END)::int as qty_181_365,
                SUM(CASE WHEN COALESCE(inh.received_date, i.created_at) < NOW() - INTERVAL \'365 days\' THEN 1 ELSE 0 END)::int as qty_gt_365
            ')
            ->groupBy(
                'wi.store_id',
                'w.name',
                'w.code',
                'p.id',
                'p.code',
                'p.name',
                ...($hasUnits ? ['u.name'] : [])
            )
            ->orderBy('w.name')
            ->orderBy('p.name');

        return $query->cursor();
    }

    private function barrierRows(string $q, ?string $storeId): LazyCollection
    {
        $hasUnits = Schema::hasTable('units') && Schema::hasColumn('products', 'unit_id');

        return DB::table('store_stock_barriers as b')
            ->join('products as p', 'p.id', '=', 'b.product_id')
            ->join('stores as w', 'w.id', '=', 'b.store_id')
            ->when($hasUnits, fn ($query) => $query->leftJoin('units as u', 'u.id', '=', 'p.unit_id'))
            ->whereNull('w.deleted_at')
            ->when($storeId, fn ($query) => $query->where('b.store_id', $storeId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('p.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.name', 'ILIKE', "%{$q}%")
                        ->orWhere('w.code', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw('
                b.store_id,
                w.name as store_name,
                w.code as store_code,
                p.id as product_id,
                p.code as product_code,
                p.name as product_name,
                b.stock_barrier,
                b.tolerance_percentage
            ')
            ->when(
                $hasUnits,
                fn ($query) => $query->selectRaw("COALESCE(u.name, '') as unit_name"),
                fn ($query) => $query->selectRaw("'' as unit_name")
            )
            ->cursor();
    }

    private function key(string $storeId, string $productId): string
    {
        return $storeId.'|'.$productId;
    }
}
