<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';

    public $timestamps = false;

    protected $fillable = [
        'country_name',
        'iso2',
        'iso3',
        'phone_code',
    ];
}
