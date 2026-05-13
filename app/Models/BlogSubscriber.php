<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogSubscriber extends Model
{
    protected $fillable = ['email', 'locale', 'is_active', 'verified_at', 'unsubscribe_token'];

    protected $casts = ['is_active' => 'boolean', 'verified_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (BlogSubscriber $subscriber) {
            $subscriber->unsubscribe_token ??= Str::random(48);
        });
    }
}
