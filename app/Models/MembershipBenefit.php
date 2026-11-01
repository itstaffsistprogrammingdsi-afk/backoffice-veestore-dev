<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- tambah
use Illuminate\Support\Str;

class MembershipBenefit extends Model
{
    use SoftDeletes; // <-- aktifkan soft delete

    protected $table = 'membership_benefits';

    // UUID primary key
    public $incrementing = false;
    protected $keyType = 'string';

    // kolom data
    protected $fillable = [
        'membership_id', // FK ke memberships.id
        'name',
        'value',
        'type',
    ];

    // tabel ini tidak punya created_at/updated_at
    public $timestamps = false;

    // cast deleted_at agar berupa Carbon (rapi)
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

    // relasi ke Membership
    public function membership()
    {
        // pakai withTrashed agar tetap terbaca meski membership di-soft-delete
        return $this->belongsTo(\App\Models\Membership::class, 'membership_id', 'id')
            ->withTrashed(); // <-- opsional tapi dianjurkan jika Membership juga soft delete
    }
}