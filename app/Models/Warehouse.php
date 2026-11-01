<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'warehouses';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'address',
        'postal_code',
        'geom',
        'contact_name',
        'contact_phone',
        'contact_email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Auto-generate code jika kosong
            if (empty($model->code)) {
                $model->code = self::generateNextCode();
            }
        });
    }

    /**
     * Generate code W.VEES.{n} secara urut & aman concurrency (PostgreSQL).
     * Mengambil max nomor dari code yang sesuai pola ^W\.VEES\.[0-9]+$
     */
    public static function generateNextCode(): string
    {
        return DB::transaction(function () {
            DB::statement('LOCK TABLE warehouses IN EXCLUSIVE MODE');

            $maxNum = (int) DB::table('warehouses')
                ->selectRaw("
                    COALESCE(
                        MAX(
                            CASE
                                WHEN code ~ '^W\\.VEES\\.[0-9]+$'
                                THEN (split_part(code, '.', 3))::int
                                ELSE NULL
                            END
                        ),
                        0
                    ) AS max_num
                ")
                ->value('max_num');

            $next = $maxNum + 1;

            return 'W.VEES.' . $next;
        });
    }

    public function users()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'warehouse_users',
            'warehouse_id',
            'user_id'
        )->withTimestamps();
    }

    public function scopeSearch($q, $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('name', 'ILIKE', "%{$term}%")
                ->orWhere('code', 'ILIKE', "%{$term}%")
                ->orWhere('address', 'ILIKE', "%{$term}%");
        });
    }
}
