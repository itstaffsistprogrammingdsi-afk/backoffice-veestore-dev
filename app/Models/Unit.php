<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- tambahkan
use Illuminate\Support\Str;

class Unit extends Model
{
    use SoftDeletes; // <-- aktifkan

    protected $table = 'units';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name'];

    // opsional tapi rapi: cast kolom tanggal
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}