<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return view('marketplace.search', ['q' => $q, 'products' => collect(), 'authors' => collect(), 'posts' => collect()]);
        }

        $term = '%'.$q.'%';

        $products = Product::query()
            ->with(['author', 'category'])
            ->published()
            ->where(fn ($w) => $w
                ->where('title', 'like', $term)
                ->orWhere('slug', 'like', $term)
                ->orWhere('description', 'like', $term))
            ->orderByDesc('downloads_count')
            ->take(12)
            ->get();

        $authors = User::query()
            ->whereHas('products', fn ($q) => $q->where('status', 'published'))
            ->where(fn ($w) => $w
                ->where('name', 'like', $term)
                ->orWhere('username', 'like', $term)
                ->orWhere('display_name', 'like', $term))
            ->withCount(['products as published_count' => fn ($q) => $q->where('status', 'published')])
            ->take(6)
            ->get();

        $posts = collect();
        if (Schema::hasTable('blog_posts')) {
            try {
                $posts = \App\Models\BlogPost::query()
                    ->where('status', 'published')
                    ->where(fn ($w) => $w
                        ->where('slug', 'like', $term)
                        ->orWhere('title', 'like', $term))
                    ->take(4)
                    ->get();
            } catch (\Throwable) {}
        }

        return view('marketplace.search', compact('q', 'products', 'authors', 'posts'));
    }
}
