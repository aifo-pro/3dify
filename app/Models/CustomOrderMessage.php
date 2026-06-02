<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomOrderMessage extends Model
{
    protected $fillable = ['custom_order_id', 'user_id', 'role', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function files()
    {
        return $this->hasMany(CustomOrderFile::class, 'message_id');
    }
}
