<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogTag extends Model
{
    protected $fillable = ['name_uk', 'name_en', 'slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    public function localized(string $field = 'name', ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) ($this->{$field.'_'.$locale} ?: $this->{$field.'_uk'} ?: $this->{$field.'_en'} ?: '');
    }
}
