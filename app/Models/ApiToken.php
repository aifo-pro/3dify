<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token_hash', 'abilities', 'expires_at', 'last_used_at'];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new plain token + hash. Returns ['plain' => '...', 'hash' => '...'].
     */
    public static function generate(): array
    {
        $plain = Str::random(48);
        return ['plain' => $plain, 'hash' => hash('sha256', $plain)];
    }

    public static function findByPlain(string $plain): ?self
    {
        return static::where('token_hash', hash('sha256', $plain))->first();
    }
}
