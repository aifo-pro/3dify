<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\RefundRequest;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $following = $user->following()
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->get();

        $orders = $user->orders()->with(['items.product', 'refundRequests'])->latest()->get();
        $products = $user->products()->latest()->get();
        $sales = OrderItem::query()
            ->where('author_id', $user->id)
            ->with(['product', 'order'])
            ->whereHas('order', function ($query) {
                $query->where('status', 'paid')
                    ->whereDoesntHave('refundRequests', function ($refunds) {
                        $refunds->whereIn('status', [RefundRequest::STATUS_APPROVED, RefundRequest::STATUS_REFUNDED]);
                    });
            })
            ->latest()
            ->get();
        $paidOrders = $orders->filter(fn ($order) => $order->status === 'paid' && ! $order->hasCompletedRefund());

        $stats = [
            'purchases_count' => $paidOrders->count(),
            'purchases_total' => (float) $paidOrders->sum('total'),
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
