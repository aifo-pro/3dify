<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tip;
use App\Models\TipPayment;
use App\Services\AifoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TipController extends Controller
{
    public function redirect(Product $product)
    {
        try {
            return redirect()->route('products.show', $product);
        } catch (\Throwable $e) {
            try {
                Log::error('Tip shortcut redirect failed', [
                    'product_id' => $product->id,
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
            }
            error_log('[3dify-tip-redirect] '.$e->getMessage());

            return redirect()->route('products.index');
        }
    }

    public function store(Request $request, Product $product, AifoPaymentService $payments)
    {
        abort_unless($product->status === 'published', 404);
        abort_if($product->user_id === $request->user()->id, 422, __('Не можна задонатити самому собі.'));

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:50000'],
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        $tip = null;
        $tipPayment = null;

        try {
            logger()->info('tip.store.start', [
                'product_id' => $product->id,
                'slug' => $product->slug,
                'user_id' => $request->user()->id,
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
                    ->with('error', __('Оплата тимчасово недоступна: додайте в адмінці Merchant ID (shop_id) і Secret key вебхука (HMAC) — вони потрібні для API aifo.pro v2. Для старого API також можна вказати endpoint і API key (Bearer).'));
            }

            $checkoutUrl = trim((string) ($tipPayment->payload['checkout_url'] ?? ''));
            if ($checkoutUrl === '' || ! $this->isAllowedPaymentRedirectUrl($checkoutUrl)) {
                $tipPayment->delete();
                $tip->delete();

                return redirect()
                    ->route('products.show', $product)
                    ->with('error', __('Не вдалося отримати коректне посилання на оплату від AIFO. Спробуйте ще раз або зверніться до підтримки.'));
            }

            return redirect()->away($checkoutUrl);
        } catch (\Throwable $e) {
            try {
                Log::error('Tip checkout failed', [
                    'product_id' => $product->id,
                    'tip_id' => $tip?->id,
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            } catch (\Throwable) {
                // Avoid secondary 500 if logging path is not writable.
            }

            try {
                report($e);
            } catch (\Throwable) {
            }

            error_log(sprintf(
                '[3dify-tip] %s: %s @ %s:%d',
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            try {
                if ($tipPayment instanceof TipPayment) {
                    $tipPayment->delete();
                }
            } catch (\Throwable) {
            }

            try {
                if ($tip instanceof Tip && $tip->exists) {
                    $tip->delete();
                }
            } catch (\Throwable) {
            }

            try {
                return redirect()
                    ->route('products.show', $product)
                    ->with('error', __('Тимчасова помилка при оплаті подяки. Якщо вона повторюється, перевірте лог сервера та міграції бази (таблиці tips / tip_payments).'));
            } catch (\Throwable $redirectException) {
                error_log('[3dify-tip] redirect failed: '.$redirectException->getMessage());

                return response(
                    __('Тимчасова помилка сервера. Перевірте права на storage/logs та php artisan migrate.'),
                    503
                );
            }
        }
    }

    /**
     * Only http(s) with a host — avoids Throwable from redirect()->away().
     * We avoid FILTER_VALIDATE_URL alone: some gateways return URLs that fail strict validation.
     */
    private function isAllowedPaymentRedirectUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '';
    }
}
