<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderMilestone extends Model
{
    protected $fillable = ['custom_order_id', 'title', 'description', 'sort_order', 'status', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }
}
