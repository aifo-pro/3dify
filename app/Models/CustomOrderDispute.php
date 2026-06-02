<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderDispute extends Model
{
    protected $fillable = [
        'custom_order_id',
        'opened_by',
        'status',
        'reason',
        'description',
        'resolution_note',
        'refund_amount',
        'resolved_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
