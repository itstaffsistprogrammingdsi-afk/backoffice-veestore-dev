<?php

namespace App\Support\Concerns;

trait NormalizesDate
{
    protected function normalizeDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            [$day, $month, $year] = explode('/', $date);

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }
}
