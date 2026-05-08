<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $following = $user->following()
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->get();

        $orders = $user->orders()->with('items.product')->latest()->get();
        $products = $user->products()->latest()->get();
        $sales = OrderItem::query()
            ->where('author_id', $user->id)
            ->with(['product', 'order'])
            ->latest()
            ->get();

        $stats = [
            'purchases_count' => $orders->where('status', 'paid')->count(),
            'purchases_total' => (float) $orders->where('status', 'paid')->sum('total'),
            'models_count' => $products->count(),
            'models_published' => $products->where('status', 'published')->count(),
            'sales_count' => $sales->count(),
            'sales_total' => (float) $sales->sum('price'),
            'followers_count' => $user->followers()->count(),
            'following_count' => $following->count(),
            'wishlist_count' => $user->wishlist()->count(),
        ];

        return view('marketplace.dashboard.index', [
            'orders' => $orders,
            'products' => $products->take(6),
            'sales' => $sales->take(6),
            'following' => $following,
            'stats' => $stats,
        ]);
    }
}
