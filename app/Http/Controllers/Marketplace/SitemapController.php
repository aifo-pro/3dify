<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = [];

        $urls[] = ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('blog.index'), 'priority' => '0.7', 'changefreq' => 'weekly'];

        if (Schema::hasTable('products')) {
            Product::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->select(['slug', 'updated_at', 'cover_path', 'gallery', 'title'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($products) use (&$urls) {
                    foreach ($products as $p) {
                        $images = collect([$p->cover_path, ...collect($p->gallery ?? [])->all()])
                            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
                            ->unique()
                            ->map(fn ($path) => $this->publicStorageUrl($path))
                            ->filter()
                            ->values()
                            ->map(fn ($url) => [
                                'loc' => $url,
                                'title' => $p->localized('title'),
                            ])
                            ->all();

                        $urls[] = [
                            'loc' => route('products.show', $p->slug),
                            'lastmod' => optional($p->updated_at)->toAtomString(),
                            'priority' => '0.8',
                            'changefreq' => 'weekly',
                            'images' => $images,
                        ];
                    }
                });
        }

        if (Schema::hasTable('categories')) {
            Category::query()->where('is_active', true)->each(function ($cat) use (&$urls) {
                $urls[] = [
                    'loc' => route('categories.show', $cat),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
            });
        }

        if (Schema::hasTable('blog_posts')) {
            BlogPost::query()
                ->published()
                ->indexable()
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($posts) use (&$urls) {
                    foreach ($posts as $post) {
                        $urls[] = [
                            'loc' => route('blog.show', $post->slug),
                            'lastmod' => optional($post->updated_at)->toAtomString(),
                            'priority' => '0.7',
                            'changefreq' => 'weekly',
                        ];
                    }
                });
        }

        if (Schema::hasTable('users')) {
            User::query()
                ->whereHas('products', fn ($q) => $q->where('status', 'published'))
                ->select(['id', 'username', 'updated_at'])
                ->each(function ($u) use (&$urls) {
                    $urls[] = [
                        'loc' => route('authors.show', ['user' => $u->username ?: $u->id]),
                        'lastmod' => optional($u->updated_at)->toAtomString(),
                        'priority' => '0.6',
                        'changefreq' => 'weekly',
                    ];
                });
        }

        return response()
            ->view('marketplace.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }

            $url = Storage::disk('public')->url($path);

            return str_starts_with($url, 'http') ? $url : url($url);
        } catch (\Throwable) {
            return null;
        }
    }
}
