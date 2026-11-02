<?php

namespace App\Http\Controllers\MasterData;

use App\Domain\LocationLayout\Actions\AddLayoutNode;
use App\Domain\StoreLocations\Actions\GenerateStoreLocations;
use App\Domain\StoreLocations\Queries\StoreLocationGroups;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LocationstoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view master-data-locations-store')->only(['index', 'treeData', 'qr']);
        $this->middleware('permission:create master-data-locations-store')->only(['store', 'generate', 'quickAdd']);
        $this->middleware('permission:update master-data-locations-store')->only(['update', 'updateStoreName']);
        $this->middleware('permission:delete master-data-locations-store')->only(['destroy']);
    }

    public function index(Request $request, StoreLocationGroups $groups)
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = $request->query('store_id');
        $storeId = is_string($storeId) && $storeId !== '' ? $storeId : null;
        $type = $request->query('type');
        $type = is_string($type) && in_array($type, ['room', 'store_display', 'staging', 'rack', 'layer'], true)
            ? $type
            : null;

        $stores = Store::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'address']);

        return Inertia::render('MasterData/StoreLocations', [
            'groups' => $groups->build($q, $storeId, $type),
            'canUpdate' => $request->user()?->can('update master-data-locations-store') ?? false,
            'stores' => $stores->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
                'address' => $store->address,
            ])->values()->all(),
            'filters' => [
                'q' => $q,
                'store_id' => $storeId,
                'type' => $type,
            ],
            'typeOptions' => [
                ['value' => 'room', 'label' => 'Room'],
                ['value' => 'store_display', 'label' => 'Store Display'],
                ['value' => 'staging', 'label' => 'Staging'],
                ['value' => 'rack', 'label' => 'Rack'],
                ['value' => 'layer', 'label' => 'Layer'],
            ],
            'urls' => [
                'index' => route('master-data.locations-store.index'),
                'generate' => route('master-data.locations-store.generate'),
                'quickAdd' => route('master-data.locations-store.quick-add'),
                'updateName' => route('master-data.locations-store.update-name', ['store' => '__ID__']),
                'update' => route('master-data.locations-store.update', ['location' => '__ID__']),
                'destroy' => route('master-data.locations-store.destroy', ['location' => '__ID__']),
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
         * Ambil nama Store dari pivot store_locations.
         * Karena child location juga di-attach ke store_locations lewat attachPivotToTopOwner(),
         * maka store_name bisa langsung diambil dari location_id masing-masing.
         */
        $storeLocationMap = DB::table('store_locations as sl')
            ->join('stores as s', 's.id', '=', 'sl.store_id')
            ->select(
                'sl.location_id',
                'sl.store_id',
                's.name as store_name'
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

        $storeMetaOf = function (?string $locationId) use ($storeLocationMap, $ownerOf) {
            if (! $locationId) {
                return [
                    'store_id' => null,
                    'store_name' => null,
                ];
            }

            $direct = $storeLocationMap->get($locationId);
            if ($direct) {
                return [
                    'store_id' => $direct->store_id,
                    'store_name' => $direct->store_name,
                ];
            }

            $owner = $ownerOf($locationId);
            if ($owner && ($owner['type'] ?? null) === 'store') {
                $ownerStore = $storeLocationMap->get($owner['id']);

                if ($ownerStore) {
                    return [
                        'store_id' => $ownerStore->store_id,
                        'store_name' => $ownerStore->store_name,
                    ];
                }

                return [
                    'store_id' => null,
                    'store_name' => $owner['name'] ?? null,
                ];
            }

            return [
                'store_id' => null,
                'store_name' => null,
            ];
        };

        $matchedIds = $all->filter(function ($loc) use ($q, $type, $ownerOf, $storeMetaOf) {
            $ok = true;

            if ($q !== '') {
                $qLower = mb_strtolower($q);
                $storeMeta = $storeMetaOf($loc->id);
                $storeName = $storeMeta['store_name'] ?? '';

                $ok = (mb_stripos(mb_strtolower($loc->name), $qLower) !== false)
                   || (mb_stripos(mb_strtolower($loc->code), $qLower) !== false)
                   || (mb_stripos(mb_strtolower($storeName), $qLower) !== false);
            }

            if ($ok && $type) {
                $ok = $loc->type === $type;
            }

            if (! $ok) {
                return false;
            }

            if ($loc->type === 'store') {
                return true;
            }

            $owner = $ownerOf($loc->id);

            return $owner && ($owner['type'] === 'store');
        })->pluck('id')->flip();

        $shouldInclude = function ($id) use (&$shouldInclude, $matchedIds, $childrenMap, $ownerOf) {
            $owner = $ownerOf($id);
            if (! $owner || $owner['type'] !== 'store') {
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

        $buildTree = function ($parentId) use (&$buildTree, $childrenMap, $shouldInclude, $ownerOf, $storeMetaOf) {
            $nodes = [];

            foreach ($childrenMap->get($parentId, []) as $loc) {
                if (! $shouldInclude($loc->id)) {
                    continue;
                }

                $owner = $ownerOf($loc->id);
                $storeMeta = $storeMetaOf($loc->id);

                $node = [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'code' => $loc->code,
                    'type' => $loc->type,
                    'location_parent_id' => $loc->location_parent_id,
                    'owner_root_id' => $owner['id'] ?? null,
                    'owner_root_type' => $owner['type'] ?? null,

                    // Tambahan untuk kolom Store di halaman index
                    'store_id' => $storeMeta['store_id'] ?? null,
                    'store_name' => $storeMeta['store_name'] ?? null,
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

    public function updateStoreName(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        $store->update(['name' => trim($data['name'])]);

        return $this->redirectIndex()->with('success', 'Nama store berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        $formFrom = $request->input('_form', 'create');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'type' => 'required|in:store,room,store_display,rack,staging,layer',
            'location_parent_id' => 'nullable|uuid|exists:locations,id',
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'store_id' => [
                'nullable',
                'uuid',
                'exists:stores,id',
                Rule::requiredIf(fn () => $request->type === 'store'),
            ],
            '_form' => 'nullable|in:create,quick_add',
        ]);

        $this->enforceParentRules($data['type'], $data['location_parent_id'] ?? null);

        try {
            DB::transaction(function () use ($data) {
                $storeId = $this->resolveTargetStoreId(
                    $data['type'],
                    $data['location_parent_id'] ?? null,
                    $data['store_id'] ?? null
                );

                if (! $storeId) {
                    throw ValidationException::withMessages([
                        'store_id' => 'Store tujuan tidak dapat ditemukan.',
                    ]);
                }

                $scope = $this->resolveUniquenessScope(
                    $data['type'],
                    $data['location_parent_id'] ?? null,
                    $storeId
                );

                $this->lockScopedValue($scope, $data['type'], 'name', $data['name']);
                $this->lockScopedValue($scope, $data['type'], 'code', $data['code']);

                $this->enforceScopedUnique($scope, $data['type'], 'name', $data['name']);
                $this->enforceScopedUnique($scope, $data['type'], 'code', $data['code']);

                $location = Location::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'type' => $data['type'],
                    'location_parent_id' => $data['location_parent_id'] ?? null,
                ]);

                if ($data['type'] === 'store') {
                    DB::table('store_locations')->insert([
                        'location_id' => $location->id,
                        'store_id' => $data['store_id'],
                    ]);
                }

                $this->attachPivotToTopOwner($location);
                $this->syncRfidTag($location);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return back()
                    ->withErrors(['code' => 'Kode lokasi sudah digunakan pada scope yang sama.'])
                    ->withInput($request->all());
            }
            throw $e;
        }

        $typeLabel = $this->locationTypeLabel($data['type']);

        return $this->redirectIndex()
            ->with('success', "{$typeLabel} \"{$data['name']}\" berhasil ditambahkan.")
            ->withInput(['_form' => $formFrom]);
    }

    public function generate(Request $request, GenerateStoreLocations $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'store_id' => ['required', 'uuid', 'exists:stores,id'],
            'rooms' => ['required', 'integer', 'min:1', 'max:20'],
            'staging' => ['required', 'integer', 'min:0', 'max:20'],
            'racks' => ['required', 'integer', 'min:0', 'max:50'],
            'layers' => ['required', 'integer', 'min:0', 'max:20'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'store_id.required' => 'Store wajib dipilih.',
            'rooms.min' => 'Jumlah room minimal 1.',
            'rooms.max' => 'Jumlah room maksimal 20.',
            'staging.max' => 'Jumlah staging maksimal 20.',
            'racks.max' => 'Jumlah rack maksimal 50.',
            'layers.max' => 'Jumlah layer maksimal 20.',
        ]);

        $rooms = (int) $data['rooms'];
        $staging = (int) $data['staging'];
        $racks = (int) $data['racks'];
        $layers = (int) $data['layers'];

        if ($layers > 0 && $staging === 0 && $racks === 0) {
            throw ValidationException::withMessages([
                'layers' => 'Isi minimal satu staging atau rack jika ingin membuat layer.',
            ]);
        }

        $perRoom = 1 + $staging + $racks + (($staging + $racks) * $layers);

        if (($rooms * $perRoom) > 500) {
            throw ValidationException::withMessages([
                'rooms' => 'Total lokasi yang dibuat melebihi batas 500 node. Kurangi jumlah room, staging, rack, atau layer.',
            ]);
        }

        $store = Store::query()->findOrFail($data['store_id']);
        $result = $action->handle(
            $store,
            trim($data['name']),
            $rooms,
            $staging,
            $racks,
            $layers
        );

        $created = $result['created'];

        return $this->redirectIndex()->with(
            'success',
            sprintf(
                'Lokasi "%s" berhasil dibuat: %d room, %d staging, %d rack, %d layer. Kode dan QR dibuat otomatis.',
                trim($data['name']),
                $created['rooms'],
                $created['staging'],
                $created['racks'],
                $created['layers']
            )
        );
    }

    public function quickAdd(Request $request, AddLayoutNode $action): RedirectResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'uuid', 'exists:stores,id'],
            'type' => ['required', 'in:room,store_display,staging,rack,layer'],
            'parent_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
        ]);

        $store = Store::query()->findOrFail($data['store_id']);
        $location = $action->handle(
            'store',
            $store->id,
            $store->name,
            (string) $store->code,
            $data['type'],
            $data['parent_id'] ?? null,
            $data['name']
        );

        return $this->redirectIndex()->with(
            'success',
            $this->locationTypeLabel($location->type)." \"{$location->name}\" ({$location->code}) berhasil ditambahkan."
        );
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'type' => 'required|in:store,room,store_display,rack,staging,layer',
            'location_parent_id' => 'nullable|uuid|exists:locations,id|not_in:'.$location->id,
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'store_id' => [
                'nullable',
                'uuid',
                'exists:stores,id',
                Rule::requiredIf(fn () => $request->type === 'store'),
            ],
            '_form' => 'nullable|in:edit',
            'id' => ['nullable', 'uuid', Rule::in([$location->id])],
        ]);

        $this->enforceParentRules($data['type'], $data['location_parent_id'] ?? null);

        try {
            DB::transaction(function () use ($location, $data) {
                $storeId = $this->resolveTargetStoreId(
                    $data['type'],
                    $data['location_parent_id'] ?? null,
                    $data['store_id'] ?? null
                );

                if (! $storeId) {
                    throw ValidationException::withMessages([
                        'store_id' => 'Store tujuan tidak dapat ditemukan.',
                    ]);
                }

                $scope = $this->resolveUniquenessScope(
                    $data['type'],
                    $data['location_parent_id'] ?? null,
                    $storeId
                );

                $this->lockScopedValue($scope, $data['type'], 'name', $data['name']);
                $this->lockScopedValue($scope, $data['type'], 'code', $data['code']);

                $this->enforceScopedUnique($scope, $data['type'], 'name', $data['name'], $location->id);
                $this->enforceScopedUnique($scope, $data['type'], 'code', $data['code'], $location->id);

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

                $lockedLocation->refresh();

                if ($lockedLocation->type === 'store') {
                    $this->syncStorePivotToSubtree($lockedLocation, $data['store_id']);
                } else {
                    $this->syncCurrentOwnerPivotToSubtree($lockedLocation);
                }

                $this->syncRfidTagToSubtree($lockedLocation);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return back()
                    ->withErrors(['code' => 'Kode lokasi sudah digunakan pada scope yang sama.'])
                    ->withInput($request->all());
            }
            throw $e;
        }

        $typeLabel = $this->locationTypeLabel($data['type']);

        return $this->redirectIndex()->with(
            'success',
            "{$typeLabel} \"{$data['name']}\" berhasil diperbarui."
        );
    }

    public function destroy(Location $location)
    {
        $typeLabel = $this->locationTypeLabel($location->type);
        $locationName = $location->name;

        DB::transaction(function () use ($location) {
            $locationIds = array_merge(
                [$location->id],
                $this->getDescendantLocationIds($location->id)
            );

            DB::table('warehouse_locations')->whereIn('location_id', $locationIds)->delete();
            DB::table('store_locations')->whereIn('location_id', $locationIds)->delete();
            DB::table('location_rfid_tags')->whereIn('location_id', $locationIds)->delete();

            Location::query()->whereIn('id', $locationIds)->delete();
        });

        return back()->with(
            'success',
            "{$typeLabel} \"{$locationName}\" beserta turunannya berhasil dihapus."
        );
    }

    public function qr(Location $location)
    {
        if (in_array($location->type, ['warehouse', 'store'], true)) {
            abort(404, 'QR tidak tersedia untuk tipe store/warehouse.');
        }

        $value = DB::table('location_rfid_tags')
            ->where('location_id', $location->id)
            ->value('value');

        if (! $value) {
            $value = (string) Str::uuid();
            DB::table('location_rfid_tags')->insert([
                'value' => $value,
                'location_id' => $location->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('location_rfid_tags')
                ->where('location_id', $location->id)
                ->update(['updated_at' => now()]);
        }

        $svg = QrCode::format('svg')->size(260)->margin(1)->generate($value);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function redirectIndex()
    {
        return app('router')->has('master-data.locations-store.index')
            ? redirect()->route('master-data.locations-store.index')
            : redirect(url('master-data/locations-store'));
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

    /**
     * ✅ RULES KHUSUS HALAMAN STORE:
     * - store root: parent kosong
     * - room & store_display: parent wajib STORE (bukan warehouse)
     * - rack/staging: parent room/store_display
     * - layer: parent rack/staging
     */
    private function enforceParentRules(string $type, ?string $parentId): void
    {
        $type = strtolower($type);

        if ($type === 'store') {
            if (! empty($parentId)) {
                abort(422, 'Parent harus kosong untuk tipe Store.');
            }

            return;
        }

        if ($type === 'room') {
            if (empty($parentId)) {
                abort(422, 'Parent STORE wajib diisi untuk tipe Room.');
            }
            $p = Location::select('id', 'type')->find($parentId);
            if (! $p || $p->type !== 'store') {
                abort(422, 'Parent untuk Room harus STORE.');
            }

            return;
        }

        if ($type === 'store_display') {
            if (empty($parentId)) {
                abort(422, 'Parent STORE wajib diisi untuk tipe Store Display.');
            }
            $p = Location::select('id', 'type')->find($parentId);
            if (! $p || $p->type !== 'store') {
                abort(422, 'Parent untuk Store Display harus STORE.');
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

    /* ===== UNIQUE helpers (STORE) ===== */

    private function resolveStoreIdFromParent(?string $parentId): ?string
    {
        $owner = $this->findAncestorByTypes($parentId, ['store']);

        if (! $owner) {
            return null;
        }

        return DB::table('store_locations')
            ->where('location_id', $owner->id)
            ->value('store_id');
    }

    private function resolveTargetStoreId(
        string $type,
        ?string $parentId,
        ?string $selectedStoreId
    ): ?string {
        if (strtolower($type) === 'store') {
            return $selectedStoreId ?: null;
        }

        return $this->resolveStoreIdFromParent($parentId);
    }

    private function resolveUniquenessScope(
        string $type,
        ?string $parentId,
        string $storeId
    ): array {
        $type = strtolower($type);

        if (in_array($type, ['rack', 'staging', 'layer'], true)) {
            if (! $parentId) {
                throw ValidationException::withMessages([
                    'location_parent_id' => 'Parent lokasi wajib diisi.',
                ]);
            }

            return ['kind' => 'parent', 'id' => $parentId];
        }

        return ['kind' => 'store', 'id' => $storeId];
    }

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
            $query->join('store_locations as sl', 'sl.location_id', '=', 'l.id')
                ->where('sl.store_id', $scope['id']);
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
        $typeLabel = $this->locationTypeLabel($type);
        $scopeLabel = ($scope['kind'] ?? null) === 'parent'
            ? 'induk yang sama'
            : 'Store yang sama';

        throw ValidationException::withMessages([
            $field => "{$fieldLabel} {$typeLabel} sudah digunakan pada {$scopeLabel}.",
        ]);
    }

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
            'store-location',
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

    private function normalizeComparable(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value, 'UTF-8');
    }

    private function locationTypeLabel(string $type): string
    {
        return match (strtolower($type)) {
            'store' => 'Store',
            'room' => 'Room',
            'store_display' => 'Store Display',
            'rack' => 'Rack',
            'staging' => 'Staging',
            'layer' => 'Layer',
            default => ucfirst($type),
        };
    }

    /* ===== Helpers existing ===== */

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->getCode();
        $msg = $e->getMessage();

        return $sqlState == '23505' ||
            str_contains($msg, 'Integrity constraint violation') ||
            str_contains($msg, 'Duplicate entry') ||
            str_contains($msg, 'UNIQUE constraint failed');
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
        $needsTag = in_array($loc->type, ['staging', 'layer'], true);

        if ($needsTag) {
            $exists = DB::table('location_rfid_tags')->where('location_id', $loc->id)->exists();
            if (! $exists) {
                DB::table('location_rfid_tags')->insert([
                    'value' => DB::raw('gen_random_uuid()'),
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

    private function syncStorePivotToSubtree(Location $root, string $storeId): void
    {
        $locationIds = array_merge(
            [$root->id],
            $this->getDescendantLocationIds($root->id)
        );

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

    private function syncCurrentOwnerPivotToSubtree(Location $root): void
    {
        $locationIds = array_merge(
            [$root->id],
            $this->getDescendantLocationIds($root->id)
        );

        if (empty($locationIds)) {
            return;
        }

        $owner = $root->type === 'store'
            ? $root
            : $this->findAncestorByTypes($root->location_parent_id, ['warehouse', 'store']);

        if (! $owner) {
            return;
        }

        DB::table('warehouse_locations')
            ->whereIn('location_id', $locationIds)
            ->delete();

        DB::table('store_locations')
            ->whereIn('location_id', $locationIds)
            ->delete();

        if ($owner->type === 'store') {
            $storeId = DB::table('store_locations')
                ->where('location_id', $owner->id)
                ->value('store_id');

            if (! $storeId) {
                return;
            }

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

            return;
        }

        if ($owner->type === 'warehouse') {
            $warehouseId = DB::table('warehouse_locations')
                ->where('location_id', $owner->id)
                ->value('warehouse_id');

            if (! $warehouseId) {
                return;
            }

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
