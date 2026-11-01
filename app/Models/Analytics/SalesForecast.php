<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SalesForecast extends Model
{
    use HasUuids;
    protected $connection = 'immostore_backoffice';
    protected $table = 'analytics_sales_forecasts';
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = ['forecast_date'=>'date','generated_at'=>'datetime','predicted_qty'=>'decimal:4','predicted_revenue'=>'decimal:2','confidence'=>'decimal:4'];
    public function product() { return $this->belongsTo(SourceProduct::class, 'product_id'); }
    public function store() { return $this->belongsTo(SourceStore::class, 'store_id'); }
    public function warehouse() { return $this->belongsTo(SourceWarehouse::class, 'warehouse_id'); }
}
