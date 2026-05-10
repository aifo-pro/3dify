<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\TipPayment;
use App\Notifications\NewTipNotification;
use App\Services\AifoPaymentService;
use App\Services\SiteSettings;
use Illuminate\Http\Request;

class TipPaymentWebhookController extends Controller
{
    public function __invoke(Request $request, AifoPaymentService $payments)
    {
        $secret = app(SiteSettings::class)->string('payments.aifo_webhook_secret');
        if ($secret !== '') {
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            abort_unless(hash_equals($expected, (string) $request->header('X-Aifo-Signature')), 403);
        }

        $payment = TipPayment::where('provider_payment_id', $request->input('payment_id'))->firstOrFail();

        if ($payment->status !== 'paid') {
            $payments->markTipPaid($payment, $request->all());
            $payment->tip->author?->notify(new NewTipNotification($payment->tip));
        }

        return response()->json(['ok' => true]);
    }
}

