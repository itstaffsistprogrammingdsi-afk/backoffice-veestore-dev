<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    protected $table = 'warehouse_items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['warehouse_id', 'item_id'];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}