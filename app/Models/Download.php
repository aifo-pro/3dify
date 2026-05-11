<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id', 'model_file_id', 'ip_address', 'downloaded_at'];

    protected $casts = ['downloaded_at' => 'datetime'];

    public function file()
    {
        return $this->belongsTo(ModelFile::class, 'model_file_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
