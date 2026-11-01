<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    protected $table = 'voucher_usages';

    public $incrementing = false;
    protected $keyType = 'string';
}
