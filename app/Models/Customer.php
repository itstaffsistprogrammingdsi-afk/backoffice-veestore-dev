<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'address',
        'email',
        'wa_number',
        'gender',
        'date_of_birth',
        'user_id',
        'referral_code',
        'province_code',
        'regency_code',
        'village_code',
        'district_code',
        'postal_code',
        'registration_channel',
        'registration_source_id',
        'is_guest',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_guest' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function registrationSource(): BelongsTo
    {
        return $this->belongsTo(
            Store::class,
            'registration_source_id',
            'id'
        );
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }
}
