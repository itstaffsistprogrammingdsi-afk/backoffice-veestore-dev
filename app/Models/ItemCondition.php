<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ItemCondition extends Model
{
    use SoftDeletes;

    protected $table = 'item_conditions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (ItemCondition $itemCondition) {
            if (empty($itemCondition->{$itemCondition->getKeyName()})) {
                $itemCondition->{$itemCondition->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}