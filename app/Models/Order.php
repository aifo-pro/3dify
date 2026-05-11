<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_CREATED = 'created';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = ['number', 'user_id', 'status', 'subtotal', 'total', 'currency', 'paid_at'];

    protected $casts = ['subtotal' => 'decimal:2', 'total' => 'decimal:2', 'paid_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function hasCompletedRefund(): bool
    {
        if ($this->relationLoaded('refundRequests')) {
            return $this->refundRequests
                ->whereIn('status', [RefundRequest::STATUS_APPROVED, RefundRequest::STATUS_REFUNDED])
                ->isNotEmpty();
        }

        return $this->refundRequests()
            ->whereIn('status', [RefundRequest::STATUS_APPROVED, RefundRequest::STATUS_REFUNDED])
            ->exists();
    }

    public function effectiveStatus(): string
    {
        return $this->hasCompletedRefund() ? self::STATUS_REFUNDED : (string) $this->status;
    }
}
