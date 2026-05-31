<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TipPayment;
use App\Notifications\NewTipNotification;
use App\Services\AifoPaymentService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentWebhookController extends Controller
{
    /**
     * Unified AIFO webhook: orders and tips share one URL in the merchant dashboard.
     * Route `/payments/aifo/tips/webhook` remains as a backwards-compatible alias.
     */
    public function __invoke(Request $request, AifoPaymentService $payments): JsonResponse
    {
        $this->assertValidSignature($request);

        $tipPayment = $this->resolveTipPayment($request);
        if ($tipPayment !== null) {
            return $this->handleTip($request, $payments, $tipPayment);
        }

        $orderPayment = $this->resolveOrderPayment($request);
        if ($orderPayment !== null) {
            return $this->handleOrder($request, $payments, $orderPayment);
        }

        abort(404);
    }

    private function assertValidSignature(Request $request): void
    {
        $secret = AifoPaymentService::webhookSigningSecret();
        if ($secret === '') {
            return;
        }

        $directSignature = $request->input('http_auth_signature');
        $invoice = $request->input('invoice') ?? $request->input('pay_id');
        $orderReference = $request->input('orderReference') ?? $request->input('external_id');
        $sum = $request->input('sum') ?? $request->input('amount');
        $shopId = $request->input('shop_id');
        if (is_scalar($directSignature) && is_scalar($invoice) && is_scalar($sum) && is_scalar($shopId)) {
            $sumString = number_format((float) $sum, 2, '.', '');
            $candidates = [
                hash('sha256', "{$shopId}:{$sumString}:{$secret}:{$invoice}"),
            ];
            if (is_scalar($orderReference) && (string) $orderReference !== '') {
                $candidates[] = hash('sha256', "{$shopId}:{$sumString}:{$secret}:{$orderReference}");
            }

            if (! collect($candidates)->contains(fn ($expected) => hash_equals($expected, (string) $directSignature))) {
                Log::warning('AIFO webhook: invalid direct signature', [
                    'ip' => $request->ip(),
                    'invoice' => $invoice,
                ]);
                abort(403);
            }

            return;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, (string) $request->header('X-Aifo-Signature'))) {
            Log::warning('AIFO webhook: invalid HMAC signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            abort(403);
        }
    }

    private function referenceFromRequest(Request $request): ?string
    {
        $ref = $request->input('orderReference')
            ?? $request->input('external_id')
            ?? $request->input('payment_id')
            ?? $request->input('invoice')
            ?? $request->input('invoice_id')
            ?? $request->input('pay_id');
        if ($ref === null || $ref === '') {
            return null;
        }

        return (string) $ref;
    }

    private function resolveTipPayment(Request $request): ?TipPayment
    {
        $externalId = $request->input('external_id');
        if (is_string($externalId) && str_starts_with($externalId, 'TIP-')) {
            $tipId = (int) substr($externalId, 4);
            if ($tipId > 0) {
                $found = TipPayment::query()
                    ->where('tip_id', $tipId)
                    ->where('provider', 'aifo')
                    ->latest('id')
                    ->first();
                if ($found !== null) {
                    return $found;
                }
            }
        }

        $ref = $this->referenceFromRequest($request);
        if ($ref === null) {
            return null;
        }

        if (str_starts_with($ref, 'TIP-')) {
            $tipId = (int) substr($ref, 4);
            if ($tipId > 0) {
                return TipPayment::query()
                    ->where('tip_id', $tipId)
                    ->where('provider', 'aifo')
                    ->latest('id')
                    ->first();
            }
        }

        return TipPayment::where('provider_payment_id', $ref)->first();
    }

    private function resolveOrderPayment(Request $request): ?Payment
    {
        $externalId = $request->input('external_id');
        if (is_string($externalId) && str_starts_with($externalId, 'TIP-')) {
            return null;
        }

        $ref = $this->referenceFromRequest($request);
        if ($ref === null) {
            return null;
        }

        if (str_starts_with($ref, 'ORD-')) {
            return Order::query()
                ->where('number', $ref)
                ->first()
                ?->payment;
        }

        return Payment::where('provider_payment_id', $ref)->first();
    }

    private function handleTip(Request $request, AifoPaymentService $payments, TipPayment $payment): JsonResponse
    {
        if ($payment->status !== 'paid') {
            $payments->markTipPaid($payment, $request->all());
            app(AuditLogger::class)->record('tip.webhook_paid', $payment, [
                'tip_id' => $payment->tip_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ]);
            $payment->tip->author?->notify(new NewTipNotification($payment->tip));
        }

        return response()->json(['ok' => true]);
    }

    private function handleOrder(Request $request, AifoPaymentService $payments, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'paid') {
            $payments->markPaid($payment, $request->all());
            app(AuditLogger::class)->record('payment.webhook_paid', $payment->order, [
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ]);

            Mail::to($payment->order->user)->queue(new PurchaseReceiptMail($payment->order));
            foreach ($payment->order->items as $item) {
                Mail::to($item->author)->queue(new SaleNotificationMail($payment->order, $item->author));
            }
        }

        return response()->json(['ok' => true]);
    }
}
