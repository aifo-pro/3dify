<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'locale', 'subject', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
