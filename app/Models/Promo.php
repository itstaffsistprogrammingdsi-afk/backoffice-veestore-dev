<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promos';

    // UUID
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'name',
        'description',
        'code',
        'start_date',
        'end_date',
        'store_id',   // uuid[] (Postgres array)
        'all_store',  // bool
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'all_store'  => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Getter: store_id from postgres "{uuid1,uuid2}" => ['uuid1','uuid2']
     */
    public function getStoreIdAttribute($value)
    {
        if ($value === null || $value === '') return [];

        // Jika sudah array (kadang driver tertentu bisa return array)
        if (is_array($value)) return array_values(array_filter($value));

        // PostgreSQL array string: {a,b,c}
        $v = trim((string)$value);

        if ($v === '{}' ) return [];

        // Remove { }
        $v = trim($v, '{}');

        if ($v === '') return [];

        // Split by comma (uuid tidak mengandung koma)
        $parts = explode(',', $v);

        // Remove quotes if any
        $parts = array_map(function ($x) {
            $x = trim($x);
            $x = trim($x, '"');
            return $x;
        }, $parts);

        return array_values(array_filter($parts));
    }

    /**
     * Setter: ['uuid1','uuid2'] => "{uuid1,uuid2}"
     */
    public function setStoreIdAttribute($value)
    {
        if ($value === null || $value === '') {
            $this->attributes['store_id'] = null;
            return;
        }

        // kalau input string "{...}" biarkan
        if (is_string($value) && str_starts_with(trim($value), '{')) {
            $this->attributes['store_id'] = $value;
            return;
        }

        // pastikan array
        $arr = is_array($value) ? $value : [$value];
        $arr = array_values(array_filter(array_map('trim', $arr)));

        if (count($arr) === 0) {
            $this->attributes['store_id'] = null;
            return;
        }

        // format postgres array
        $this->attributes['store_id'] = '{' . implode(',', $arr) . '}';
    }
}
