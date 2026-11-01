<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Laravel 9+
use Illuminate\Database\Eloquent\SoftDeletes;        // jika tabel pakai soft deletes (opsional)

class Discount extends Model
{
    use HasUuids;
    // use SoftDeletes; // <- uncomment jika tabel discounts punya kolom deleted_at

    protected $table = 'discounts';

    /**
     * UUID primary key
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Mass assignment
     */
    protected $fillable = [
        'promo_id',
        'name',
        'description',
        'type',          // fixed | percentage
        'value',         // decimal(15,2)
        'max_discount',  // decimal(15,2) nullable (only for percentage)
        'min_purchase',  // decimal(15,2) default(0)
        'valid_from',    // timestamp nullable
        'valid_until',   // timestamp nullable
        'usage_limit',   // int nullable
        'usage_count',   // int default(0)
        'priority',      // int default(100)
        'combinable',    // boolean default false
        'is_active',     // boolean default true
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'value'        => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'valid_from'   => 'datetime',
        'valid_until'  => 'datetime',
        'usage_limit'  => 'integer',
        'usage_count'  => 'integer',
        'priority'     => 'integer',
        'combinable'   => 'boolean',
        'is_active'    => 'boolean',
        // 'deleted_at' => 'datetime', // jika SoftDeletes
    ];

    /**
     * Relations
     */
    public function promo()
    {
        // FK: promo_id (UUID) -> promos.id (UUID)
        return $this->belongsTo(Promo::class, 'promo_id', 'id');
    }

    /**
     * Scopes
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        // prioritas besar dulu, lalu terbaru
        return $q->orderByDesc('priority')->orderByDesc('created_at');
    }
}