<?php
namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    protected static function ck(string $key): string { return "setting:$key"; }

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever(self::ck($key), function () use ($key, $default) {
            return AppSetting::query()->find($key)?->value ?? $default;
        });
    }

    public static function set(string $key, $value): void
    {
        AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::ck($key));
    }

    public static function getFloat(string $key, string $path, float $default = 0.0): float
    {
        $val = self::get($key, []);
        foreach (explode('.', $path) as $seg) {
            if (!is_array($val) || !array_key_exists($seg, $val)) return $default;
            $val = $val[$seg];
        }
        return (float) $val;
    }
}
