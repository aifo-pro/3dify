<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = [];

        $urls[] = ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'];

        if (Schema::hasTable('products')) {
            Product::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->select(['slug', 'updated_at'])
                ->orderByDesc('updated_at')
                ->chunk(500, function ($products) use (&$urls) {
                    foreach ($products as $p) {
                        $urls[] = [
                            'loc' => route('products.show', $p->slug),
                            'lastmod' => optional($p->updated_at)->toAtomString(),
                            'priority' => '0.8',
                            'changefreq' => 'weekly',
                        ];
                    }
                });
        }

        if (Schema::hasTable('categories')) {
            Category::query()->where('is_active', true)->each(function ($cat) use (&$urls) {
                $urls[] = [
                    'loc' => route('products.index', ['category' => $cat->slug]),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
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
}
