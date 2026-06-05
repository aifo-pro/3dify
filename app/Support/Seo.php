<?php

namespace App\Support;

/**
 * Centralised schema.org / JSON-LD builders. Each method returns an array ready
 * to be rendered with Seo::jsonLd(). Keeps structured data consistent and
 * testable across every page (Google 2024-2026, SGE / AI Overviews).
 */
class Seo
{
    /** Render one or more schema arrays as a <script type="application/ld+json"> tag. */
    public static function jsonLd(array $schema): string
    {
        return '<script type="application/ld+json">'
            .json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .'</script>';
    }

    public static function organization(): array
    {
        $settings = app(\App\Services\SiteSettings::class);
        $name = $settings->string('site.name', '3Dify');
        $logo = $settings->string('brand.logo_path');
        $base = rtrim((string) config('app.url'), '/');

        $sameAs = array_values(array_filter([
            $settings->string('social.twitter'),
            $settings->string('social.github'),
            $settings->string('social.telegram'),
            $settings->string('social.instagram'),
            $settings->string('social.youtube'),
        ]));

        $org = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $base.'/#organization',
            'name' => $name,
            'url' => $base,
        ];

        if ($logo) {
            $logoUrl = str_starts_with($logo, 'http') ? $logo : \Illuminate\Support\Facades\Storage::disk('public')->url($logo);
            $org['logo'] = ['@type' => 'ImageObject', 'url' => $logoUrl];
        }

        if ($sameAs) {
            $org['sameAs'] = $sameAs;
        }

        return $org;
    }

    public static function website(): array
    {
        $settings = app(\App\Services\SiteSettings::class);
        $name = $settings->string('site.name', '3Dify');
        $base = rtrim((string) config('app.url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'name' => $name,
            'url' => $base,
            'publisher' => ['@id' => $base.'/#organization'],
            'inLanguage' => app()->getLocale(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function breadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(fn ($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ], $items, array_keys($items))),
        ];
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $faqs
     */
    public static function faqPage(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_values(array_map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ], $faqs)),
        ];
    }

    /**
     * @param  array{value: float, count: int}|null  $rating  optional aggregate rating
     */
    public static function person(\App\Models\User $user, ?array $rating = null): array
    {
        $person = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => $user->profileUrl().'#person',
            'name' => $user->displayName(),
            'url' => $user->profileUrl(),
        ];

        if ($avatar = $user->avatarUrl()) {
            $person['image'] = $avatar;
        }

        if ($rating && ($rating['count'] ?? 0) > 0) {
            $person['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $rating['value'],
                'reviewCount' => (int) $rating['count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $bio = $user->localizedBio();
        if (filled($bio)) {
            $person['description'] = \Illuminate\Support\Str::limit(strip_tags($bio), 300);
        }

        $sameAs = array_values(array_filter([
            $user->website_url, $user->telegram_url, $user->instagram_url,
            $user->youtube_url, $user->github_url, $user->twitter_url,
        ]));
        if ($sameAs) {
            $person['sameAs'] = $sameAs;
        }

        return $person;
    }

    public static function profilePage(\App\Models\User $user, int $modelCount = 0): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => $user->profileUrl(),
            'name' => $user->displayName(),
            'mainEntity' => ['@id' => $user->profileUrl().'#person'],
            'dateCreated' => optional($user->created_at)->toAtomString(),
        ];
    }

    /**
     * Collection page (category / tag / authors list) with an ItemList.
     *
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public static function collectionPage(string $name, string $url, string $description, array $items = []): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'inLanguage' => app()->getLocale(),
        ];

        if ($items) {
            $schema['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($items),
                'itemListElement' => array_values(array_map(fn ($item, $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => $item['url'],
                    'name' => $item['name'],
                ], $items, array_keys($items))),
            ];
        }

        return $schema;
    }
}
