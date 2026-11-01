<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreVisitPayment extends Model
{
    protected $table = 'store_visit_payments';

    protected $guarded = [];

    public function cashierSession()
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }
}