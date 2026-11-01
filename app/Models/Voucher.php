<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes; // << gunakan bila ada deleted_at
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use SoftDeletes; // hapus baris ini jika TIDAK pakai soft delete di migration

    protected $table = 'vouchers';

    // Jika PK adalah UUID string
    protected $primaryKey  = 'id';
    public $incrementing   = false;
    protected $keyType     = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'max_discount',
        'min_purchase',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_count',
        'is_active',
        'context',
        'distribution_type',
        // 'deleted_at'  // TIDAK perlu di-fillable
    ];

    protected $casts = [
        'value'        => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'valid_from'   => 'datetime',
        'valid_until'  => 'datetime',
        'usage_limit'  => 'integer',
        'usage_count'  => 'integer',
        'is_active'    => 'boolean',
        'deleted_at'   => 'datetime',
    ];

    public function voucherCodes(): HasMany
    {
        return $this->hasMany(VoucherCode::class, 'voucher_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class, 'voucher_id');
    }

    protected static function boot()
    {
        parent::boot();

        // auto generate UUID saat create jika kosong
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /** scope simple search */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;
        return $q->where(function ($w) use ($term) {
            $w->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /** scope active only (optional) */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
