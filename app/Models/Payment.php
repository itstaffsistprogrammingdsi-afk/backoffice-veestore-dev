<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'change_amount' => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'completed_at'  => 'datetime',
        'voided_at'     => 'datetime',
    ];
}