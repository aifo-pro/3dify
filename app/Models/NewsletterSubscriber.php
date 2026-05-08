<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'name', 'locale', 'source', 'verified_at', 'unsubscribed_at', 'unsubscribe_token'];

    protected $casts = [
        'verified_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            if (! $sub->unsubscribe_token) {
                $sub->unsubscribe_token = Str::random(48);
            }
        });
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null;
    }
}
