<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\AifoPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @deprecated Use {@see PaymentWebhookController} at `/payments/aifo/webhook`. Kept so old URLs keep working.
 */
class TipPaymentWebhookController extends Controller
{
    public function __invoke(Request $request, AifoPaymentService $payments): JsonResponse
    {
        return app(PaymentWebhookController::class)($request, $payments);
    }
}
