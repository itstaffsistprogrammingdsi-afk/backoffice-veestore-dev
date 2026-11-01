<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('villages')) {
            return;
        }

        $coordinates = [
            // BPS Kabupaten Batang, Kecamatan Blado Dalam Angka.
            '31783' => [-7.07110, 109.83360],
            // BPS Kabupaten Batang, Kecamatan Gringsing Dalam Angka.
            '35495' => [-6.96470, 110.03630],
        ];

        foreach ($coordinates as $code => [$latitude, $longitude]) {
            DB::table('villages')
                ->where('code', $code)
                ->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'coordinate_source' => 'bps_batang',
                    'geocoded_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('villages')) {
            return;
        }

        DB::table('villages')
            ->whereIn('code', ['31783', '35495'])
            ->where('coordinate_source', 'bps_batang')
            ->update([
                'latitude' => null,
                'longitude' => null,
                'coordinate_source' => null,
                'geocoded_at' => null,
                'updated_at' => now(),
            ]);
    }
};
