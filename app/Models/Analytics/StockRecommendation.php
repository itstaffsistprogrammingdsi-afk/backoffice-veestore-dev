<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockRecommendation extends Model
{
    use HasUuids;
    protected $connection = 'immostore_backoffice';
    protected $table = 'analytics_stock_recommendations';
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = ['forecast_qty_7d'=>'decimal:4','average_daily_demand'=>'decimal:4','generated_at'=>'datetime','reviewed_at'=>'datetime'];
    public function product() { return $this->belongsTo(SourceProduct::class, 'product_id'); }
    public function store() { return $this->belongsTo(SourceStore::class, 'store_id'); }
    public function reviewer() { return $this->belongsTo(SourceUser::class, 'reviewed_by'); }
}
