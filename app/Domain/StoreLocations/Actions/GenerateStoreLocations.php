<?php

namespace App\Domain\StoreLocations\Actions;

use App\Models\Location;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateStoreLocations
{
    public function handle(
        Store $store,
        string $name,
        int $rooms,
        int $staging,
        int $racks,
        int $layers
    ): array {
        return DB::transaction(function () use ($store, $name, $rooms, $staging, $racks, $layers) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select(
                    'SELECT pg_advisory_xact_lock(hashtext(?))',
                    ['store-location-generate:'.$store->id]
                );
            }

            $root = $this->ensureRoot($store);
            $usedCodes = $this->usedCodes($store->id);
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
                    $store,
                    $rooms === 1 ? $name : $name.' Room '.$roomIndex,
                    'room',
                    $root->id
                );

                $room = $this->createNode($store, $roomName, $roomCode, 'room', $root->id);
                $created['rooms']++;

                $stagingSequence = 1;
                for ($stagingIndex = 1; $stagingIndex <= $staging; $stagingIndex++) {
                    $stagingCode = $this->nextCode($room->code.'/STG', $usedCodes, $stagingSequence);
                    $stagingNode = $this->createNode(
                        $store,
                        $this->uniqueName($store, 'Staging '.$stagingIndex, 'staging', $room->id),
                        $stagingCode,
                        'staging',
                        $room->id
                    );
                    $created['staging']++;

                    $layerSequence = 1;
                    for ($layerIndex = 1; $layerIndex <= $layers; $layerIndex++) {
                        $layerCode = $this->nextCode($stagingNode->code.'/L', $usedCodes, $layerSequence);
                        $this->createNode(
                            $store,
                            $this->uniqueName($store, 'Layer '.$layerIndex, 'layer', $stagingNode->id),
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
                        $store,
                        $this->uniqueName($store, 'Rack '.$rackIndex, 'rack', $room->id),
                        $rackCode,
                        'rack',
                        $room->id
                    );
                    $created['racks']++;

                    $layerSequence = 1;
                    for ($layerIndex = 1; $layerIndex <= $layers; $layerIndex++) {
                        $layerCode = $this->nextCode($rackNode->code.'/L', $usedCodes, $layerSequence);
                        $this->createNode(
                            $store,
                            $this->uniqueName($store, 'Layer '.$layerIndex, 'layer', $rackNode->id),
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

    private function ensureRoot(Store $store): Location
    {
        $root = Location::query()
            ->where('type', 'store')
            ->whereIn(
                'id',
                DB::table('store_locations')
                    ->where('store_id', $store->id)
                    ->pluck('location_id')
            )
            ->first();

        if ($root) {
            $this->attachStorePivot($root, $store);

            return $root;
        }

        $code = trim((string) ($store->code ?: 'ST-'.mb_substr($store->id, 0, 8)));
        $root = Location::create([
            'name' => $store->name,
            'code' => $this->uniqueRootCode($code),
            'type' => 'store',
            'location_parent_id' => null,
        ]);

        $this->attachStorePivot($root, $store);

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

    private function usedCodes(string $storeId): array
    {
        return DB::table('locations as l')
            ->join('store_locations as sl', 'sl.location_id', '=', 'l.id')
            ->where('sl.store_id', $storeId)
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
        Store $store,
        string $name,
        string $type,
        string $parentId
    ): string {
        $candidate = $name;
        $suffix = 2;

        while ($this->nameExists($store, $candidate, $type, $parentId)) {
            $candidate = $name.' '.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function nameExists(
        Store $store,
        string $name,
        string $type,
        string $parentId
    ): bool {
        $query = DB::table('locations as l')
            ->join('store_locations as sl', 'sl.location_id', '=', 'l.id')
            ->where('sl.store_id', $store->id)
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
        Store $store,
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

        $this->attachStorePivot($location, $store);
        $this->createQrTag($location);

        return $location;
    }

    private function attachStorePivot(Location $location, Store $store): void
    {
        DB::table('store_locations')->insertOrIgnore([
            'location_id' => $location->id,
            'store_id' => $store->id,
        ]);
    }

    private function createQrTag(Location $location): void
    {
        if ($location->type === 'store') {
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
