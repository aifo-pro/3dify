<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class ProductBundle extends Model
{
    use HasLocalizedFields;

    protected $fillable = [
        'user_id', 'slug', 'title', 'description', 'cover_path',
        'price', 'currency', 'discount_percent', 'is_active',
    ];

    protected $casts = [
        'title'            => 'array',
        'description'      => 'array',
        'price'            => 'decimal:2',
        'discount_percent' => 'integer',
        'is_active'        => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->belongsToMany(Product::class, 'product_bundle_items', 'bundle_id', 'product_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_path);
    }

    /** Original total if bought separately. */
    public function originalTotal(): float
    {
        return (float) $this->items->sum(fn ($p) => (float) $p->price);
    }

    /** Savings amount. */
    public function savings(): float
    {
        return round($this->originalTotal() - (float) $this->price, 2);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
