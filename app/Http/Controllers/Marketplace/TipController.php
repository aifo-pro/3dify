<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tip;
use App\Notifications\NewTipNotification;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function redirect(Product $product)
    {
        return redirect()->route('products.show', $product);
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->status === 'published', 404);
        abort_if($product->user_id === $request->user()->id, 422, __('Не можна задонатити самому собі.'));

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:50000'],
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        // Local marketplace mode: mark tips as paid immediately. Paid tips are
        // included in PayoutService and become available in the author's balance.
        $tip = Tip::create([
            'product_id' => $product->id,
            'author_id' => $product->user_id,
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'currency' => 'UAH',
            'message' => $data['message'] ?? null,
            'status' => Tip::STATUS_PAID,
        ]);

        $product->author?->notify(new NewTipNotification($tip));

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Дякуємо! :amount грн зараховано на баланс автора.', [
                'amount' => number_format((float) $data['amount'], 2, '.', ' '),
            ]));
    }
}
