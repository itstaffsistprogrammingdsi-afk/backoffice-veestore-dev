<?php

namespace App\Domain\LocationLayout\Actions;

use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AddLayoutNode
{
    public function handle(
        string $ownerType,
        string $ownerId,
        string $ownerName,
        string $ownerCode,
        string $type,
        ?string $parentId,
        string $name
    ): Location {
        return DB::transaction(function () use ($ownerType, $ownerId, $ownerName, $ownerCode, $type, $parentId, $name) {
            $pivot = $ownerType === 'warehouse' ? 'warehouse_locations' : 'store_locations';
            $ownerKey = $ownerType === 'warehouse' ? 'warehouse_id' : 'store_id';
            $rootType = $ownerType === 'warehouse' ? 'warehouse' : 'store';

            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select(
                    'SELECT pg_advisory_xact_lock(hashtext(?))',
                    ['layout-quick-add:'.$ownerType.':'.$ownerId]
                );
            }

            $root = $this->ensureRoot($pivot, $ownerKey, $rootType, $ownerId, $ownerName, $ownerCode);
            $parent = $this->resolveParent($pivot, $ownerKey, $ownerId, $type, $parentId, $root);

            $usedCodes = DB::table('locations as l')
                ->join($pivot.' as p', 'p.location_id', '=', 'l.id')
                ->where('p.'.$ownerKey, $ownerId)
                ->whereNull('l.deleted_at')
                ->pluck('l.code')
                ->mapWithKeys(fn (string $code) => [mb_strtolower($code) => true])
                ->all();

            $codePrefix = match ($type) {
                'room' => rtrim($root->code, '/').'/R',
                'store_display' => rtrim($root->code, '/').'/D',
                'staging' => rtrim($parent->code, '/').'/STG',
                'rack' => rtrim($parent->code, '/').'/RK',
                'layer' => rtrim($parent->code, '/').'/L',
                default => throw ValidationException::withMessages(['type' => 'Tipe lokasi tidak valid.']),
            };

            $codeSequence = 1;
            $code = $this->nextCode($codePrefix, $usedCodes, $codeSequence);

            $name = trim($name);

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => 'Nama lokasi wajib diisi.',
                ]);
            }

            $this->ensureNameAvailable($pivot, $ownerKey, $ownerId, $type, $parent->id, $name);

            $location = Location::create([
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'location_parent_id' => $parent->id,
            ]);

            DB::table($pivot)->insertOrIgnore([
                'location_id' => $location->id,
                $ownerKey => $ownerId,
            ]);

            $this->createQrTag($location);

            return $location;
        });
    }

    private function ensureRoot(
        string $pivot,
        string $ownerKey,
        string $rootType,
        string $ownerId,
        string $ownerName,
        string $ownerCode
    ): Location {
        $root = Location::query()
            ->where('type', $rootType)
            ->whereIn(
                'id',
                DB::table($pivot)->where($ownerKey, $ownerId)->pluck('location_id')
            )
            ->first();

        if ($root) {
            return $root;
        }

        $code = trim($ownerCode) ?: ($rootType === 'warehouse' ? 'WH-' : 'ST-').mb_substr($ownerId, 0, 8);
        $candidate = $code;
        $suffix = 1;

        while (Location::query()->where('code', $candidate)->whereNull('deleted_at')->exists()) {
            $suffix++;
            $candidate = $code.'-'.$suffix;
        }

        $root = Location::create([
            'name' => $ownerName,
            'code' => $candidate,
            'type' => $rootType,
            'location_parent_id' => null,
        ]);

        DB::table($pivot)->insertOrIgnore([
            'location_id' => $root->id,
            $ownerKey => $ownerId,
        ]);

        return $root;
    }

    private function resolveParent(
        string $pivot,
        string $ownerKey,
        string $ownerId,
        string $type,
        ?string $parentId,
        Location $root
    ): Location {
        if (in_array($type, ['room', 'store_display'], true)) {
            return $root;
        }

        if (! $parentId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Parent wajib dipilih.',
            ]);
        }

        $parent = Location::query()
            ->whereKey($parentId)
            ->whereIn(
                'id',
                DB::table($pivot)->where($ownerKey, $ownerId)->pluck('location_id')
            )
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Parent lokasi tidak ditemukan pada owner ini.',
            ]);
        }

        if (in_array($type, ['staging', 'rack'], true) && ! in_array($parent->type, ['room', 'store_display'], true)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Staging dan Rack harus berada di bawah Room atau Display.',
            ]);
        }

        if ($type === 'layer' && ! in_array($parent->type, ['rack', 'staging'], true)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Layer harus berada di bawah Rack atau Staging.',
            ]);
        }

        return $parent;
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

    private function ensureNameAvailable(
        string $pivot,
        string $ownerKey,
        string $ownerId,
        string $type,
        string $parentId,
        string $name
    ): void {
        $exists = DB::table('locations as l')
            ->join($pivot.' as p', 'p.location_id', '=', 'l.id')
            ->where('p.'.$ownerKey, $ownerId)
            ->where('l.type', $type)
            ->whereNull('l.deleted_at')
            ->when(
                in_array($type, ['staging', 'rack', 'layer'], true),
                fn ($query) => $query->where('l.location_parent_id', $parentId)
            )
            ->whereRaw(
                "LOWER(REGEXP_REPLACE(TRIM(l.name), '\\s+', ' ', 'g')) = ?",
                [mb_strtolower(trim(preg_replace('/\\s+/u', ' ', $name) ?? $name))]
            )
            ->exists();

        if ($exists) {
            $typeLabel = match ($type) {
                'room' => 'Room',
                'store_display' => 'Display',
                'staging' => 'Staging',
                'rack' => 'Rack',
                'layer' => 'Layer',
                default => ucfirst($type),
            };

            throw ValidationException::withMessages([
                'name' => "Nama {$typeLabel} sudah digunakan pada scope lokasi ini.",
            ]);
        }
    }

    private function createQrTag(Location $location): void
    {
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
