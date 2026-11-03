<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- tambah
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    use SoftDeletes; // <-- aktifkan soft delete

    protected $table = 'payment_methods';

    // PK UUID
    public $incrementing = false;
    protected $keyType = 'string';

    // Hanya ada created_at, tidak ada updated_at
    const UPDATED_AT = null;

    protected $fillable = ['name', 'type'];

    protected $appends = ['type_label'];

    public const TYPE_LABELS = [
        'cash' => 'Cash',
        'non_cash' => 'Non Cash',
        'api' => 'API',
        'receivable' => 'Receivable',
    ];

    // Rapi: cast kolom waktu
    protected $casts = [
        'created_at' => 'datetime',
        'deleted_at' => 'datetime', // <-- penting untuk SoftDeletes
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? '-';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            if (empty($m->{$m->getKeyName()})) {
                $m->{$m->getKeyName()} = (string) Str::uuid();
            }
            // jika created_at tidak auto by DB, isi sekarang
            if (!$m->getAttribute('created_at')) {
                $m->setAttribute('created_at', now());
            }
        });
    }
}