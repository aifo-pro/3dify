<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderPayment extends Model
{
    protected $fillable = ['custom_order_id', 'provider', 'provider_payment_id', 'status', 'amount', 'currency', 'paid_at', 'payload'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }
}
