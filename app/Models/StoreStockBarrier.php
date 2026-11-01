<?php
// app/Models/StoreStockBarrier.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StoreStockBarrier extends Model
{
    use HasUuids;

    protected $table = 'store_stock_barriers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'store_id',
        'product_id',
        'stock_barrier',
        'tolerance_percentage',
    ];

    protected $casts = [
        'stock_barrier'         => 'integer',
        'tolerance_percentage'  => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}