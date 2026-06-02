<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderStatusLog extends Model
{
    protected $fillable = ['custom_order_id', 'user_id', 'from_status', 'to_status', 'note', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
