<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'current_location_id',
        'current_condition_id',
        'expired_date',
        'rfid_tag_id',
        'rfid_installed_by',
        'status',
        'warehouse_inbound_detail_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}