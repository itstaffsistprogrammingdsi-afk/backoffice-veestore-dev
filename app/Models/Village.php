<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $table = 'villages';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'district_code',
        'name',
        'latitude',
        'longitude',
        'coordinate_source',
        'geocoded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geocoded_at' => 'datetime',
    ];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
