<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

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
}
