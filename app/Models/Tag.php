<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasLocalizedFields;

    protected $fillable = ['slug', 'name'];

    protected $casts = ['name' => 'array'];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
