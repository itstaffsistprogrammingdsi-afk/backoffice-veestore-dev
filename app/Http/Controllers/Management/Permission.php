<?php

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;

    protected $connection = 'immostore_backoffice';
    protected $table = 'permissions';

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = ['id','name','guard_name'];
    protected $attributes = ['guard_name' => 'web'];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if (empty($m->id)) {
                $m->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
