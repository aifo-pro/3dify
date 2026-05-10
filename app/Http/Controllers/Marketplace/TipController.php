<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tip;
use App\Services\AifoPaymentService;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function redirect(Product $product)
    {
        return redirect()->route('products.show', $product);
    }

    public function store(Request $request, Product $product, AifoPaymentService $payments)
    {
        abort_unless($product->status === 'published', 404);
        abort_if($product->user_id === $request->user()->id, 422, __('Не можна задонатити самому собі.'));

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:50000'],
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        $tip = Tip::create([
            'product_id' => $product->id,
            'author_id' => $product->user_id,
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'currency' => 'UAH',
            'message' => $data['message'] ?? null,
            'status' => Tip::STATUS_PENDING,
        ]);

        $tipPayment = $payments->createTipPayment($tip);

        if ($tipPayment === null) {
            $tip->delete();

            return redirect()
                ->route('products.show', $product)
                ->with('error', __('Оплата тимчасово недоступна: не налаштовано AIFO в адмінці (endpoint або API-ключ).'));
        }

        $checkoutUrl = (string) ($tipPayment->payload['checkout_url'] ?? '');
        if ($checkoutUrl === '') {
            $tipPayment->delete();
            $tip->delete();

            return redirect()
                ->route('products.show', $product)
                ->with('error', __('Не вдалося отримати посилання на оплату від AIFO. Спробуйте ще раз або зверніться до підтримки.'));
        }

        return redirect()->away($checkoutUrl);
    }
}
