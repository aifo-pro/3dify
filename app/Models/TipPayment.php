<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipPayment extends Model
{
    protected $fillable = [
        'tip_id',
        'provider',
        'provider_payment_id',
        'status',
        'amount',
        'currency',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function tip()
    {
        return $this->belongsTo(Tip::class);
    }
}

