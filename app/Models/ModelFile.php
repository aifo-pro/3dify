<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelFile extends Model
{
    public const ALLOWED_EXTENSIONS = ['stl', 'obj', 'glb', 'gltf', 'zip', '3mf'];

    protected $fillable = ['product_id', 'type', 'disk', 'path', 'original_name', 'extension', 'size', 'is_preview', 'validation_warnings'];

    protected $casts = ['is_preview' => 'boolean', 'validation_warnings' => 'array'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
