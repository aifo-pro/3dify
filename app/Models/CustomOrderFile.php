<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CustomOrderFile extends Model
{
    protected $fillable = [
        'custom_order_id',
        'message_id',
        'user_id',
        'purpose',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }

    public function message()
    {
        return $this->belongsTo(CustomOrderMessage::class, 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }
}
