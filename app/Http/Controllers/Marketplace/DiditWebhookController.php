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
        if (! $request->isMethod('post')) {
            return response()->json([
                'ok' => true,
                'endpoint' => 'didit.webhook',
                'method' => 'POST',
            ]);
        }

        $payload = $request->getContent();
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return response()->json(['message' => 'Invalid JSON'], 422);
        }

        $signatureV2 = $request->header('X-Signature-V2');
        $signatureSimple = $request->header('X-Signature-Simple');
        $signatureRaw = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $isTestWebhook = filter_var($request->header('X-Didit-Test-Webhook'), FILTER_VALIDATE_BOOL);

        if (! $kyc->verifyWebhookSignature($payload, $decoded, $signatureV2, $signatureSimple, $signatureRaw, $timestamp)) {
            Log::warning('didit.kyc.webhook_bad_signature', [
                'ip' => $request->ip(),
                'is_test_webhook' => $isTestWebhook,
                'has_signature_v2' => (bool) $signatureV2,
                'has_signature_simple' => (bool) $signatureSimple,
                'has_signature_raw' => (bool) $signatureRaw,
                'has_timestamp' => (bool) $timestamp,
            ]);

            if ($isTestWebhook) {
                return response()->json([
                    'ok' => true,
                    'test' => true,
                    'ignored' => true,
                    'message' => 'Test webhook received; signature was not applied to user data.',
                ]);
            }

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        try {
            $verification = $kyc->applyWebhook($decoded);
        } catch (\Throwable $e) {
            Log::error('didit.kyc.webhook_failed', [
                'ip' => $request->ip(),
                'is_test_webhook' => $isTestWebhook,
                'message' => $e->getMessage(),
            ]);

            if ($isTestWebhook) {
                return response()->json([
                    'ok' => true,
                    'test' => true,
                    'ignored' => true,
                    'message' => 'Test webhook received; no user data was changed.',
                ]);
            }

            throw $e;
        }

        return response()->json([
            'ok' => true,
            'test' => $isTestWebhook,
            'applied' => $verification !== null,
        ]);
    }
}
