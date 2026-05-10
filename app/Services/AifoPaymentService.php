<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Tip;
use App\Models\TipPayment;
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
        $v = trim($settings->string('payments.aifo_endpoint'));
        if ($v !== '') {
            return $v;
        }

        return trim($settings->string('payments.api_endpoint'));
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

    public function createPayment(Order $order): Payment
    {
        $checkoutUrl = null;
        $response = null;

        $endpoint = $this->apiEndpoint();
        $apiKey = $this->apiToken();

        if ($endpoint !== '' && $apiKey !== '' && $order->total > 0) {
            $response = Http::withToken($apiKey)->acceptJson()->post($endpoint, [
                'order_id' => $order->number,
                'amount' => (float) $order->total,
                'currency' => $order->currency,
                'success_url' => route('checkout.success', $order),
                'webhook_url' => route('payments.aifo.webhook'),
            ]);

            if ($response->successful()) {
                $checkoutUrl = $response->json('checkout_url');
            }
        }

        $payment = $order->payment()->create([
            'provider' => 'aifo',
            'provider_payment_id' => $checkoutUrl ? ($response->json('payment_id') ?? 'AIFO-'.strtoupper(str()->random(14))) : 'AIFO-'.strtoupper(str()->random(14)),
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

        $endpoint = $this->apiEndpoint();
        $apiKey = $this->apiToken();
        if ($endpoint === '' || $apiKey === '') {
            return null;
        }

        $successUrl = route('tips.success', $tip);
        $webhookUrl = route('payments.aifo.tips.webhook');

        $response = Http::withToken($apiKey)->acceptJson()->post($endpoint, [
            'order_id' => 'TIP-'.$tip->id,
            'amount' => (float) $tip->amount,
            'currency' => $tip->currency,
            'success_url' => $successUrl,
            'webhook_url' => $webhookUrl,
        ]);

        if ($response->successful()) {
            $checkoutUrl = $response->json('checkout_url');
        } else {
            Log::warning('AIFO tip checkout request failed', [
                'tip_id' => $tip->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return TipPayment::create([
            'tip_id' => $tip->id,
            'provider' => 'aifo',
            'provider_payment_id' => $checkoutUrl
                ? ($response->json('payment_id') ?? 'AIFO-TIP-'.strtoupper(str()->random(14)))
                : 'AIFO-TIP-'.strtoupper(str()->random(14)),
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
