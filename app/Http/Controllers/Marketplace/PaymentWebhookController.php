<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\Payment;
use App\Services\AifoPaymentService;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, AifoPaymentService $payments)
    {
        $secret = app(SiteSettings::class)->string('payments.aifo_webhook_secret');
        if ($secret !== '') {
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            abort_unless(hash_equals($expected, (string) $request->header('X-Aifo-Signature')), 403);
        }

        $payment = Payment::where('provider_payment_id', $request->input('payment_id'))->firstOrFail();
        $payments->markPaid($payment, $request->all());

        Mail::to($payment->order->user)->queue(new PurchaseReceiptMail($payment->order));
        foreach ($payment->order->items as $item) {
            Mail::to($item->author)->queue(new SaleNotificationMail($payment->order));
        }

        return response()->json(['ok' => true]);
    }
}
