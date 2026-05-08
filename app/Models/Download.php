<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id', 'model_file_id', 'ip_address', 'downloaded_at'];

    protected $casts = ['downloaded_at' => 'datetime'];
}
