<?php

namespace App\Support;

use App\Services\Settings;

class PointRate
{
    /** Setting disimpan di key "points.rate" dengan field "points_per_idr" (float). */
    public static function pointsPerIdr(): float
    {
        // default: 0.001 → 1 Rupiah = 0.001 point → 1.000 Rupiah ≈ 1 point
        return Settings::getFloat('points.rate', 'points_per_idr', 0.001);
    }

    /** Rupiah → Poin */
    public static function toPoints(float $amountIdr, string $round = null): int
    {
        $ppidr = max(self::pointsPerIdr(), 0.0);
        $raw   = $amountIdr * $ppidr;
        $mode  = $round ?: (Settings::get('points.rate', [])['rounding'] ?? 'floor');
        return match ($mode) {
            'ceil'  => (int) ceil($raw),
            'round' => (int) round($raw),
            default => (int) floor($raw),
        };
    }

    /** Poin → Rupiah (kebalikan dari points_per_idr) */
    public static function toIdr(int $points): float
    {
        $ppidr = self::pointsPerIdr();
        if ($ppidr <= 0) return 0.0;
        return $points / $ppidr;
    }
}
