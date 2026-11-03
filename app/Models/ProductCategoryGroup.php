<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductCategoryGroup extends Model
{
    use SoftDeletes;

    protected $table = 'product_category_groups';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $group): void {
            if (empty($group->getKey())) {
                $group->{$group->getKeyName()} = (string) Str::uuid();
            }

            $group->name = self::normalizeName($group->name);
        });

        static::updating(function (self $group): void {
            $group->name = self::normalizeName($group->name);
        });
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'group_id', 'id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    private static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);

        return preg_replace('/\s+/u', ' ', $name) ?? $name;
    }
}
