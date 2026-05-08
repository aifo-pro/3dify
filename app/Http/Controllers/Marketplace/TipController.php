<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tip;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->status === 'published', 404);
        abort_if($product->user_id === $request->user()->id, 422, __('Не можна задонатити самому собі.'));

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000'],
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        // For demo purposes mark as paid immediately. In production this would
        // route through AifoPaymentService and a webhook to flip pending → paid.
        Tip::create([
            'product_id' => $product->id,
            'author_id' => $product->user_id,
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'currency' => $product->currency ?? 'EUR',
            'message' => $data['message'] ?? null,
            'status' => Tip::STATUS_PAID,
        ]);

        return back()->with('status', __('Дякуємо! :amount EUR відправлено автору.', ['amount' => number_format((float) $data['amount'], 2)]));
    }
}
