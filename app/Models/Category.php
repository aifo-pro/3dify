<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = ['parent_id', 'slug', 'name', 'description', 'image_path', 'is_active', 'sort_order'];

    protected $casts = ['name' => 'array', 'description' => 'array', 'is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
