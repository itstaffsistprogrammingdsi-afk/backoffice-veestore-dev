<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EcommerceBanner extends Model
{
    protected $table = 'ecommerce_banners';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'image_url',
        'description',
        'status',
        'size',
        'mobile_image_url',
        'alt_text',
        'placement',
        'display_order',
        'action_type',
        'cta_label',
        'target_reference_id',
        'target_url',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (EcommerceBanner $banner) {
            if (empty($banner->getKey())) {
                $banner->{$banner->getKeyName()} =
                    (string) Str::uuid();
            }

            if (empty($banner->status)) {
                $banner->status = 'active';
            }

            if ($banner->display_order === null) {
                $banner->display_order = 0;
            }

            if (empty($banner->placement)) {
                $banner->placement = 'home';
            }
        });
    }

    public function getIsActiveAttribute(): bool
    {
        return (string) $this->status === 'active';
    }

    public function getIsCurrentlyRunningAttribute(): bool
    {
        $now = now();

        if ($this->starts_at && $this->starts_at->isAfter($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isBefore($now)) {
            return false;
        }

        return $this->is_active;
    }
}
