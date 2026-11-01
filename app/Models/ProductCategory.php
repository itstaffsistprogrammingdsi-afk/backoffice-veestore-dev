<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $table = 'product_categories';

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
        static::creating(function (ProductCategory $category) {
            if (empty($category->getKey())) {
                $category->{$category->getKeyName()} = (string) Str::uuid();
            }

            $category->name = self::normalizeName($category->name);
        });

        static::updating(function (ProductCategory $category) {
            $category->name = self::normalizeName($category->name);
        });
    }

    private static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }
}
