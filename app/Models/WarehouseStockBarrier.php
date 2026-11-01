<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WarehouseStockBarrier extends Model
{
    use HasUuids;

    protected $table = 'warehouse_stock_barriers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'stock_barrier',
        'tolerance_percentage',
    ];

    protected $casts = [
        'stock_barrier'        => 'integer',
        'tolerance_percentage' => 'decimal:2',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
