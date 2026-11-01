<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Location extends Model
{
    use SoftDeletes;

    protected $table = 'locations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['location_parent_id', 'name', 'code', 'type'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relasi hirarkis
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'location_parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'location_parent_id');
    }
}
