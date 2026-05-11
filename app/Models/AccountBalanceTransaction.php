<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBalanceTransaction extends Model
{
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'user_id',
        'order_id',
        'refund_request_id',
        'type',
        'status',
        'amount',
        'currency',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function refundRequest()
    {
        return $this->belongsTo(RefundRequest::class);
    }
}
