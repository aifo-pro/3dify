<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code', 'description', 'type', 'value', 'currency',
        'usage_limit', 'used_count', 'min_order_amount',
        'starts_at', 'expires_at', 'is_active', 'author_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function redemptions()
    {
        return $this->hasMany(PromoCodeRedemption::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isUsable(?float $orderAmount = null): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }
        if ($orderAmount !== null && $this->min_order_amount && $orderAmount < (float) $this->min_order_amount) {
            return false;
        }
        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENT) {
            return round($amount * ((float) $this->value / 100), 2);
        }
        return min($amount, (float) $this->value);
    }
}
