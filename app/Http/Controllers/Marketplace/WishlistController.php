<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();

        $exists = $user->wishlist()->where('product_id', $product->id)->exists();

        if ($exists) {
            $user->wishlist()->detach($product->id);
            $state = false;
        } else {
            $user->wishlist()->syncWithoutDetaching([$product->id]);
            $state = true;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'wishlisted' => $state,
                'count' => $product->wishlistedBy()->count(),
            ]);
        }

        return back()->with('status', $state
            ? __('Додано до обраного.')
            : __('Видалено з обраного.'));
    }

    public function index(Request $request)
    {
        $items = $request->user()->wishlist()
            ->with(['author', 'category'])
            ->where('status', 'published')
            ->orderByDesc('wishlists.created_at')
            ->paginate(12);

        return view('marketplace.wishlist.index', [
            'items' => $items,
        ]);
    }
}
