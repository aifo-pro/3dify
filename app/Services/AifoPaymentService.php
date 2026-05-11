<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Tip;
use App\Models\TipPayment;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AifoPaymentService
{
    /**
     * Secret used to validate X-Aifo-Signature on webhooks (orders + tips).
     * Accepts canonical key or legacy admin keys from `/admin/content` (payments tab).
     */
    public static function webhookSigningSecret(): string
    {
        $settings = app(SiteSettings::class);
        $primary = trim($settings->string('payments.aifo_webhook_secret'));
        if ($primary !== '') {
            return $primary;
        }

        return trim($settings->string('payments.secret_key'));
    }

    /**
     * POST URL for creating AIFO checkouts (same as in aifo.pro merchant API docs).
     */
    private function apiEndpoint(): string
    {
        $settings = app(SiteSettings::class);
        $v = $this->rejectMisconfiguredInvoiceEndpoint(trim($settings->string('payments.aifo_endpoint')));
        if ($v !== '') {
            return $this->normalizeInvoiceApiUrl($v);
        }

        $legacy = $this->rejectMisconfiguredInvoiceEndpoint(trim($settings->string('payments.api_endpoint')));

        return $this->normalizeInvoiceApiUrl($legacy !== '' ? $legacy : 'https://aifo.pro/api/v2/invoices/create');
    }

    /**
     * Trailing slashes sometimes hit a different vhost/route and return 404 with an empty body.
     */
    private function normalizeInvoiceApiUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    /**
     * Admins sometimes paste the app's webhook URL here — that is not the AIFO invoice API and breaks checkout.
     */
    private function rejectMisconfiguredInvoiceEndpoint(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $lower = strtolower($url);
        if (str_contains($lower, '/payments/aifo/webhook')
            || str_contains($lower, '/payments/aifo/tips/webhook')) {
            Log::warning('payments.aifo_endpoint looks like this site webhook URL, not AIFO invoice API — using default.', ['url' => $url]);

            return '';
        }

        $endpointHost = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($endpointHost)
            && is_string($appHost)
            && $endpointHost !== ''
            && strcasecmp($endpointHost, $appHost) === 0) {
            Log::warning('payments.aifo_endpoint points to this application host, not AIFO invoice API — using default.', ['url' => $url]);

            return '';
        }

        return $url;
    }

    /**
     * Bearer token for AIFO API. Canonical `payments.aifo_api_key` or legacy `payments.api_key`.
     */
    private function apiToken(): string
    {
        $settings = app(SiteSettings::class);
        $v = trim($settings->string('payments.aifo_api_key'));
        if ($v !== '') {
            return $v;
        }

        return trim($settings->string('payments.api_key'));
    }

    /**
     * Merchant id from settings: `payments.aifo_merchant_id` or legacy `payments.merchant_id`.
     */
    private function merchantId(): string
    {
        $id = Setting::value('payments.aifo_merchant_id');
        if ($id !== null && $id !== '') {
            return is_scalar($id) ? (string) $id : 'demo';
        }
        $legacy = Setting::value('payments.merchant_id');

        return ($legacy !== null && $legacy !== '') ? (string) $legacy : 'demo';
    }

    /**
     * Secret used for API v2 HMAC (same material as webhook verification — kassa secret).
     */
    private function apiSigningSecret(): string
    {
        return trim(self::webhookSigningSecret());
    }

    private function shopId(): ?int
    {
        $raw = trim($this->merchantId());
        if ($raw === '' || $raw === 'demo') {
            return null;
        }
        if (! ctype_digit($raw)) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    /**
     * Official API v2 uses POST .../api/v2/invoices/create with HMAC headers (see aifo.pro/docs).
     */
    private function usesInvoiceCreateV2(string $endpoint): bool
    {
        return str_contains(strtolower($endpoint), '/api/v2/invoices/create');
    }

    /**
     * Canonical JSON for the HTTP body (same field values as used for signing).
     */
    private function canonicalBodyJson(array $body): string
    {
        ksort($body);

        return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * SORTED_PARAMS for HMAC per aifo.pro docs: alphabetical keys, URL-encoded query string
     * (same semantics as form-urlencoded body). Override with AIFO_V2_HMAC_USE_SORTED_JSON_BODY=true
     * if your merchant backend expects the raw sorted JSON string instead.
     */
    private function signingSortedParamsForV2Invoice(array $body): string
    {
        if (filter_var(env('AIFO_V2_HMAC_USE_SORTED_JSON_BODY', false), FILTER_VALIDATE_BOOL)) {
            return $this->canonicalBodyJson($body);
        }

        return $this->canonicalSortedParamsForSignature($body);
    }

    private function canonicalSortedParamsForSignature(array $body): string
    {
        ksort($body);

        return http_build_query($body, '', '&', PHP_QUERY_RFC3986);
    }

    private function pathForSigning(string $endpointUrl): string
    {
        $path = parse_url($endpointUrl, PHP_URL_PATH);

        return ($path !== null && $path !== '') ? $path : '/api/v2/invoices/create';
    }

    /**
     * @return array{0: Response|null, 1: string|null} Response and checkout URL (payment_url / legacy checkout_url)
     */
    private function createInvoiceCheckout(string $endpoint, array $body): array
    {
        $secret = $this->apiSigningSecret();
        if ($secret === '' || ! $this->usesInvoiceCreateV2($endpoint)) {
            return [null, null];
        }

        $bodyJson = $this->canonicalBodyJson($body);
        $sortedParams = $this->signingSortedParamsForV2Invoice($body);
        $path = $this->pathForSigning($endpoint);
        $timestamp = (string) time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = "POST\n{$path}\n{$timestamp}\n{$nonce}\n{$sortedParams}";
        $signature = hash_hmac('sha256', $canonical, $secret);

        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->connectTimeout(15)
                ->withHeaders([
                    'X-AIFO-Timestamp' => $timestamp,
                    'X-AIFO-Nonce' => $nonce,
                    'X-AIFO-Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($bodyJson, 'application/json')
                ->post($endpoint);
        } catch (\Throwable $e) {
            Log::error('AIFO v2 invoice request threw', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [null, null];
        }

        $rawBody = $response->body();

        if (! $response->successful()) {
            Log::warning('AIFO v2 invoice request failed', [
                'endpoint' => $endpoint,
                'sign_path' => $path,
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'body_preview' => $this->truncateForLog($rawBody),
                'looks_like_html' => $this->responseBodyLooksLikeHtml($rawBody),
            ]);

            return [$response, null];
        }

        if ($this->responseBodyLooksLikeHtml($rawBody)) {
            Log::warning('AIFO v2 invoice returned HTML instead of JSON (wrong endpoint or gateway error)', [
                'status' => $response->status(),
                'endpoint' => $endpoint,
                'content_type' => $response->header('Content-Type'),
                'body_preview' => $this->truncateForLog($rawBody),
            ]);

            return [$response, null];
        }

        $url = $response->json('data.payment_url');
        if (! is_string($url) || $url === '') {
            $url = $response->json('checkout_url');
        }

        return [$response, is_string($url) && $url !== '' ? $url : null];
    }

    private function truncateForLog(string $body, int $max = 2000): string
    {
        if (strlen($body) <= $max) {
            return $body;
        }

        return substr($body, 0, $max).'…';
    }

    private function responseBodyLooksLikeHtml(string $body): bool
    {
        $sample = strtolower(substr(ltrim($body), 0, 800));

        return str_contains($sample, '<html')
            || str_contains($sample, '<!doctype')
            || str_contains($sample, 'aifo-error')
            || str_contains($sample, 'не знайдено')
            || str_contains($sample, 'page not found');
    }

    private function extractInvoiceId(?Response $response): ?string
    {
        if ($response === null || ! $response->successful()) {
            return null;
        }

        $id = $response->json('data.invoice_id');
        if ($id === null) {
            $id = $response->json('data.id');
        }
        if ($id === null) {
            $id = $response->json('payment_id');
        }

        if ($id === null) {
            return null;
        }

        return is_scalar($id) ? (string) $id : null;
    }

    /**
     * AIFO public docs also support direct checkout links:
     * https://aifo.pro/pay/?shop_id=ID&pay_id=PAY_ID&amount=AMOUNT&sign=SHA256(shop_id:amount:secret:pay_id)
     */
    private function directCheckoutUrl(string $payId, float $amount): ?string
    {
        $shopId = $this->shopId();
        $secret = $this->apiSigningSecret();
        if ($shopId === null || $secret === '' || $amount <= 0) {
            return null;
        }

        $amountString = number_format($amount, 2, '.', '');
        $signature = hash('sha256', "{$shopId}:{$amountString}:{$secret}:{$payId}");

        return 'https://aifo.pro/pay/?'.http_build_query([
            'shop_id' => $shopId,
            'pay_id' => $payId,
            'amount' => $amountString,
            'sign' => $signature,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function createPayment(Order $order): Payment
    {
        $checkoutUrl = null;
        $response = null;
        $directPayId = $order->number;

        $endpoint = $this->apiEndpoint();
        $shopId = $this->shopId();

        if ($order->total > 0 && $shopId !== null && $this->apiSigningSecret() !== '' && $this->usesInvoiceCreateV2($endpoint)) {
            [$response, $checkoutUrl] = $this->createInvoiceCheckout($endpoint, [
                'shop_id' => $shopId,
                'external_id' => $order->number,
                'amount_minor' => (int) round(((float) $order->total) * 100),
                'description' => __('Order :number', ['number' => $order->number]),
            ]);
        }

        $apiKey = $this->apiToken();
        $useLegacy = $apiKey !== '' && $endpoint !== '' && ! $this->usesInvoiceCreateV2($endpoint);
        if ($checkoutUrl === null && $useLegacy && $order->total > 0) {
            try {
                $response = Http::withToken($apiKey)->acceptJson()
                    ->timeout(30)
                    ->connectTimeout(15)
                    ->post($endpoint, [
                        'order_id' => $order->number,
                        'amount' => (float) $order->total,
                        'currency' => $order->currency,
                        'success_url' => route('checkout.success', $order),
                        'webhook_url' => route('payments.aifo.webhook'),
                    ]);
            } catch (\Throwable $e) {
                Log::error('AIFO legacy order checkout HTTP exception', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
                $response = null;
            }

            if ($response && $response->successful()) {
                $checkoutUrl = $response->json('checkout_url');
            }
        }

        if ($checkoutUrl === null && $order->total > 0) {
            $checkoutUrl = $this->directCheckoutUrl($directPayId, (float) $order->total);
        }

        $providerPaymentId = $directPayId ?: 'AIFO-'.strtoupper(str()->random(14));
        if ($checkoutUrl) {
            $pid = $this->extractInvoiceId($response);
            if ($pid === null && $response?->json('payment_id') !== null) {
                $pid = (string) $response->json('payment_id');
            }
            $providerPaymentId = $pid ?? $directPayId;
        }

        $payment = $order->payment()->create([
            'provider' => 'aifo',
            'provider_payment_id' => $providerPaymentId,
            'status' => $order->total > 0 ? 'created' : 'paid',
            'amount' => $order->total,
            'currency' => $order->currency,
            'payload' => [
                'merchant_id' => $this->merchantId(),
                'checkout_url' => $checkoutUrl,
                'success_url' => route('checkout.success', $order),
                'webhook_url' => route('payments.aifo.webhook'),
            ],
        ]);

        if ((float) $order->total === 0.0) {
            $this->markPaid($payment, ['free_order' => true]);
        }

        return $payment;
    }

    /**
     * Create an AIFO checkout for a tip. Returns null when AIFO is not configured (no keys in settings).
     */
    public function createTipPayment(Tip $tip): ?TipPayment
    {
        $checkoutUrl = null;
        $response = null;
        $directPayId = 'TIP-'.$tip->id;

        $endpoint = $this->apiEndpoint();
        $shopId = $this->shopId();
        $secret = $this->apiSigningSecret();
        $apiKey = $this->apiToken();

        $endpointIsV2 = $this->usesInvoiceCreateV2($endpoint);
        $useV2 = $shopId !== null && $secret !== '' && $endpointIsV2;
        $useLegacy = $apiKey !== '' && $endpoint !== '' && ! $endpointIsV2;

        if (! $useV2 && ! $useLegacy) {
            if ($endpointIsV2) {
                Log::warning('AIFO tip checkout skipped: v2 endpoint requires numeric Merchant ID and HMAC secret.', [
                    'tip_id' => $tip->id,
                    'endpoint' => $endpoint,
                    'has_shop_id' => $shopId !== null,
                    'has_secret' => $secret !== '',
                ]);
            }

            return null;
        }

        $successUrl = route('tips.success', $tip);
        $webhookUrl = route('payments.aifo.webhook');

        if ($useV2) {
            [$response, $checkoutUrl] = $this->createInvoiceCheckout($endpoint, [
                'shop_id' => $shopId,
                'external_id' => $directPayId,
                'amount_minor' => (int) round(((float) $tip->amount) * 100),
                'description' => __('Tip for model (:id)', ['id' => $tip->product_id]),
            ]);
        }

        if ($checkoutUrl === null && $useLegacy) {
            try {
                $response = Http::withToken($apiKey)->acceptJson()
                    ->timeout(30)
                    ->connectTimeout(15)
                    ->post($endpoint, [
                        'order_id' => $directPayId,
                        'amount' => (float) $tip->amount,
                        'currency' => $tip->currency,
                        'success_url' => $successUrl,
                        'webhook_url' => $webhookUrl,
                    ]);
            } catch (\Throwable $e) {
                Log::error('AIFO legacy tip checkout HTTP exception', [
                    'tip_id' => $tip->id,
                    'endpoint' => $endpoint,
                    'message' => $e->getMessage(),
                ]);

                $response = null;
            }

            if ($response && $response->successful()) {
                $checkoutUrl = $response->json('checkout_url');
            } elseif ($response) {
                Log::warning('AIFO tip checkout request failed', [
                    'tip_id' => $tip->id,
                    'status' => $response->status(),
                    'body_preview' => $this->truncateForLog($response->body()),
                ]);
            }
        } elseif ($checkoutUrl === null && $useV2) {
            Log::warning('AIFO tip checkout: no payment URL (check response from AIFO / signature)', [
                'tip_id' => $tip->id,
                'endpoint' => $endpoint,
            ]);
        }

        if ($checkoutUrl === null) {
            $checkoutUrl = $this->directCheckoutUrl($directPayId, (float) $tip->amount);
        }

        $providerPaymentId = $directPayId;
        if ($checkoutUrl) {
            $pid = $this->extractInvoiceId($response);
            if ($pid === null && $response?->json('payment_id') !== null) {
                $pid = (string) $response->json('payment_id');
            }
            $providerPaymentId = $pid ?? $directPayId;
        }

        return TipPayment::create([
            'tip_id' => $tip->id,
            'provider' => 'aifo',
            'provider_payment_id' => $providerPaymentId,
            'status' => 'created',
            'amount' => $tip->amount,
            'currency' => $tip->currency,
            'payload' => [
                'merchant_id' => $this->merchantId(),
                'checkout_url' => $checkoutUrl,
                'success_url' => $successUrl,
                'webhook_url' => $webhookUrl,
            ],
        ]);
    }

    public function markPaid(Payment $payment, array $payload = []): void
    {
        $payment->update([
            'status' => 'paid',
            'payload' => array_merge($payment->payload ?? [], $payload),
        ]);

        $payment->order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        app(AccountBalanceService::class)->settleOrderDebit($payment->order);
    }

    public function markTipPaid(TipPayment $payment, array $payload = []): void
    {
        $payment->update([
            'status' => 'paid',
            'payload' => array_merge($payment->payload ?? [], $payload),
        ]);

        $payment->tip->update([
            'status' => Tip::STATUS_PAID,
        ]);
    }
}
