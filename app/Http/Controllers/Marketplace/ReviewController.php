<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Notifications\NewReviewNotification;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:3000'],
        ]);

        $user = $request->user();

        // Block author from reviewing own model.
        abort_if($user->id === $product->user_id, 422, 'Cannot review own model.');

        $isVerifiedBuyer = $product->is_free
            ? $product->downloads()->where('user_id', $user->id)->exists()
            : OrderItem::query()
                ->where('product_id', $product->id)
                ->where('author_id', '!=', $user->id)
                ->whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('status', 'paid');
                })
                ->exists();

        $review = ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            [
                'rating' => $data['rating'],
                'body' => $data['body'] ?? null,
                'is_verified_buyer' => $isVerifiedBuyer,
                'status' => 'published',
            ]
        );

        if ($review->wasRecentlyCreated && $product->user_id !== $user->id) {
            $product->author?->notify(new NewReviewNotification($product, $review));
        }

        return back()
            ->with('status', __('Дякуємо за відгук!'))
            ->withFragment('reviews');
    }

    public function destroy(Request $request, Product $product, ProductReview $review)
    {
        abort_unless($review->product_id === $product->id, 404);

        $user = $request->user();
        $owns = $user->id === $review->user_id;
        abort_unless($owns || $user->canModerate(), 403);

        $review->delete();

        return back()->with('status', __('Відгук видалено.'))->withFragment('reviews');
    }
}
