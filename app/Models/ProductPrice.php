<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductPrice extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'product_prices';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'price_type',
        'price',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'valid_from' => 'date',
        'valid_to'   => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->getKey())) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * Ambil daftar nilai enum dari PostgreSQL (public.price_type)
     */
    public static function enumValues(string $enumName = 'price_type'): array
    {
        $rows = DB::select("
            SELECT e.enumlabel AS value
            FROM pg_type t
            JOIN pg_enum e ON t.oid = e.enumtypid
            JOIN pg_catalog.pg_namespace n ON n.oid = t.typnamespace
            WHERE t.typname = ? AND n.nspname = 'public'
            ORDER BY e.enumsortorder
        ", [$enumName]);

        return array_map(fn($r) => $r->value, $rows);
    }

    /** Apakah periode ini aktif pada tanggal tertentu */
    public function isActiveOn(?string $date = null): bool
    {
        $date = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $fromOk = $this->valid_from <= $date;
        $toOk   = is_null($this->valid_to) || $this->valid_to >= $date;
        return $fromOk && $toOk;
    }
}