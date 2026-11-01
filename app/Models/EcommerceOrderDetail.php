<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcommerceOrderDetail extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_details';

    protected $keyType = 'string';
    public $incrementing = false;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}