<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- tambah
use Illuminate\Support\Str;

class Membership extends Model
{
    use SoftDeletes; // <-- aktifkan soft delete

    protected $table = 'memberships';

    // pakai UUID
    public $incrementing = false;
    protected $keyType = 'string';

    // hanya 1 kolom data
    protected $fillable = ['name'];

    // tabel ini tidak punya created_at/updated_at
    public $timestamps = false;

    // cast deleted_at biar jadi datetime (Carbon)
    protected $casts = [
        'deleted_at' => 'datetime',
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
}