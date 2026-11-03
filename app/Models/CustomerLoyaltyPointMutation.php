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
        'customer_id',
        'ref_id',
        'type',
        'points',
        'balance_after',
        'description',
        'customer_loyalty_point_id',
        'mutation_type',
        'expired_at',
        'has_expiration',
        'used_points',
        'ref',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'used_points' => 'decimal:2',
        'has_expiration' => 'boolean',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
