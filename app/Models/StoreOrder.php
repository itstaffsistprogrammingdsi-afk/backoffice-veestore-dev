<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOrder extends Model
{
    use HasUuids;

    protected $table = 'store_orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'order_number',
        'store_id',
        'warehouse_id',
        'status',        // draft/requested/approved/rejected
        'requested_at',
        'approved_at',
        'approved_by',
        'created_by',
        'date',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date' => 'date',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(StoreOrderDetail::class, 'store_order_id');
    }

    /** helper: boleh di-edit? */
    public function isEditable(): bool
    {
        return in_array(strtolower($this->status), ['draft', 'rejected']);
    }
}
