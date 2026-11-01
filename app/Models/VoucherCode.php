<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VoucherCode extends Model
{
    protected $table = 'voucher_codes';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['voucher_id', 'code', 'is_active', 'expires_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class, 'voucher_code_id');
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->id ??= (string) Str::uuid();
        });
    }
}
