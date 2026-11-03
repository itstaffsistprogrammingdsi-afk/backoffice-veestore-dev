<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceOrder extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_orders';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'handled_by_store_id',
        'handled_by_warehouse_id',
        'grand_total',
    ];

    protected $casts = [
        'grand_total' => 'float',
    ];

    public function details()
    {
        return $this->hasMany(EcommerceOrderDetail::class, 'ecommerce_order_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'handled_by_store_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'handled_by_warehouse_id', 'id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(
            EcommerceOrderShippingAddress::class,
            'shipping_address_snapshot_id',
            'id'
        );
    }
}
