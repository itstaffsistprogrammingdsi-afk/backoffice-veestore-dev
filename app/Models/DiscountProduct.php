<?php
// app/Models/DiscountProduct.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountProduct extends Model
{
    protected $table = 'discount_products';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // tabel ini biasanya tanpa timestamps; ubah ke true jika ada

    protected $fillable = [
        'discount_id',
        'product_id',
        'applied_to',
        'store_id',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}