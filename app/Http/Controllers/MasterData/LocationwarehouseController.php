<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LocationwarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-locations-warehouse')->only(['index', 'treeData', 'qr']);
        $this->middleware('permission:create master-data-locations-warehouse')->only(['store']);
        $this->middleware('permission:update master-data-locations-warehouse')->only(['update']);
        $this->middleware('permission:delete master-data-locations-warehouse')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $type = $request->get('type');

        $locations = Location::query()
            ->with('parent:id,name')
            ->where('type', '!=', 'store')
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ILIKE', "%{$q}%")->orWhere('code', 'ILIKE', "%{$q}%");
            }))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $types = ['warehouse', 'room', 'store_display', 'rack', 'staging', 'layer'];
        $stores = Store::select('id', 'name')->orderBy('name')->get();
        $warehouses = Warehouse::select('id', 'name')->orderBy('name')->get();
        $parentOptions = Location::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('MasterData/SimpleCrud', [
            'title' => 'Location Warehouse',
            'description' => 'Kelola lokasi gudang.',
            'searchPlaceholder' => 'Cari lokasi...',
            'items' => $locations,
            'columns' => [
                ['key' => 'name', 'label' => 'Nama'],
                ['key' => 'code', 'label' => 'Kode'],
                ['key' => 'type', 'label' => 'Tipe'],
                ['key' => 'parent.name', 'label' => 'Parent'],
            ],
            'fields' => [
                ['key' => 'name', 'label' => 'Nama Lokasi', 'required' => true],
                ['key' => 'code', 'label' => 'Kode', 'required' => true],
                ['key' => 'type', 'label' => 'Tipe', 'type' => 'select', 'required' => true, 'options' => collect($types)->map(fn ($type) => ['value' => $type, 'label' => $type])->values()->all()],
                ['key' => 'location_parent_id', 'label' => 'Parent', 'type' => 'select', 'options' => $parentOptions->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])->values()->all()],
                ['key' => 'warehouse_id', 'label' => 'Warehouse', 'type' => 'select', 'options' => $warehouses->map(fn ($warehouse) => ['value' => $warehouse->id, 'label' => $warehouse->name])->values()->all()],
            ],
            'filters' => ['q' => $q],
            'urls' => [
                'index' => route('master-data.locations-warehouse.index'),
                'store' => route('master-data.locations-warehouse.store'),
                'update' => route('master-data.locations-warehouse.update', ['location' => '__ID__']),
                'destroy' => route('master-data.locations-warehouse.destroy', ['location' => '__ID__']),
            ],
        ]);
    }

    public function treeData(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $type = $request->get('type');

        $all = Location::select('id', 'name', 'code', 'type', 'location_parent_id')
            ->orderByRaw($this->orderByTypeCase())
            ->orderBy('name')
            ->get();

        $byId = $all->map(fn ($x) => [
            'id' => $x->id,
            'name' => $x->name,
            'code' => $x->code,
            'type' => $x->type,
            'location_parent_id' => $x->location_parent_id,
        ])->keyBy('id')->toArray();

        $childrenMap = $all->groupBy('location_parent_id');

        /*
         * Ambil nama Warehouse dari pivot warehouse_locations.
         * Child juga akan punya pivot warehouse_locations via attach/sync.
         */
        $warehouseLocationMap = DB::table('warehouse_locations as wl')
            ->join('warehouses as w', 'w.id', '=', 'wl.warehouse_id')
            ->select(
                'wl.location_id',
                'wl.warehouse_id',
                'w.name as warehouse_name'
            )
            ->get()
            ->keyBy('location_id');

        $ownerOf = function (?string $id) use ($byId) {
            while ($id && isset($byId[$id])) {
                $n = $byId[$id];

                if (in_array($n['type'], ['warehouse', 'store'], true)) {
                    return $n;
                }

                $id = $n['location_parent_id'];
            }

            return null;
        };

        $warehouseMetaOf = function (?string $locationId) use ($warehouseLocationMap, $ownerOf) {
            if (! $locationId) {
                return [
                    'warehouse_id' => null,
                    'warehouse_name' => null,
                ];
            }

            $direct = $warehouseLocationMap->get($locationId);

            if ($direct) {
                return [
                    'warehouse_id' => $direct->warehouse_id,
                    'warehouse_name' => $direct->warehouse_name,
                ];
            }

            $owner = $ownerOf($locationId);

            if ($owner && ($owner['type'] ?? null) === 'warehouse') {
                $ownerWarehouse = $warehouseLocationMap->get($owner['id']);

                if ($ownerWarehouse) {
                    return [
                        'warehouse_id' => $ownerWarehouse->warehouse_id,
                        'warehouse_name' => $ownerWarehouse->warehouse_name,
                    ];
                }

                return [
                    'warehouse_id' => null,
                    'warehouse_name' => $owner['name'] ?? null,
                ];
            }

            return [
                'warehouse_id' => null,
                'warehouse_name' => null,
            ];
        };

        $matchedIds = $all->filter(function ($loc) use ($q, $type, $ownerOf, $warehouseMetaOf) {
            $ok = true;

            if ($q !== '') {
                $qLower = mb_strtolower($q);
                $warehouseMeta = $warehouseMetaOf($loc->id);
                $warehouseName = $warehouseMeta['warehouse_name'] ?? '';

                $ok = (mb_stripos(mb_strtolower($loc->name), $qLower) !== false)
                   || (mb_stripos(mb_strtolower($loc->code), $qLower) !== false)
                   || (mb_stripos(mb_strtolower($warehouseName), $qLower) !== false);
            }

            if ($ok && $type) {
                $ok = $loc->type === $type;
            }

            if (! $ok) {
                return false;
            }

            if ($loc->type === 'warehouse') {
                return true;
            }

            $owner = $ownerOf($loc->id);

            return $owner && ($owner['type'] === 'warehouse');
        })->pluck('id')->flip();

        $shouldInclude = function ($id) use (&$shouldInclude, $matchedIds, $childrenMap, $ownerOf) {
            $owner = $ownerOf($id);

            if (! $owner || $owner['type'] !== 'warehouse') {
                return false;
            }

            if (isset($matchedIds[$id])) {
                return true;
            }

            foreach ($childrenMap->get($id, []) as $child) {
                if ($shouldInclude($child->id)) {
                    return true;
                }
            }

            return false;
        };

        $buildTree = function ($parentId) use (&$buildTree, $childrenMap, $shouldInclude, $ownerOf, $warehouseMetaOf) {
            $nodes = [];

            foreach ($childrenMap->get($parentId, []) as $loc) {
                if (! $shouldInclude($loc->id)) {
                    continue;
                }

                $owner = $ownerOf($loc->id);
                $warehouseMeta = $warehouseMetaOf($loc->id);

                $node = [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'code' => $loc->code,
                    'type' => $loc->type,
                    'location_parent_id' => $loc->location_parent_id,
                    'owner_root_id' => $owner['id'] ?? null,
                    'owner_root_type' => $owner['type'] ?? null,

                    // Tambahan untuk kolom Warehouse dan modal edit
                    'warehouse_id' => $warehouseMeta['warehouse_id'] ?? null,
                    'warehouse_name' => $warehouseMeta['warehouse_name'] ?? null,
                ];

                $children = $buildTree($loc->id);

                if (! empty($children)) {
                    $node['_children'] = $children;
                }

                $nodes[] = $node;
            }

            return $nodes;
        };

        return response()->json($buildTree(null));
    }

    public function store(Request $request)
    {
        $formFrom = $request->input('_form', 'create');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'type' => 'required|in:warehouse,room,store_display,rack,staging,layer',
            'location_parent_id' => 'nullable|uuid|exists:locations,id',
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id', Rule::requiredIf(fn () => $request->type === 'warehouse')],
            'store_id' => ['nullable', 'uuid', 'exists:stores,id'],
            '_form' => 'nullable|in:create,quick_add',
        ]);

        $this->enforceParentRules($data['type'], $data['location_parent_id'] ?? null);

        DB::transaction(function () use ($data) {
            $warehouseId = $this->resolveTargetWarehouseId(
                $data['type'],
                $data['location_parent_id'] ?? null,
                $data['warehouse_id'] ?? null
            );

            if (! $warehouseId) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Warehouse tujuan tidak dapat ditemukan.',
                ]);
            }

            /*
             * Scope unik mengikuti struktur:
             * - Warehouse: dalam warehouse fisik yang sama.
             * - Room/Store Display: dalam warehouse yang sama.
             * - Rack/Staging: dalam parent Room/Store Display yang sama.
             * - Layer: dalam parent Rack/Staging yang sama.
             */
            $scope = $this->resolveUniquenessScope(
                $data['type'],
                $data['location_parent_id'] ?? null,
                $warehouseId
            );

            $this->lockScopedValue($scope, $data['type'], 'name', $data['name']);
            $this->lockScopedValue($scope, $data['type'], 'code', $data['code']);

            $this->enforceScopedUnique(
                $scope,
                $data['type'],
                'name',
                $data['name']
            );

            $this->enforceScopedUnique(
                $scope,
                $data['type'],
                'code',
                $data['code']
            );

            $location = Location::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'],
                'location_parent_id' => $data['location_parent_id'] ?? null,
            ]);

            if ($data['type'] === 'warehouse') {
                DB::table('warehouse_locations')->insert([
                    'location_id' => $location->id,
                    'warehouse_id' => $data['warehouse_id'],
                ]);
            }

            // Lokasi turunan mengikuti warehouse_id dari Warehouse induknya.
            $this->attachPivotToTopOwner($location);
            $this->syncRfidTag($location);
        });

        $typeLabel = $this->locationTypeLabel($data['type']);

        return $this->redirectIndex()
            ->with(
                'success',
                "{$typeLabel} \"{$data['name']}\" berhasil ditambahkan."
            )
            ->withInput(['_form' => $formFrom]);
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'type' => 'required|in:warehouse,room,store_display,rack,staging,layer',
            'location_parent_id' => 'nullable|uuid|exists:locations,id|not_in:'.$location->id,
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id', Rule::requiredIf(fn () => $request->type === 'warehouse')],
            'store_id' => ['nullable', 'uuid', 'exists:stores,id'],
            '_form' => 'nullable|in:edit',
            'id' => ['nullable', 'uuid', Rule::in([$location->id])],
            'owner_root_id' => 'nullable|uuid',
        ]);

        $this->enforceParentRules($data['type'], $data['location_parent_id'] ?? null);

        DB::transaction(function () use ($location, $data) {
            $warehouseId = $this->resolveTargetWarehouseId(
                $data['type'],
                $data['location_parent_id'] ?? null,
                $data['warehouse_id'] ?? null
            );

            if (! $warehouseId) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Warehouse tujuan tidak dapat ditemukan.',
                ]);
            }

            $scope = $this->resolveUniquenessScope(
                $data['type'],
                $data['location_parent_id'] ?? null,
                $warehouseId
            );

            $this->lockScopedValue($scope, $data['type'], 'name', $data['name']);
            $this->lockScopedValue($scope, $data['type'], 'code', $data['code']);

            $this->enforceScopedUnique(
                $scope,
                $data['type'],
                'name',
                $data['name'],
                $location->id
            );

            $this->enforceScopedUnique(
                $scope,
                $data['type'],
                'code',
                $data['code'],
                $location->id
            );

            $lockedLocation = Location::query()
                ->whereKey($location->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLocation->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'],
                'location_parent_id' => $data['location_parent_id'] ?? null,
            ]);

            /*
             * Setelah locations di-update, pivot warehouse_locations juga harus ikut update.
             * - Type warehouse: update warehouse_id untuk location ini dan semua child.
             * - Type child: update warehouse_id sesuai owner warehouse dari parent terbaru.
             */
            $lockedLocation->refresh();

            if ($lockedLocation->type === 'warehouse') {
                $this->syncWarehousePivotToSubtree(
                    $lockedLocation,
                    $data['warehouse_id']
                );
            } else {
                $this->syncCurrentOwnerPivotToSubtree($lockedLocation);
            }

            $this->syncRfidTagToSubtree($lockedLocation);
        });

        $typeLabel = $this->locationTypeLabel($data['type']);

        return $this->redirectIndex()->with(
            'success',
            "{$typeLabel} \"{$data['name']}\" berhasil diperbarui."
        );
    }

    public function destroy(Location $location)
    {
        DB::transaction(function () use ($location) {
            DB::table('warehouse_locations')->where('location_id', $location->id)->delete();
            DB::table('store_locations')->where('location_id', $location->id)->delete();
            DB::table('location_rfid_tags')->where('location_id', $location->id)->delete();
            $location->delete();
        });

        return back()->with('success', 'Location berhasil dihapus.');
    }

    public function qr(Location $location)
    {
        if (in_array($location->type, ['warehouse', 'store'], true)) {
            abort(404, 'QR tidak tersedia untuk tipe warehouse/store.');
        }

        $this->syncRfidTag($location);

        $value = DB::table('location_rfid_tags')
            ->where('location_id', $location->id)
            ->value('value');

        if (! $value) {
            abort(404, 'RFID/QR tidak tersedia untuk lokasi ini.');
        }

        $svg = QrCode::format('svg')->size(260)->margin(1)->generate($value);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function redirectIndex()
    {
        return app('router')->has('master-data.locations-warehouse.index')
            ? redirect()->route('master-data.locations-warehouse.index')
            : redirect(url('master-data/locations-warehouse'));
    }

    private function orderByTypeCase(): string
    {
        return "
            CASE type
              WHEN 'warehouse'     THEN 1
              WHEN 'store'         THEN 2
              WHEN 'room'          THEN 3
              WHEN 'store_display' THEN 3
              WHEN 'rack'          THEN 4
              WHEN 'staging'       THEN 4
              WHEN 'layer'         THEN 5
              ELSE 99
            END
        ";
    }

    private function enforceParentRules(string $type, ?string $parentId): void
    {
        $type = strtolower($type);

        if ($type === 'warehouse') {
            if (! empty($parentId)) {
                abort(422, 'Parent harus kosong untuk tipe Warehouse.');
            }

            return;
        }

        if ($type === 'room') {
            if (empty($parentId)) {
                abort(422, 'Parent WAREHOUSE wajib diisi untuk tipe Room.');
            }
            $p = Location::select('id', 'type')->find($parentId);
            if (! $p || $p->type !== 'warehouse') {
                abort(422, 'Parent untuk Room harus WAREHOUSE.');
            }

            return;
        }

        if ($type === 'store_display') {
            if (empty($parentId)) {
                abort(422, 'Parent WAREHOUSE wajib diisi untuk tipe Store Display.');
            }
            $p = Location::select('id', 'type')->find($parentId);
            if (! $p || $p->type !== 'warehouse') {
                abort(422, 'Parent untuk Store Display harus WAREHOUSE.');
            }

            return;
        }

        if ($type === 'rack' || $type === 'staging') {
            if (empty($parentId)) {
                abort(422, 'Parent ROOM/STORE DISPLAY wajib diisi.');
            }
            $p = Location::select('id', 'type')->find($parentId);
            if (! $p || ! in_array($p->type, ['room', 'store_display'], true)) {
                abort(422, 'Parent harus ROOM atau STORE DISPLAY.');
            }

            return;
        }

        if ($type === 'layer') {
            if (empty($parentId)) {
                abort(422, 'Parent RACK/STAGING wajib diisi.');
            }
            $p = Location::select('id', 'type')->find($parentId);
            if (! $p || ! in_array($p->type, ['rack', 'staging'], true)) {
                abort(422, 'Parent untuk Layer harus RACK atau STAGING.');
            }

            return;
        }
    }

    /**
     * Ambil warehouse_id dari parent dengan cara:
     * - cari ancestor location type warehouse
     * - ambil warehouse_id dari pivot warehouse_locations
     */
    private function resolveWarehouseIdFromParent(?string $parentId): ?string
    {
        $owner = $this->findAncestorByTypes($parentId, ['warehouse']);
        if (! $owner) {
            return null;
        }

        return DB::table('warehouse_locations')
            ->where('location_id', $owner->id)
            ->value('warehouse_id');
    }

    /**
     * Menentukan Warehouse fisik yang menjadi scope validasi.
     *
     * - Lokasi type warehouse memakai warehouse_id dari form.
     * - Room, Rack, Staging, Layer, dan Store Display mengikuti Warehouse
     *   dari lokasi induknya.
     */
    private function resolveTargetWarehouseId(
        string $type,
        ?string $parentId,
        ?string $selectedWarehouseId
    ): ?string {
        if (strtolower($type) === 'warehouse') {
            return $selectedWarehouseId ?: null;
        }

        return $this->resolveWarehouseIdFromParent($parentId);
    }

    /**
     * Menentukan ruang lingkup keunikan berdasarkan hierarki lokasi.
     *
     * - warehouse      : warehouse fisik yang dipilih.
     * - room           : warehouse fisik yang sama.
     * - store_display  : warehouse fisik yang sama.
     * - rack/staging   : parent Room/Store Display yang sama.
     * - layer          : parent Rack/Staging yang sama.
     */
    private function resolveUniquenessScope(
        string $type,
        ?string $parentId,
        string $warehouseId
    ): array {
        $type = strtolower($type);

        if (in_array($type, ['rack', 'staging', 'layer'], true)) {
            if (! $parentId) {
                throw ValidationException::withMessages([
                    'location_parent_id' => 'Parent lokasi wajib diisi.',
                ]);
            }

            return [
                'kind' => 'parent',
                'id' => $parentId,
            ];
        }

        return [
            'kind' => 'warehouse',
            'id' => $warehouseId,
        ];
    }

    /**
     * Validasi nama/kode sesuai scope hierarki.
     * Tipe lokasi ikut dibandingkan agar nama Room tidak bentrok dengan Rack.
     */
    private function enforceScopedUnique(
        array $scope,
        string $type,
        string $field,
        string $value,
        ?string $ignoreId = null
    ): void {
        if (! in_array($field, ['name', 'code'], true)) {
            throw new \InvalidArgumentException('Field unik tidak didukung.');
        }

        $type = strtolower($type);
        $normalizedValue = $this->normalizeComparable($value);

        $query = DB::table('locations as l')
            ->where('l.type', $type)
            ->whereNull('l.deleted_at');

        if (($scope['kind'] ?? null) === 'parent') {
            $query->where('l.location_parent_id', $scope['id']);
        } else {
            $query->join(
                'warehouse_locations as wl',
                'wl.location_id',
                '=',
                'l.id'
            )->where('wl.warehouse_id', $scope['id']);
        }

        $query->whereRaw(
            "LOWER(REGEXP_REPLACE(TRIM(l.{$field}), '\\s+', ' ', 'g')) = ?",
            [$normalizedValue]
        );

        if ($ignoreId) {
            $query->where('l.id', '!=', $ignoreId);
        }

        if (! $query->exists()) {
            return;
        }

        $fieldLabel = $field === 'name' ? 'Nama' : 'Kode';
        $typeLabel = match ($type) {
            'warehouse' => 'Warehouse',
            'room' => 'Room',
            'store_display' => 'Store Display',
            'rack' => 'Rack',
            'staging' => 'Staging',
            'layer' => 'Layer',
            default => ucfirst($type),
        };

        $scopeLabel = ($scope['kind'] ?? null) === 'parent'
            ? 'induk yang sama'
            : 'Warehouse yang sama';

        throw ValidationException::withMessages([
            $field => "{$fieldLabel} {$typeLabel} sudah digunakan pada {$scopeLabel}.",
        ]);
    }

    /**
     * Advisory lock PostgreSQL agar request bersamaan tidak lolos validasi.
     */
    private function lockScopedValue(
        array $scope,
        string $type,
        string $field,
        string $value
    ): void {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $lockKey = implode(':', [
            'warehouse-location',
            $scope['kind'] ?? 'unknown',
            $scope['id'] ?? 'unknown',
            strtolower($type),
            $field,
            $this->normalizeComparable($value),
        ]);

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            [$lockKey]
        );
    }

    private function locationTypeLabel(string $type): string
    {
        return match (strtolower($type)) {
            'warehouse' => 'Warehouse',
            'room' => 'Room',
            'store_display' => 'Store Display',
            'rack' => 'Rack',
            'staging' => 'Staging',
            'layer' => 'Layer',
            default => ucfirst($type),
        };
    }

    private function normalizeComparable(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }

    private function findOwnerRootFromMap(array $byId, ?string $id): ?array
    {
        while ($id && isset($byId[$id])) {
            $n = $byId[$id];
            if (in_array($n['type'], ['warehouse', 'store'], true)) {
                return $n;
            }
            $id = $n['location_parent_id'];
        }

        return null;
    }

    private function findAncestorByTypes(?string $parentId, array $types): ?Location
    {
        if (! $parentId) {
            return null;
        }
        $wanted = array_map('strtolower', $types);

        while ($parentId) {
            $p = Location::select('id', 'type', 'location_parent_id')->find($parentId);
            if (! $p) {
                return null;
            }
            if (in_array(strtolower($p->type), $wanted, true)) {
                return $p;
            }
            $parentId = $p->location_parent_id;
        }

        return null;
    }

    private function attachPivotToTopOwner(Location $loc): void
    {
        if (! in_array($loc->type, ['room', 'store_display', 'rack', 'staging', 'layer'], true)) {
            return;
        }

        $owner = $this->findAncestorByTypes($loc->location_parent_id, ['warehouse', 'store']);
        if (! $owner) {
            return;
        }

        DB::table('warehouse_locations')->where('location_id', $loc->id)->delete();
        DB::table('store_locations')->where('location_id', $loc->id)->delete();

        if ($owner->type === 'warehouse') {
            $wid = DB::table('warehouse_locations')->where('location_id', $owner->id)->value('warehouse_id');
            if ($wid) {
                DB::table('warehouse_locations')->insert([
                    'location_id' => $loc->id,
                    'warehouse_id' => $wid,
                ]);
            }
        } else {
            $sid = DB::table('store_locations')->where('location_id', $owner->id)->value('store_id');
            if ($sid) {
                DB::table('store_locations')->insert([
                    'location_id' => $loc->id,
                    'store_id' => $sid,
                ]);
            }
        }
    }

    private function syncRfidTag(Location $loc): void
    {
        $needsTag = ! in_array($loc->type, ['warehouse', 'store'], true);

        if ($needsTag) {
            $exists = DB::table('location_rfid_tags')->where('location_id', $loc->id)->exists();
            if (! $exists) {
                DB::table('location_rfid_tags')->insert([
                    'value' => (string) Str::uuid(),
                    'location_id' => $loc->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('location_rfid_tags')
                    ->where('location_id', $loc->id)
                    ->update(['updated_at' => now()]);
            }
        } else {
            DB::table('location_rfid_tags')->where('location_id', $loc->id)->delete();
        }
    }

    private function getDescendantLocationIds(string $rootId): array
    {
        $ids = [];
        $parents = [$rootId];

        while (! empty($parents)) {
            $children = Location::query()
                ->whereIn('location_parent_id', $parents)
                ->pluck('id')
                ->all();

            if (empty($children)) {
                break;
            }

            foreach ($children as $childId) {
                $ids[] = $childId;
            }

            $parents = $children;
        }

        return array_values(array_unique($ids));
    }

    private function syncWarehousePivotToSubtree(Location $root, string $warehouseId): void
    {
        $locationIds = $this->getSelfAndDescendantLocationIds($root->id);

        $this->replaceWarehouseLocationsForIds($locationIds, $warehouseId);
    }

    private function syncCurrentOwnerPivotToSubtree(Location $root): void
    {
        $locationIds = $this->getSelfAndDescendantLocationIds($root->id);

        $owner = $root->type === 'warehouse'
            ? $root
            : $this->findAncestorByTypes($root->location_parent_id, ['warehouse', 'store']);

        if (! $owner) {
            DB::table('warehouse_locations')->whereIn('location_id', $locationIds)->delete();
            DB::table('store_locations')->whereIn('location_id', $locationIds)->delete();

            return;
        }

        if ($owner->type === 'warehouse') {
            $warehouseId = DB::table('warehouse_locations')
                ->where('location_id', $owner->id)
                ->value('warehouse_id');

            if (! $warehouseId) {
                DB::table('warehouse_locations')->whereIn('location_id', $locationIds)->delete();
                DB::table('store_locations')->whereIn('location_id', $locationIds)->delete();

                return;
            }

            $this->replaceWarehouseLocationsForIds($locationIds, $warehouseId);

            return;
        }

        if ($owner->type === 'store') {
            $storeId = DB::table('store_locations')
                ->where('location_id', $owner->id)
                ->value('store_id');

            if (! $storeId) {
                DB::table('warehouse_locations')->whereIn('location_id', $locationIds)->delete();
                DB::table('store_locations')->whereIn('location_id', $locationIds)->delete();

                return;
            }

            $this->replaceStoreLocationsForIds($locationIds, $storeId);
        }
    }

    private function getSelfAndDescendantLocationIds(string $rootId): array
    {
        return array_values(array_unique(array_merge(
            [$rootId],
            $this->getDescendantLocationIds($rootId)
        )));
    }

    private function replaceWarehouseLocationsForIds(array $locationIds, string $warehouseId): void
    {
        $locationIds = array_values(array_unique(array_filter($locationIds)));

        if (empty($locationIds)) {
            return;
        }

        DB::table('warehouse_locations')
            ->whereIn('location_id', $locationIds)
            ->delete();

        DB::table('store_locations')
            ->whereIn('location_id', $locationIds)
            ->delete();

        $rows = [];

        foreach ($locationIds as $locationId) {
            $rows[] = [
                'location_id' => $locationId,
                'warehouse_id' => $warehouseId,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('warehouse_locations')->insert($chunk);
        }
    }

    private function replaceStoreLocationsForIds(array $locationIds, string $storeId): void
    {
        $locationIds = array_values(array_unique(array_filter($locationIds)));

        if (empty($locationIds)) {
            return;
        }

        DB::table('warehouse_locations')
            ->whereIn('location_id', $locationIds)
            ->delete();

        DB::table('store_locations')
            ->whereIn('location_id', $locationIds)
            ->delete();

        $rows = [];

        foreach ($locationIds as $locationId) {
            $rows[] = [
                'location_id' => $locationId,
                'store_id' => $storeId,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('store_locations')->insert($chunk);
        }
    }

    private function syncRfidTagToSubtree(Location $root): void
    {
        $locationIds = array_merge(
            [$root->id],
            $this->getDescendantLocationIds($root->id)
        );

        $locations = Location::query()
            ->whereIn('id', $locationIds)
            ->get();

        foreach ($locations as $loc) {
            $this->syncRfidTag($loc);
        }
    }
}
