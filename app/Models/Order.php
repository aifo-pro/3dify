<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
}
