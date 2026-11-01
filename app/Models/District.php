<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'regency_code',
        'name',
    ];

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_code', 'code');
    }

    public function villages()
    {
        return $this->hasMany(Village::class, 'district_code', 'code');
    }
}