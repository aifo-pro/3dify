<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'popular');
        if (! in_array($sort, ['popular', 'new', 'models', 'downloads'], true)) {
            $sort = 'popular';
        }

        $authors = User::query()
            ->withCount([
                'followers',
                'products as published_products_count' => fn ($query) => $query->published(),
            ])
            ->withSum(['products as downloads_sum' => fn ($query) => $query->published()], 'downloads_count')
            ->where(function (Builder $query) {
                $query->where('role', 'author')
                    ->orWhereHas('products', fn ($q) => $q->published());
            })
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function (Builder $inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('display_name', 'like', $term)
                        ->orWhere('username', 'like', $term);
                });
            })
            ->when($sort === 'new', fn ($query) => $query->latest())
            ->when($sort === 'models', fn ($query) => $query->orderByDesc('published_products_count')->orderByDesc('id'))
            ->when($sort === 'downloads', fn ($query) => $query->orderByDesc('downloads_sum')->orderByDesc('published_products_count'))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('followers_count')->orderByDesc('downloads_sum')->orderByDesc('published_products_count'))
            ->paginate(12)
            ->withQueryString();

        return view('marketplace.authors.index', [
            'authors' => $authors,
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Request $request, string $user)
    {
        $author = User::query()
            ->where('username', $user)
            ->orWhere('id', $user)
            ->firstOrFail();

        $tab = $request->query('tab', 'models');
        if (! in_array($tab, ['models', 'free', 'popular', 'about'], true)) {
            $tab = 'models';
        }

        $sort = $request->input('sort', $tab === 'popular' ? 'downloads' : 'latest');
        if (! in_array($sort, ['latest', 'popular', 'downloads', 'free'], true)) {
            $sort = 'latest';
        }

        $base = Product::query()
            ->where('user_id', $author->id)
            ->published()
            ->with(['author', 'category']);

        $products = match ($tab) {
            'free' => (clone $base)->where('is_free', true)->latest('published_at')->paginate(12)->withQueryString(),
            'popular' => (clone $base)->orderByDesc('downloads_count')->orderByDesc('views_count')->paginate(12)->withQueryString(),
            'about' => null,
            default => (clone $base)
                ->when($sort === 'popular', fn ($q) => $q->orderByDesc('views_count')->orderByDesc('downloads_count'))
                ->when($sort === 'downloads', fn ($q) => $q->orderByDesc('downloads_count'))
                ->when($sort === 'free', fn ($q) => $q->where('is_free', true)->latest('published_at'))
                ->when($sort === 'latest', fn ($q) => $q->latest('published_at'))
                ->paginate(12)
                ->withQueryString(),
        };

        $productIds = Product::query()->where('user_id', $author->id)->published()->pluck('id');

        $stats = [
            'models' => Product::query()->where('user_id', $author->id)->published()->count(),
            'downloads' => (int) Product::query()->where('user_id', $author->id)->published()->sum('downloads_count'),
            'followers' => $author->followers()->count(),
            'likes' => Schema::hasTable('wishlists')
                ? (int) DB::table('wishlists')->whereIn('product_id', $productIds)->count()
                : 0,
            'sales' => Schema::hasTable('order_items') && Schema::hasTable('orders')
                ? OrderItem::query()
                    ->where('author_id', $author->id)
                    ->whereHas('order', fn (Builder $q) => $q->where('status', 'paid'))
                    ->count()
                : 0,
            'rating_avg' => 0.0,
            'rating_count' => 0,
        ];

        // Aggregate rating across the author's published models (EEAT signal).
        if (Schema::hasTable('product_reviews') && $productIds->isNotEmpty()) {
            $reviewAgg = DB::table('product_reviews')
                ->whereIn('product_id', $productIds)
                ->where('status', 'published')
                ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
                ->first();

            if ($reviewAgg && (int) $reviewAgg->cnt > 0) {
                $stats['rating_avg'] = round((float) $reviewAgg->avg_rating, 1);
                $stats['rating_count'] = (int) $reviewAgg->cnt;
            }
        }

        $isFollowing = $author->isFollowedBy($request->user());
        $isSelf = $request->user()?->id === $author->id;

        return view('marketplace.authors.show', [
            'author' => $author,
            'tab' => $tab,
            'sort' => $sort,
            'products' => $products,
            'stats' => $stats,
            'isFollowing' => $isFollowing,
            'isSelf' => $isSelf,
        ]);
    }
}
