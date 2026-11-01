<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomerLoyaltyPointMutation extends Model
{
    protected $table = 'customer_loyalty_point_mutations';

    // UUID PK (samakan dengan skema Anda)
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'customer_loyalty_point_id', // FK -> customer_loyalty_points.id
        'value',                     // integer (positif)
        'type',                      // 'in' | 'out'
    ];

    protected $casts = [
        'value' => 'integer',
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

    public function loyaltyPoint(): BelongsTo
    {
        return $this->belongsTo(CustomerLoyaltyPoint::class, 'customer_loyalty_point_id');
    }

    // shortcut ke customer
    public function customer(): BelongsTo
    {
        return $this->loyaltyPoint()->withDefault()->getRelated()->customer();
    }
}
