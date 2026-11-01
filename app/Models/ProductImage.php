<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $table = 'product_images';
    public $incrementing = false;
    protected $keyType = 'string';

    // ✅ Tambahkan is_main agar bisa tersimpan via create()/update() mass assignment
    protected $fillable = [
        'product_id',
        'image_url',
        'status_active',
        'is_main',
    ];

    // ✅ Pastikan is_main benar-benar boolean
    protected $casts = [
        'is_main' => 'boolean',
    ];

    // cache label ENUM agar tidak query berulang
    protected static ?string $activeLabel   = null;
    protected static ?string $inactiveLabel = null;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            // set uuid jika kosong
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }

            // jika tidak diisi, default-kan ke label "inactive" (atau label kedua)
            if (empty($m->status_active)) {
                $m->status_active = static::inactiveLabel();
            }

            // ✅ default is_main jika tidak dikirim
            if (!isset($m->is_main)) {
                $m->is_main = false;
            }
        });
    }

    /**
     * Mutator: terima true/false/0/1 dan konversi ke label ENUM DB.
     * Jika sudah berupa string label ENUM, biarkan apa adanya.
     */
    public function setStatusActiveAttribute($value): void
    {
        static::resolveEnumLabels();

        if ($value === true || $value === 1 || $value === '1') {
            $this->attributes['status_active'] = static::$activeLabel;
            return;
        }

        if ($value === false || $value === 0 || $value === '0' || $value === null) {
            $this->attributes['status_active'] = static::$inactiveLabel;
            return;
        }

        // diasumsikan sudah label enum valid
        $this->attributes['status_active'] = $value;
    }

    /** Scope untuk ambil yang aktif */
    public function scopeActive($q)
    {
        return $q->where('status_active', static::activeLabel());
    }

    /** Scope untuk ambil main image */
    public function scopeMain($q)
    {
        return $q->where('is_main', true);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Ambil label "aktif" dari tipe enum di DB */
    public static function activeLabel(): string
    {
        static::resolveEnumLabels();
        return static::$activeLabel;
    }

    /** Ambil label "nonaktif" dari tipe enum di DB */
    public static function inactiveLabel(): string
    {
        static::resolveEnumLabels();
        return static::$inactiveLabel;
    }

    /**
     * Baca daftar label ENUM dari PostgreSQL untuk kolom product_images.status_active
     * Contoh hasil: ['active', 'inactive'] — urutan sesuai enumsortorder
     */
    protected static function resolveEnumLabels(): void
    {
        if (static::$activeLabel !== null && static::$inactiveLabel !== null) {
            return;
        }

        // Cari nama tipe enum kolom status_active
        $typeName = DB::table('pg_attribute as a')
            ->join('pg_class as c', 'a.attrelid', '=', 'c.oid')
            ->join('pg_type  as t', 'a.atttypid', '=', 't.oid')
            ->where('c.relname', 'product_images')
            ->where('a.attname', 'status_active')
            ->value('t.typname'); // ex: 'product_image_status'

        // kalau karena alasan tertentu gagal dapat typname, fallback aman
        if (empty($typeName)) {
            static::$activeLabel = 'active';
            static::$inactiveLabel = 'inactive';
            return;
        }

        $rows = DB::select(
            "SELECT e.enumlabel
             FROM pg_enum e
             JOIN pg_type t ON t.oid = e.enumtypid
             WHERE t.typname = ? ORDER BY e.enumsortorder",
            [$typeName]
        );

        $labels = collect($rows)->pluck('enumlabel')->values();

        // pilih label aktif – jika ada 'active' pakai itu, kalau tidak ambil label pertama
        $active = $labels->contains('active') ? 'active' : ($labels[0] ?? 'active');

        // label nonaktif = label yang bukan aktif; fallback ke aktif kalau tipe hanya punya satu label
        $inactive = $labels->first(fn($l) => $l !== $active) ?? $active;

        static::$activeLabel   = $active;
        static::$inactiveLabel = $inactive;
    }
}
