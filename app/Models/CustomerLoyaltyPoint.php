<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerLoyaltyPoint extends Model
{
    protected $table = 'customer_loyalty_points';

    // UUID primary key (sesuaikan jika pk Anda integer)
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['customer_id', 'balance'];

    protected $casts = [
        'balance' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}