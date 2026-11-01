<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Store extends Model
{
    use SoftDeletes;

    protected $table = 'stores';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'address',
        'postal_code',
        'geom',
        'image_url',
        'phone',
        'contact_name',
        'contact_phone',
        'contact_email',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} =
                    (string) Str::uuid();
            }

            if (empty($model->code)) {
                $model->code = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        return DB::transaction(function () {
            DB::statement(
                'LOCK TABLE stores IN EXCLUSIVE MODE'
            );

            $maxNum = (int) DB::table('stores')
                ->selectRaw("
                    COALESCE(
                        MAX(
                            CASE
                                WHEN code ~ '^VEES\\.[0-9]+$'
                                THEN (split_part(code, '.', 2))::int
                                ELSE NULL
                            END
                        ),
                        0
                    ) AS max_num
                ")
                ->value('max_num');

            return 'VEES.' . ($maxNum + 1);
        });
    }

    public function storeItems()
    {
        return $this->hasMany(
            \App\Models\StoreItem::class,
            'store_id'
        );
    }

    public function getLatitudeAttribute()
    {
        if (!$this->geom) {
            return null;
        }

        return optional(
            DB::table($this->table)
                ->selectRaw('ST_Y(geom) as lat')
                ->where('id', $this->id)
                ->first()
        )->lat;
    }

    public function getLongitudeAttribute()
    {
        if (!$this->geom) {
            return null;
        }

        return optional(
            DB::table($this->table)
                ->selectRaw('ST_X(geom) as lng')
                ->where('id', $this->id)
                ->first()
        )->lng;
    }
}
