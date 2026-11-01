<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProductSpecification extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'product_specifications';

    // id UUID, bukan auto increment
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'value',
    ];
}
