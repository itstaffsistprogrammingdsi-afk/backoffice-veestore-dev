<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StoreOrderDetail extends Model
{
    use HasUuids;

    protected $table = 'store_order_details';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'store_order_id',
        'product_id',
        'product_price_id',   // NOT NULL
        'product_price',      // NOT NULL
        'requested_qty',
        'approved_qty',
        'received_qty',
        'note',
    ];

    protected $casts = [
        'requested_qty' => 'integer',
        'approved_qty'  => 'integer',
        'received_qty'  => 'integer',
        'product_price' => 'decimal:2',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }
}
