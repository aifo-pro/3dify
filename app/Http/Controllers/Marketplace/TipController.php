<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tip;
use App\Services\AifoPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

            logger()->info('tip.record.created', ['tip_id' => $tip->id]);

            $tipPayment = $payments->createTipPayment($tip);

            logger()->info('tip.payment.returned', [
                'tip_id' => $tip->id,
                'tip_payment_null' => $tipPayment === null,
                'tip_payment_id' => $tipPayment?->id,
            ]);

            if ($tipPayment === null) {
                $tip->delete();

                return redirect()
                    ->route('products.show', $product)
                    ->with('error', __('Оплата тимчасово недоступна: додайте в адмінці Merchant ID (shop_id) і Secret key вебхука (HMAC) — вони потрібні для API aifo.pro v2. Для старого API також можна вказати endpoint і API key (Bearer).'));
            }

            $checkoutUrl = trim((string) ($tipPayment->payload['checkout_url'] ?? ''));
            logger()->info('tip.checkout.url_resolved', [
                'tip_id' => $tip->id,
                'checkout_url_length' => strlen($checkoutUrl),
                'url_scheme' => $checkoutUrl !== '' ? (parse_url($checkoutUrl, PHP_URL_SCHEME) ?: '') : '',
            ]);

            if ($checkoutUrl === '' || ! $this->isAllowedPaymentRedirectUrl($checkoutUrl)) {
                logger()->warning('tip.checkout.url_invalid_or_empty', [
                    'tip_id' => $tip->id,
                    'tip_payment_id' => $tipPayment->id,
                    'checkout_url_length' => strlen($checkoutUrl),
                ]);
                $tipPayment->delete();
                $tip->delete();

                return redirect()
                    ->route('products.show', $product)
                    ->with('error', __('Не вдалося отримати коректне посилання на оплату від AIFO. Спробуйте ще раз або зверніться до підтримки.'));
            }

            logger()->info('tip.redirect.to_gateway', [
                'tip_id' => $tip->id,
                'host' => parse_url($checkoutUrl, PHP_URL_HOST) ?: '',
            ]);

            return redirect()->away($checkoutUrl);
        } catch (\Throwable $e) {
            Log::error('Tip checkout failed', [
                'product_id' => $product->id,
                'tip_id' => $tip?->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            report($e);

            $tipPayment?->delete();
            if ($tip instanceof Tip && $tip->exists) {
                $tip->delete();
            }

            $userError = __('Тимчасова помилка при оплаті подяки. Якщо вона повторюється, перевірте лог сервера та міграції бази (таблиці tips / tip_payments).');
            if ($e instanceof QueryException) {
                $userError = __('Помилка бази даних при збереженні оплати. На сервері виконайте: php artisan migrate --force (потрібні таблиці tips та tip_payments). Далі перегляньте storage/logs/laravel.log.');
            }

            if (config('app.debug')) {
                $userError .= ' '.Str::limit('['.$e::class.'] '.$e->getMessage(), 400);
            }

            return redirect()
                ->route('products.show', $product)
                ->with('error', $userError);
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
