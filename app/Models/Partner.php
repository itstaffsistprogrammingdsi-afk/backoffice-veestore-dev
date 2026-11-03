<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use SoftDeletes;

    protected $table = 'partners';

    // UUID primary
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'default_credit_limit',
        'billing_cycle',
        'late_fee_type',
        'late_fee_rate',
        'status',
    ];

    protected $appends = ['status_label'];

    public const STATUS_LABELS = [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
    ];

    protected $casts = [
        'default_credit_limit' => 'decimal:2',
        'late_fee_rate' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '-';
    }
}
