<?php

namespace App\Services;

use App\Models\Village;
use Illuminate\Support\Facades\Http;
use Throwable;

class VillageGeocoder
{
    public function geocode(?string $villageCode): ?Village
    {
        if (!$villageCode) {
            return null;
        }

        $village = Village::query()
            ->with('district.regency.province')
            ->find($villageCode);

        if (!$village || ($village->latitude && $village->longitude)) {
            return $village;
        }

        $district = $village->district;
        $regency = $district?->regency;
        $province = $regency?->province;

        if (!$district || !$regency || !$province) {
            return $village;
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(6)
                ->retry(1, 300)
                ->withHeaders([
                    'User-Agent' => 'VeestoreBackoffice/1.0 (customer-location-map)',
                    'Accept-Language' => 'id',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'id',
                    'q' => implode(', ', [
                        $village->name,
                        $district->name,
                        $regency->name,
                        $province->name,
                        'Indonesia',
                    ]),
                ]);

            $result = $response->successful()
                ? $response->json('0')
                : null;

            if (is_array($result) && isset($result['lat'], $result['lon'])) {
                $village->forceFill([
                    'latitude' => (float) $result['lat'],
                    'longitude' => (float) $result['lon'],
                    'coordinate_source' => 'nominatim_osm',
                    'geocoded_at' => now(),
                ])->save();
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $village->refresh();
    }
}
