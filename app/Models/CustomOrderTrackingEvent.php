<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderTrackingEvent extends Model
{
    protected $fillable = ['shipment_id', 'status', 'location', 'description', 'happened_at', 'payload'];

    protected $casts = [
        'happened_at' => 'datetime',
        'payload' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(CustomOrderShipment::class, 'shipment_id');
    }
}
