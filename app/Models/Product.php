<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasLocalizedFields, SoftDeletes;

    public const STATUSES = ['draft', 'pending', 'published', 'rejected', 'archived'];

    protected $fillable = [
        'user_id', 'category_id', 'license_id', 'slug', 'title', 'short_description', 'description',
        'status', 'moderation_note', 'price', 'currency', 'is_free', 'is_featured', 'cover_path',
        'gallery', 'views_count', 'downloads_count', 'published_at',
        'dim_x', 'dim_y', 'dim_z', 'recommended_materials',
        'print_profile_path', 'print_profile_name', 'print_profile_settings',
    ];

    protected $casts = [
        'title' => 'array',
        'short_description' => 'array',
        'description' => 'array',
        'gallery' => 'array',
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'recommended_materials' => 'array',
        'print_profile_settings' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function files()
    {
        return $this->hasMany(ModelFile::class);
    }

    public function previewFile()
    {
        return $this->hasOne(ModelFile::class)->where('is_preview', true);
    }

    public function downloads()
    {
        return $this->hasMany(Download::class);
    }

    public function makes()
    {
        return $this->hasMany(ProductMake::class)->latest();
    }

    public function comments()
    {
        return $this->hasMany(ProductComment::class)->latest();
    }

    public function reports()
    {
        return $this->hasMany(ProductReport::class)->latest();
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function getDisplayPriceAttribute(): string
    {
        return $this->is_free ? 'Безкоштовно' : number_format((float) $this->price, 2).' '.$this->currency;
    }
}
