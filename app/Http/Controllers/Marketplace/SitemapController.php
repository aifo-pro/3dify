<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    /** Sitemap index referencing every per-type sitemap. */
    public function index()
    {
        $sitemaps = [
            ['loc' => route('sitemap.pages'),      'lastmod' => now()->toAtomString()],
            ['loc' => route('sitemap.models'),     'lastmod' => $this->latest(Product::class)],
            ['loc' => route('sitemap.categories'), 'lastmod' => $this->latest(Category::class)],
            ['loc' => route('sitemap.authors'),    'lastmod' => $this->latest(User::class)],
            ['loc' => route('sitemap.blog'),       'lastmod' => now()->toAtomString()],
        ];

        return $this->xml('marketplace.sitemap-index', compact('sitemaps'));
    }

    /** Static / evergreen pages. */
    public function pages()
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('authors.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('blog.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('makes.gallery'), 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['loc' => route('leaderboard'), 'priority' => '0.5', 'changefreq' => 'weekly'],
        ];

        return $this->xml('marketplace.sitemap', compact('urls'));
    }

    public function models()
    {
        $urls = [];

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
                            ->map(fn ($url) => ['loc' => $url, 'title' => $p->localized('title')])
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

        return $this->xml('marketplace.sitemap', compact('urls'));
    }

    public function categories()
    {
        $urls = [];

        if (Schema::hasTable('categories')) {
            Category::query()->where('is_active', true)->orderBy('sort_order')->each(function ($cat) use (&$urls) {
                $urls[] = [
                    'loc' => route('categories.show', $cat),
                    'lastmod' => optional($cat->updated_at)->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
            });
        }

        return $this->xml('marketplace.sitemap', compact('urls'));
    }

    public function authors()
    {
        $urls = [];

        if (Schema::hasTable('users')) {
            User::query()
                ->whereHas('products', fn ($q) => $q->where('status', 'published'))
                ->select(['id', 'username', 'updated_at'])
                ->orderByDesc('updated_at')
                ->each(function ($u) use (&$urls) {
                    $urls[] = [
                        'loc' => route('authors.show', ['user' => $u->username ?: $u->id]),
                        'lastmod' => optional($u->updated_at)->toAtomString(),
                        'priority' => '0.6',
                        'changefreq' => 'weekly',
                    ];
                });
        }

        return $this->xml('marketplace.sitemap', compact('urls'));
    }

    private function latest(string $model): string
    {
        try {
            $max = $model::query()->max('updated_at');

            return $max ? \Illuminate\Support\Carbon::parse($max)->toAtomString() : now()->toAtomString();
        } catch (\Throwable) {
            return now()->toAtomString();
        }
    }

    private function xml(string $view, array $data)
    {
        return response()
            ->view($view, $data)
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
