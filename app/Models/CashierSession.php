<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashierSession extends Model
{
    use SoftDeletes;

    protected $table = 'cashier_sessions';

    protected $guarded = [];

    protected $casts = [
        'opened_at'      => 'datetime',
        'closed_at'      => 'datetime',
        'opening_cash'   => 'decimal:2',
        'expected_cash'  => 'decimal:2',
        'actual_cash'    => 'decimal:2',
        'difference'     => 'decimal:2',
        'total_sales'    => 'decimal:2',
        'total_transactions' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function storeVisitPayments()
    {
        return $this->hasMany(StoreVisitPayment::class, 'cashier_session_id');
    }
}