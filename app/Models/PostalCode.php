<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    protected $table = 'postal_codes';
    protected $primaryKey = 'postal_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'postal_code',
        'province_code',
        'regency_code',
        'district_code',
        'village_code',
    ];
}