<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tip;
use App\Models\TipPayment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AifoPaymentService
{
    public function createPayment(Order $order): Payment
    {
        $checkoutUrl = null;
        $endpoint = app(SiteSettings::class)->string('payments.aifo_endpoint');
        $apiKey = app(SiteSettings::class)->string('payments.aifo_api_key');

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
                'merchant_id' => Setting::value('payments.aifo_merchant_id', 'demo'),
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

        $endpoint = app(SiteSettings::class)->string('payments.aifo_endpoint');
        $apiKey = app(SiteSettings::class)->string('payments.aifo_api_key');
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
                'merchant_id' => Setting::value('payments.aifo_merchant_id', 'demo'),
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
