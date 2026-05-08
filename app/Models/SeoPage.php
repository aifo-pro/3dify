<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = ['route_name', 'locale', 'title', 'description', 'canonical_url', 'open_graph'];

    protected $casts = ['open_graph' => 'array'];
}
