<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MakeController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $request->file('image')->store('makes/'.$product->id, 'public');

        // Auto-approve for the product author / moderators, otherwise pending.
        $user = $request->user();
        $autoApprove = $user->canModerate() || $user->id === $product->user_id;

        ProductMake::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'image_path' => $path,
            'comment' => $data['comment'] ?? null,
            'status' => $autoApprove ? 'approved' : 'pending',
        ]);

        return back()
            ->with('status', $autoApprove
                ? __('Дякуємо! Ваше фото опубліковано.')
                : __('Дякуємо! Фото зʼявиться після перевірки автором.'))
            ->withFragment('makes');
    }

    public function destroy(Product $product, ProductMake $make, Request $request)
    {
        abort_unless($make->product_id === $product->id, 404);

        $user = $request->user();
        $owns = $user->id === $make->user_id;
        $isAuthor = $user->id === $product->user_id;
        abort_unless($owns || $isAuthor || $user->canModerate(), 403);

        Storage::disk('public')->delete($make->image_path);
        $make->delete();

        return back()->with('status', __('Фото видалено.'))->withFragment('makes');
    }

    public function moderate(Request $request, Product $product, ProductMake $make)
    {
        abort_unless($make->product_id === $product->id, 404);

        $user = $request->user();
        $isAuthor = $user->id === $product->user_id;
        abort_unless($isAuthor || $user->canModerate(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $make->update(['status' => $data['status']]);

        return back()->with('status', __('Статус оновлено.'))->withFragment('makes');
    }
}
