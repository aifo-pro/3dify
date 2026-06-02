<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\DiditKycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DiditWebhookController extends Controller
{
    public function __invoke(Request $request, DiditKycService $kyc)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Signature-V2') ?: $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (! $kyc->verifyWebhookSignature($payload, $signature, $timestamp)) {
            Log::warning('didit.kyc.webhook_bad_signature', [
                'ip' => $request->ip(),
                'has_signature' => (bool) $signature,
                'has_timestamp' => (bool) $timestamp,
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return response()->json(['message' => 'Invalid JSON'], 422);
        }

        $kyc->applyWebhook($decoded);

        return response()->json(['ok' => true]);
    }
}
