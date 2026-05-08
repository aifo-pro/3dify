<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value'];

    protected $casts = ['value' => 'array'];

    public static function value(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }
}
