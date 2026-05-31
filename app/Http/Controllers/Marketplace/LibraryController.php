<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function __invoke(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'paid')
            ->whereDoesntHave('refundRequests', fn ($q) => $q->whereIn('status', ['approved', 'refunded']))
            ->with([
                'items.product' => fn ($q) => $q->withTrashed()->with(['files' => fn ($f) => $f->where('is_preview', false), 'author']),
            ])
            ->latest('paid_at')
            ->paginate(20);

        return view('marketplace.library', compact('orders'));
    }
}
