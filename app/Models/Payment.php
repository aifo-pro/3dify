<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'provider', 'provider_payment_id', 'status', 'amount', 'currency', 'payload'];

    protected $casts = ['amount' => 'decimal:2', 'payload' => 'array'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
