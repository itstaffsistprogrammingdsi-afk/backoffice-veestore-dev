<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;

    /**
     * Gunakan koneksi backoffice yang benar.
     */
    protected $connection = 'immostore_backoffice'; // ✅ ubah ke nama yang sama dengan config/permission.php

    protected $table = 'permissions';
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable = ['id', 'name', 'guard_name'];
    protected $attributes = ['guard_name' => 'web'];

    protected $casts = [
        'id'         => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
