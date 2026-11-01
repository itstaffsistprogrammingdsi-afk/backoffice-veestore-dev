<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelRun extends Model
{
    use HasUuids;
    protected $connection = 'immostore_backoffice';
    protected $table = 'analytics_model_runs';
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = ['parameters'=>'array','metrics'=>'array','training_from'=>'date','training_to'=>'date','started_at'=>'datetime','completed_at'=>'datetime'];
}
