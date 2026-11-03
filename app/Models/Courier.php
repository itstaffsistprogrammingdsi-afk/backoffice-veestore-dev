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

    protected $appends = ['status_label', 'service_type_label'];

    public const STATUS_LABELS = [
        'active' => 'Aktif',
        'non_active' => 'Nonaktif',
    ];

    public const SERVICE_TYPE_LABELS = [
        'regular' => 'Regular',
        'express' => 'Express',
        'cargo' => 'Cargo',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '-';
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return self::SERVICE_TYPE_LABELS[$this->service_type] ?? '-';
    }

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