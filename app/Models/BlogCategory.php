<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $fillable = [
        'name_uk', 'name_en', 'slug', 'description_uk', 'description_en',
        'seo_title_uk', 'seo_title_en', 'seo_description_uk', 'seo_description_en',
        'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_category_post');
    }

    public function localized(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) ($this->{$field.'_'.$locale} ?: $this->{$field.'_uk'} ?: $this->{$field.'_en'} ?: '');
    }
}
