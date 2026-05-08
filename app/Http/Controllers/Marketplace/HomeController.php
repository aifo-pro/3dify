<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke()
    {
        $emptyStats = [
            'products' => 0,
            'free_products' => 0,
            'authors' => 0,
            'categories' => 0,
            'paid_orders' => 0,
            'downloads' => 0,
            'views' => 0,
        ];

        if (! Schema::hasTable('products')) {
            return view('marketplace.home', [
                'featuredProducts' => collect(),
                'popularProducts' => collect(),
                'latestProducts' => collect(),
                'freeProducts' => collect(),
                'categories' => collect(),
                'stats' => $emptyStats,
            ]);
        }

        $stats = [
            'products' => Product::query()->published()->count(),
            'free_products' => Product::query()->published()->where('is_free', true)->count(),
            'authors' => Schema::hasTable('users')
                ? User::query()->whereHas('products', fn ($q) => $q->where('status', 'published'))->count()
                : 0,
            'categories' => Schema::hasTable('categories')
                ? Category::query()->where('is_active', true)->count()
                : 0,
            'paid_orders' => Schema::hasTable('orders')
                ? Order::query()->where('status', 'paid')->count()
                : 0,
            'downloads' => (int) Product::query()->published()->sum('downloads_count'),
            'views' => (int) Product::query()->published()->sum('views_count'),
        ];

        return view('marketplace.home', [
            'featuredProducts' => Product::query()->with(['author', 'category'])->published()->where('is_featured', true)->latest('published_at')->take(8)->get(),
            'popularProducts' => Product::query()->with(['author', 'category'])->published()->orderByDesc('views_count')->latest('published_at')->take(8)->get(),
            'latestProducts' => Product::query()->with(['author', 'category'])->published()->latest('published_at')->take(8)->get(),
            'freeProducts' => Product::query()->with(['author', 'category'])->published()->where('is_free', true)->latest('published_at')->take(4)->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->take(8)->get(),
            'stats' => $stats,
        ]);
    }
}
