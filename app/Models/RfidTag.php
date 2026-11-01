<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RfidTag extends Model
{
    use SoftDeletes; // aktifkan soft delete

    protected $table = 'rfid_tags';

    public $incrementing = false;
    protected $keyType = 'string';

    // model ini memang tidak punya created_at/updated_at
    public $timestamps = false;

    protected $fillable = ['serial_number', 'value'];

    // cast deleted_at supaya dikenali sebagai datetime
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // generate UUID otomatis
        static::creating(function ($m) {
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}