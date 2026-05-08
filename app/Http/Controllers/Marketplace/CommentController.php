<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComment;
use App\Notifications\NewCommentNotification;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:product_comments,id'],
        ]);

        // Make sure the parent (if any) belongs to the same product.
        if (! empty($data['parent_id'])) {
            $parent = ProductComment::query()->find($data['parent_id']);
            abort_unless($parent && $parent->product_id === $product->id, 422);
        }

        $comment = ProductComment::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
            'status' => 'published',
        ]);

        // Notify model author of new comment (skip self-comments).
        if ($product->user_id !== $request->user()->id) {
            $product->author?->notify(new NewCommentNotification($product, $comment));
        }

        return back()
            ->with('status', __('Коментар опубліковано.'))
            ->withFragment('comments');
    }

    public function destroy(Product $product, ProductComment $comment, Request $request)
    {
        abort_unless($comment->product_id === $product->id, 404);

        $user = $request->user();
        $owns = $user->id === $comment->user_id;
        $isAuthor = $user->id === $product->user_id;
        abort_unless($owns || $isAuthor || $user->canModerate(), 403);

        $comment->delete();

        return back()->with('status', __('Коментар видалено.'))->withFragment('comments');
    }
}
