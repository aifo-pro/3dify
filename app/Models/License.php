<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasLocalizedFields;

    protected $fillable = ['slug', 'name', 'description', 'allows_commercial_use', 'requires_attribution'];

    protected $casts = ['name' => 'array', 'description' => 'array', 'allows_commercial_use' => 'boolean', 'requires_attribution' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
