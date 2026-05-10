<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tip;
use App\Services\AifoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $tipPayment = null;

        try {
            $tipPayment = $payments->createTipPayment($tip);
        } catch (\Throwable $e) {
            Log::error('Tip AIFO checkout failed with exception', [
                'tip_id' => $tip->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $tip->delete();

            return redirect()
                ->route('products.show', $product)
                ->with('error', __('Не вдалося підключитися до платіжної системи. Перевірте секрет і доступ сервера до aifo.pro, або спробуйте пізніше.'));
        }

        if ($tipPayment === null) {
            $tip->delete();

            return redirect()
                ->route('products.show', $product)
                ->with('error', __('Оплата тимчасово недоступна: додайте в адмінці Merchant ID (shop_id) і Secret key вебхука (HMAC) — вони потрібні для API aifo.pro v2. Для старого API також можна вказати endpoint і API key (Bearer).'));
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
