<?php

namespace App\Domain\WarehouseLocations\Actions;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateWarehouseLocations
{
    public function handle(
        Warehouse $warehouse,
        string $name,
        int $rooms,
        int $staging,
        int $racks,
        int $layers
    ): array {
        return DB::transaction(function () use ($warehouse, $name, $rooms, $staging, $racks, $layers) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select(
                    'SELECT pg_advisory_xact_lock(hashtext(?))',
                    ['warehouse-location-generate:'.$warehouse->id]
                );
            }

            $root = $this->ensureRoot($warehouse);
            $usedCodes = $this->usedCodes($warehouse->id);
            $usedCodes[mb_strtolower($root->code)] = true;

            $created = [
                'rooms' => 0,
                'staging' => 0,
                'racks' => 0,
                'layers' => 0,
            ];

            $roomSequence = 1;

            for ($roomIndex = 1; $roomIndex <= $rooms; $roomIndex++) {
                $roomCode = $this->nextCode(rtrim($root->code, '/').'/R', $usedCodes, $roomSequence);
                $roomName = $this->uniqueName(
                    $warehouse,
                    $rooms === 1 ? $name : $name.' Room '.$roomIndex,
                    'room',
                    $root->id
                );

                $room = $this->createNode($warehouse, $roomName, $roomCode, 'room', $root->id);
                $created['rooms']++;

                $stagingSequence = 1;
                for ($stagingIndex = 1; $stagingIndex <= $staging; $stagingIndex++) {
                    $stagingCode = $this->nextCode($room->code.'/STG', $usedCodes, $stagingSequence);
                    $stagingNode = $this->createNode(
                        $warehouse,
                        $this->uniqueName($warehouse, 'Staging '.$stagingIndex, 'staging', $room->id),
                        $stagingCode,
                        'staging',
                        $room->id
                    );
                    $created['staging']++;

                    $layerSequence = 1;
                    for ($layerIndex = 1; $layerIndex <= $layers; $layerIndex++) {
                        $layerCode = $this->nextCode($stagingNode->code.'/L', $usedCodes, $layerSequence);
                        $this->createNode(
                            $warehouse,
                            $this->uniqueName($warehouse, 'Layer '.$layerIndex, 'layer', $stagingNode->id),
                            $layerCode,
                            'layer',
                            $stagingNode->id
                        );
                        $created['layers']++;
                    }
                }

                $rackSequence = 1;
                for ($rackIndex = 1; $rackIndex <= $racks; $rackIndex++) {
                    $rackCode = $this->nextCode($room->code.'/RK', $usedCodes, $rackSequence);
                    $rackNode = $this->createNode(
                        $warehouse,
                        $this->uniqueName($warehouse, 'Rack '.$rackIndex, 'rack', $room->id),
                        $rackCode,
                        'rack',
                        $room->id
                    );
                    $created['racks']++;

                    $layerSequence = 1;
                    for ($layerIndex = 1; $layerIndex <= $layers; $layerIndex++) {
                        $layerCode = $this->nextCode($rackNode->code.'/L', $usedCodes, $layerSequence);
                        $this->createNode(
                            $warehouse,
                            $this->uniqueName($warehouse, 'Layer '.$layerIndex, 'layer', $rackNode->id),
                            $layerCode,
                            'layer',
                            $rackNode->id
                        );
                        $created['layers']++;
                    }
                }
            }

            return [
                'root_id' => $root->id,
                'root_code' => $root->code,
                'created' => $created,
                'total' => array_sum($created),
            ];
        });
    }

    private function ensureRoot(Warehouse $warehouse): Location
    {
        $root = Location::query()
            ->where('type', 'warehouse')
            ->whereIn(
                'id',
                DB::table('warehouse_locations')
                    ->where('warehouse_id', $warehouse->id)
                    ->pluck('location_id')
            )
            ->first();

        if ($root) {
            $this->attachWarehousePivot($root, $warehouse);

            return $root;
        }

        $code = trim((string) ($warehouse->code ?: 'WH-'.mb_substr($warehouse->id, 0, 8)));
        $root = Location::create([
            'name' => $warehouse->name,
            'code' => $this->uniqueRootCode($code),
            'type' => 'warehouse',
            'location_parent_id' => null,
        ]);

        $this->attachWarehousePivot($root, $warehouse);

        return $root;
    }

    private function uniqueRootCode(string $code): string
    {
        $candidate = $code;
        $suffix = 1;

        while (Location::query()->where('code', $candidate)->whereNull('deleted_at')->exists()) {
            $suffix++;
            $candidate = $code.'-'.$suffix;
        }

        return $candidate;
    }

    private function usedCodes(string $warehouseId): array
    {
        return DB::table('locations as l')
            ->join('warehouse_locations as wl', 'wl.location_id', '=', 'l.id')
            ->where('wl.warehouse_id', $warehouseId)
            ->whereNull('l.deleted_at')
            ->pluck('l.code')
            ->mapWithKeys(fn (string $code) => [mb_strtolower($code) => true])
            ->all();
    }

    private function nextCode(string $prefix, array &$usedCodes, int &$sequence): string
    {
        do {
            $code = $prefix.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            $sequence++;
        } while (isset($usedCodes[mb_strtolower($code)]));

        $usedCodes[mb_strtolower($code)] = true;

        return $code;
    }

    private function uniqueName(
        Warehouse $warehouse,
        string $name,
        string $type,
        string $parentId
    ): string {
        $candidate = $name;
        $suffix = 2;

        while ($this->nameExists($warehouse, $candidate, $type, $parentId)) {
            $candidate = $name.' '.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function nameExists(
        Warehouse $warehouse,
        string $name,
        string $type,
        string $parentId
    ): bool {
        $query = DB::table('locations as l')
            ->join('warehouse_locations as wl', 'wl.location_id', '=', 'l.id')
            ->where('wl.warehouse_id', $warehouse->id)
            ->where('l.type', $type)
            ->whereNull('l.deleted_at');

        if (in_array($type, ['rack', 'staging', 'layer'], true)) {
            $query->where('l.location_parent_id', $parentId);
        }

        $normalized = mb_strtolower(trim(preg_replace('/\\s+/u', ' ', $name) ?? $name));

        return $query
            ->whereRaw(
                "LOWER(REGEXP_REPLACE(TRIM(l.name), '\\s+', ' ', 'g')) = ?",
                [$normalized]
            )
            ->exists();
    }

    private function createNode(
        Warehouse $warehouse,
        string $name,
        string $code,
        string $type,
        string $parentId
    ): Location {
        $location = Location::create([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'location_parent_id' => $parentId,
        ]);

        $this->attachWarehousePivot($location, $warehouse);
        $this->createQrTag($location);

        return $location;
    }

    private function attachWarehousePivot(Location $location, Warehouse $warehouse): void
    {
        DB::table('warehouse_locations')->insertOrIgnore([
            'location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
        ]);
    }

    private function createQrTag(Location $location): void
    {
        if ($location->type === 'warehouse') {
            return;
        }

        do {
            $value = (string) Str::uuid();
        } while (DB::table('location_rfid_tags')->where('value', $value)->exists());

        DB::table('location_rfid_tags')->insertOrIgnore([
            'value' => $value,
            'location_id' => $location->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
