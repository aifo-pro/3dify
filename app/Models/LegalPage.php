<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = [
        'slug',
        'locale',
        'title',
        'subtitle',
        'body',
        'meta_title',
        'meta_description',
        'is_published',
        'sort_order',
        'updated_by_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Canonical list of slugs the application creates by default.
     * Each entry: slug, label (UK), default sort, footer column.
     *
     * @return array<int, array{slug:string,label_uk:string,label_en:string,column:string}>
     */
    public static function defaultSlugs(): array
    {
        return [
            ['slug' => 'publishing-rules', 'label_uk' => 'Правила публікації', 'label_en' => 'Publishing rules', 'column' => 'authors'],
            ['slug' => 'copyright',        'label_uk' => 'Авторські права',    'label_en' => 'Copyright',        'column' => 'authors'],
            ['slug' => 'terms',            'label_uk' => 'Умови використання', 'label_en' => 'Terms of service', 'column' => 'authors'],
            ['slug' => 'contact',          'label_uk' => 'Контакти',           'label_en' => 'Contact',          'column' => 'support'],
            ['slug' => 'faq',              'label_uk' => 'Поширені питання',   'label_en' => 'FAQ',              'column' => 'support'],
            ['slug' => 'privacy',          'label_uk' => 'Політика приватності', 'label_en' => 'Privacy policy', 'column' => 'support'],
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeForLocale(Builder $q, string $locale): Builder
    {
        return $q->where('locale', $locale);
    }

    /**
     * Find a page by slug honouring the active locale, with a UK fallback.
     */
    public static function lookup(string $slug, ?string $locale = null): ?self
    {
        $locale = $locale ?: app()->getLocale();

        return static::query()->published()->where('slug', $slug)->where('locale', $locale)->first()
            ?? static::query()->published()->where('slug', $slug)->where('locale', 'uk')->first()
            ?? static::query()->published()->where('slug', $slug)->orderBy('id')->first();
    }
}
