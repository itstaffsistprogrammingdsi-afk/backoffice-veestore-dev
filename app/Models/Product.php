<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    /** PK bertipe UUID (string) */
    public $incrementing = false;
    protected $keyType = 'string';

    /** Mass-assignable fields */
    protected $fillable = [
        'name',
        'code',
        'unit_id',
        'category_id',
        'brand_id',
        'description',
        'weight_g',
        'specification_id', // ⬅️ TAMBAH INI
    ];

    /** Casts */
    protected function casts(): array
    {
        return [
            'deleted_at'       => 'datetime',
            'weight_g'         => 'integer',
        ];
    }

    /** Event hooks */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID primary key
        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /* ========================
     * Relationships
     * ====================== */

    /** Kategori (ikut baca yang soft-deleted) */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id')->withTrashed();
    }

    /** Unit (ikut baca yang soft-deleted) */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id')->withTrashed();
    }

    /** Brand (ikut baca yang soft-deleted) */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id')->withTrashed();
    }

    /** Spesifikasi produk */
    public function specification()
    {
        return $this->belongsTo(ProductSpecification::class, 'specification_id')->withTrashed();
    }

    /** Semua gambar */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    /** Satu gambar utama (mengandalkan scope `active()` di ProductImage jika ada) */
    public function mainImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')->active();
    }

    /** Harga produk */
    public function prices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id');
    }

    /** Mapping produk per store */
    public function storeItems()
    {
        return $this->hasMany(StoreItem::class, 'product_id');
    }

    /* ========================
     * Accessors / Helpers
     * ====================== */

    public function getWeightKgAttribute(): ?float
    {
        return is_null($this->weight_g) ? null : $this->weight_g / 1000;
    }
}
