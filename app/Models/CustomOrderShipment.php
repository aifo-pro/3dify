<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderShipment extends Model
{
    protected $fillable = ['custom_order_id', 'carrier', 'tracking_number', 'status', 'shipped_at', 'delivered_at', 'metadata'];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function events()
    {
        return $this->hasMany(CustomOrderTrackingEvent::class, 'shipment_id')->latest('happened_at');
    }
}
