<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\Payment;
use App\Models\TipPayment;
use App\Notifications\NewTipNotification;
use App\Services\AifoPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        abort_unless(hash_equals($expected, (string) $request->header('X-Aifo-Signature')), 403);
    }

    private function referenceFromRequest(Request $request): ?string
    {
        $ref = $request->input('payment_id') ?? $request->input('invoice') ?? $request->input('invoice_id');
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

        return Payment::where('provider_payment_id', $ref)->first();
    }

    private function handleTip(Request $request, AifoPaymentService $payments, TipPayment $payment): JsonResponse
    {
        if ($payment->status !== 'paid') {
            $payments->markTipPaid($payment, $request->all());
            $payment->tip->author?->notify(new NewTipNotification($payment->tip));
        }

        return response()->json(['ok' => true]);
    }

    private function handleOrder(Request $request, AifoPaymentService $payments, Payment $payment): JsonResponse
    {
        if ($payment->status !== 'paid') {
            $payments->markPaid($payment, $request->all());

            Mail::to($payment->order->user)->queue(new PurchaseReceiptMail($payment->order));
            foreach ($payment->order->items as $item) {
                Mail::to($item->author)->queue(new SaleNotificationMail($payment->order, $item->author));
            }
        }

        return response()->json(['ok' => true]);
    }
}
