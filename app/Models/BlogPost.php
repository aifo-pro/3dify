<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BlogPost extends Model
{
    public const STATUSES = ['draft', 'published', 'scheduled'];

    protected $fillable = [
        'user_id', 'title_uk', 'title_en', 'slug', 'excerpt_uk', 'excerpt_en',
        'content_uk', 'content_en', 'cover_image', 'cover_alt_uk', 'cover_alt_en',
        'seo_title_uk', 'seo_title_en', 'seo_description_uk', 'seo_description_en',
        'seo_keywords', 'og_image', 'status', 'published_at', 'notification_sent_at',
        'views', 'is_featured', 'allow_index',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'is_featured' => 'boolean',
        'allow_index' => 'boolean',
        'views' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_category_post');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->where('allow_index', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function localized(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = $this->{$field.'_'.$locale} ?: $this->{$field.'_uk'} ?: $this->{$field.'_en'} ?: '';

        return (string) $value;
    }

    public function getUrlAttribute(): string
    {
        return route('blog.show', $this->slug);
    }

    public function getCoverUrlAttribute(): ?string
    {
        $path = $this->cover_image ?: $this->og_image;
        if (! $path) {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        return str_starts_with($url, 'http') ? $url : url($url);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        $path = $this->og_image ?: $this->cover_image;
        if (! $path) {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        return str_starts_with($url, 'http') ? $url : url($url);
    }

    public function getLocalizedTitleAttribute(): string
    {
        return $this->localized('title');
    }

    public function getLocalizedExcerptAttribute(): string
    {
        return $this->localized('excerpt');
    }

    public function getLocalizedContentAttribute(): string
    {
        return $this->localized('content');
    }
}
