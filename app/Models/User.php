<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * Sumber data user: DB default (pgsql -> dev_immostore)
     */
    protected $connection = 'pgsql';
    protected $table = 'users';

    /**
     * Primary key UUID string.
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Guard untuk Spatie (harus konsisten dengan guard pada role/permission).
     */
    protected $guard_name = 'web';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'ecommerce_access',
        'wms_access',
        'backoffice_access',
        'store_access',
        'remember_token',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'ecommerce_access'  => false,
        'wms_access'        => false,
        'backoffice_access' => false,
        'store_access'      => false,
    ];

    /**
     * Hidden attributes (serialization).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     */
    protected $casts = [
        'ecommerce_access'  => 'boolean',
        'wms_access'        => 'boolean',
        'backoffice_access' => 'boolean',
        'store_access'      => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
        // Jika ingin auto-hash di Laravel 10+, aktifkan:
        // 'password'       => 'hashed',
    ];

    /**
     * Auto-UUID saat create.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Setter password aman (tidak double-hash jika sudah bcrypt).
     */
    public function setPasswordAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = Str::startsWith($value, '$2y$')
                ? $value
                : Hash::make($value);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function warehouses()
    {
        return $this->belongsToMany(
            \App\Models\Warehouse::class,
            'warehouse_users',
            'user_id',
            'warehouse_id'
        )->withTimestamps();
    }

    public function stores()
    {
        return $this->belongsToMany(
            \App\Models\Store::class,
            'store_users',
            'user_id',
            'store_id'
        )->withTimestamps();
    }

    /**
     * Contoh accessor sederhana.
     */
    public function getWarehouseNamesAttribute()
    {
        return $this->warehouses->pluck('name');
    }

    /**
     * Penting untuk Spatie: nilai morph class yang disimpan di model_has_roles
     * harus persis FQCN ini.
     */
    public function getMorphClass()
    {
        return static::class;
    }
}
