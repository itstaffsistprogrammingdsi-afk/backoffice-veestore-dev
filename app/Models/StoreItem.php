<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreItem extends Model
{
    protected $table = 'store_items';

    // pakai UUID? sesuaikan dengan tabel Anda
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['store_id', 'item_id', 'category', 'created_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'item_id');
    }
}