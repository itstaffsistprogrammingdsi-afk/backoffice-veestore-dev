<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Courier extends Model
{
    use SoftDeletes;

    protected $table = 'couriers';

    public $incrementing = false;  
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'code',
        'phone',
        'contact_person',
        'email',
        'service_type',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}