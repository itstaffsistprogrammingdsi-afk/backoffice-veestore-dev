<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    // Simpan segala tipe (number/string/bool/array/object) di kolom JSON/JSONB
    protected $fillable = ['key', 'value'];
    protected $casts = [
        'value'      => 'json',     // penting: bukan 'array', supaya scalar tetap scalar
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Readers (static)
     | ----------------------------------------------------------------- */

    /**
     * Ambil record mentah (atau null jika tidak ada).
     */
    public static function getRaw(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    /**
     * Ambil nilai apa adanya (boleh scalar/array/object).
     * Fallback ke $default jika tidak ada.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::getRaw($key);
        return is_null($row) ? $default : $row->value;
    }

    /**
     * Alias lama/umum: AppSetting::get(...)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }

    /**
     * Ambil numeric (int/float). Jika null/tidak ada -> $default.
     * Otomatis pilih int jika tanpa pecahan.
     */
    public static function getNumeric(string $key, int|float $default = 0): int|float
    {
        $val = static::getValue($key, $default);

        if (is_numeric($val)) {
            $num = $val + 0; // cast cepat
            // kembalikan int jika bilangan bulat
            return (is_float($num) && floor($num) == $num) ? (int) $num : $num;
        }

        return $default;
    }

    /**
     * Ambil string. Non-string akan dicasting jadi string.
     */
    public static function getString(string $key, string $default = ''): string
    {
        $val = static::getValue($key, $default);
        if (is_null($val)) return $default;
        return is_string($val) ? $val : (string) $val;
    }

    /**
     * Ambil boolean dengan normalisasi nilai truthy/falsey umum.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::getValue($key, $default);

        if (is_bool($val)) return $val;
        if (is_numeric($val)) return (int)$val === 1;

        if (is_string($val)) {
            $v = strtolower(trim($val));
            return in_array($v, ['1','true','yes','y','on','aktif','active','enabled'], true);
        }

        return (bool) $val;
    }

    /**
     * Ambil semua settings sebagai array asosiatif: key => value.
     */
    public static function allAsAssoc(): array
    {
        return static::query()
            ->get(['key', 'value'])
            ->pluck('value', 'key')
            ->toArray();
    }

    /* -----------------------------------------------------------------
     |  Writers (static)
     | ----------------------------------------------------------------- */

    /**
     * Set/Upsert nilai setting (scalar/array/object).
     * Mengembalikan model yang tersimpan.
     */
    public static function setValue(string $key, mixed $value): self
    {
        // updateOrCreate by primary key 'key'
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Alias yang sering dipakai: putValue(...).
     * Agar kompatibel dengan kode lama yang memanggil putValue().
     */
    public static function putValue(string $key, mixed $value): self
    {
        return static::setValue($key, $value);
    }

    /**
     * Hapus sebuah setting (kalau ada).
     */
    public static function forget(string $key): bool
    {
        return (bool) static::query()->where('key', $key)->delete();
    }
}
