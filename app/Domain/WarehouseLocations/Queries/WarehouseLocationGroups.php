<?php

namespace App\Domain\WarehouseLocations\Queries;

use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseLocationGroups
{
    private const TYPE_ORDER = [
        'warehouse' => 1,
        'room' => 2,
        'store_display' => 2,
        'staging' => 3,
        'rack' => 4,
        'layer' => 5,
    ];

    private const TYPE_LABELS = [
        'warehouse' => 'Warehouse',
        'room' => 'Room',
        'store_display' => 'Store Display',
        'staging' => 'Staging',
        'rack' => 'Rack',
        'layer' => 'Layer',
    ];

    public function build(string $q, ?string $warehouseId, ?string $type): array
    {
        $warehouses = Warehouse::query()
            ->when($warehouseId, fn ($query) => $query->whereKey($warehouseId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address']);

        if ($warehouses->isEmpty()) {
            return [];
        }

        $locations = DB::table('locations as l')
            ->leftJoin('warehouse_locations as wl', 'wl.location_id', '=', 'l.id')
            ->whereNull('l.deleted_at')
            ->whereIn('l.type', array_keys(self::TYPE_ORDER))
            ->whereIn('wl.warehouse_id', $warehouses->pluck('id'))
            ->select(
                'l.id',
                'l.name',
                'l.code',
                'l.type',
                'l.location_parent_id',
                'wl.warehouse_id'
            )
            ->get()
            ->groupBy('warehouse_id');

        $groups = [];

        foreach ($warehouses as $warehouse) {
            $group = $this->buildGroup(
                $warehouse,
                $locations->get($warehouse->id, collect()),
                $q,
                $type
            );

            if ($group !== null) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    private function buildGroup(
        Warehouse $warehouse,
        Collection $locations,
        string $q,
        ?string $type
    ): ?array {
        $locations = $locations->unique('id')->values();
        $root = $locations->firstWhere('type', 'warehouse');
        $nodes = $locations->where('type', '!=', 'warehouse')->values();
        $warehouseMatches = $q !== '' && (
            mb_stripos((string) $warehouse->name, $q) !== false
            || mb_stripos((string) $warehouse->code, $q) !== false
        );

        $matches = $nodes->filter(function (object $node) use ($q, $type, $warehouseMatches) {
            if ($type && $node->type !== $type) {
                return false;
            }

            if ($q === '' || $warehouseMatches) {
                return true;
            }

            return mb_stripos((string) $node->name, $q) !== false
                || mb_stripos((string) $node->code, $q) !== false;
        });

        if ($matches->isEmpty() && ($q !== '' || $type !== null) && ! $warehouseMatches) {
            return null;
        }

        $byId = $nodes->keyBy('id');
        $keep = [];

        foreach ($matches as $node) {
            $current = $node;

            while ($current) {
                $keep[$current->id] = true;
                $current = $byId->get($current->location_parent_id);
            }
        }

        $childrenByParent = $nodes->groupBy(fn (object $node) => (string) $node->location_parent_id);
        $rootId = $root?->id;
        $rows = [];

        $flatten = function (string $parentId, int $depth, array $parentNames) use (
            &$flatten,
            &$rows,
            $childrenByParent,
            $keep
        ): void {
            $children = $childrenByParent
                ->get($parentId, collect())
                ->sortBy(fn (object $node) => [
                    self::TYPE_ORDER[$node->type] ?? 99,
                    mb_strtolower((string) $node->name),
                ]);

            foreach ($children as $node) {
                if (! isset($keep[$node->id])) {
                    continue;
                }

                $names = [...$parentNames, $node->name];
                $rows[] = [
                    'id' => $node->id,
                    'name' => $node->name,
                    'code' => $node->code,
                    'type' => $node->type,
                    'type_label' => self::TYPE_LABELS[$node->type] ?? ucfirst($node->type),
                    'parent_id' => $node->location_parent_id,
                    'parent_name' => $parentNames === [] ? null : $parentNames[array_key_last($parentNames)],
                    'depth' => $depth,
                    'path' => implode(' / ', $names),
                    'has_children' => $childrenByParent->has($node->id),
                    'qr_url' => route('master-data.locations-warehouse.qr', ['location' => $node->id]),
                ];

                $flatten($node->id, $depth + 1, $names);
            }
        };

        $topLevelParent = $rootId ?? '';
        $flatten($topLevelParent, 0, []);

        $validParentIds = $byId->keys()->push($topLevelParent)->all();
        $orphans = $nodes->filter(fn (object $node) => ! in_array((string) $node->location_parent_id, $validParentIds, true));

        foreach ($orphans as $orphan) {
            if (! isset($keep[$orphan->id])) {
                continue;
            }

            $rows[] = [
                'id' => $orphan->id,
                'name' => $orphan->name,
                'code' => $orphan->code,
                'type' => $orphan->type,
                'type_label' => self::TYPE_LABELS[$orphan->type] ?? ucfirst($orphan->type),
                'parent_id' => $orphan->location_parent_id,
                'parent_name' => null,
                'depth' => 0,
                'path' => $orphan->name,
                'has_children' => false,
                'qr_url' => route('master-data.locations-warehouse.qr', ['location' => $orphan->id]),
            ];
        }

        return [
            'id' => $warehouse->id,
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'address' => $warehouse->address,
            'root_id' => $rootId,
            'root_code' => $root?->code,
            'stats' => [
                'rooms' => $nodes->whereIn('type', ['room', 'store_display'])->count(),
                'staging' => $nodes->where('type', 'staging')->count(),
                'racks' => $nodes->where('type', 'rack')->count(),
                'layers' => $nodes->where('type', 'layer')->count(),
                'total' => $nodes->count(),
            ],
            'parentOptions' => [
                'staging' => $this->parentOptions($nodes->whereIn('type', ['room', 'store_display'])),
                'rack' => $this->parentOptions($nodes->whereIn('type', ['room', 'store_display'])),
                'layer' => $this->parentOptions($nodes->whereIn('type', ['rack', 'staging'])),
            ],
            'rows' => $rows,
        ];
    }

    private function parentOptions(Collection $nodes): array
    {
        return $nodes
            ->sortBy(fn (object $node) => [self::TYPE_ORDER[$node->type] ?? 99, mb_strtolower((string) $node->name)])
            ->map(fn (object $node) => [
                'id' => $node->id,
                'name' => $node->name,
                'code' => $node->code,
                'type' => $node->type,
                'type_label' => self::TYPE_LABELS[$node->type] ?? ucfirst($node->type),
            ])
            ->values()
            ->all();
    }
}
