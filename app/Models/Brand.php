<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Brand extends Model
{
    use SoftDeletes;

    protected $table = 'brands';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Brand $brand) {
            if (empty($brand->getKey())) {
                $brand->{$brand->getKeyName()} = (string) Str::uuid();
            }

            $brand->name = self::normalizeName($brand->name);
        });

        static::updating(function (Brand $brand) {
            $brand->name = self::normalizeName($brand->name);
        });
    }

    private static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'id');
    }
}
