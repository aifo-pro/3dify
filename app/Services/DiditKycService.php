<?php

namespace App\Services;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DiditKycService
{
    public function createSession(User $user): KycVerification
    {
        $apiKey = (string) config('services.didit.api_key');
        $workflowId = (string) config('services.didit.workflow_id');

        if ($apiKey === '' || $workflowId === '') {
            throw new RuntimeException(__('kyc.errors.not_configured'));
        }

        $verification = KycVerification::create([
            'user_id' => $user->id,
            'provider' => KycVerification::PROVIDER_DIDIT,
            'status' => KycVerification::STATUS_PENDING,
        ]);

        try {
            $response = Http::asJson()
                ->withHeaders(['x-api-key' => $apiKey])
                ->timeout(20)
                ->post(rtrim((string) config('services.didit.endpoint'), '/').'/v3/session/', [
                    'workflow_id' => $workflowId,
                    'vendor_data' => (string) $verification->id,
                    'callback' => route('kyc.return', absolute: true),
                    'contact_details' => [
                        'email' => $user->email,
                    ],
                    'metadata' => [
                        'user_id' => $user->id,
                        'verification_id' => $verification->id,
                        'site' => config('app.name', '3Dify'),
                    ],
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $verification->update([
                'status' => KycVerification::STATUS_FAILED,
                'decision' => 'api_error',
                'rejection_reason' => Str::limit($exception->response?->body() ?: $exception->getMessage(), 1000),
            ]);

            Log::warning('didit.kyc.session_failed', [
                'user_id' => $user->id,
                'verification_id' => $verification->id,
                'status' => $exception->response?->status(),
                'body' => Str::limit($exception->response?->body() ?: '', 1000),
            ]);

            throw $exception;
        }

        $sessionId = $this->firstString($response, ['session_id', 'id', 'session.id']);
        $url = $this->firstString($response, ['url', 'verification_url', 'session.url', 'hosted_url']);

        if (! $url) {
            $verification->update([
                'status' => KycVerification::STATUS_FAILED,
                'decision' => 'missing_redirect_url',
                'webhook_payload' => $response,
            ]);

            throw new RuntimeException(__('kyc.errors.missing_redirect'));
        }

        $verification->update([
            'provider_session_id' => $sessionId,
            'verification_url' => $url,
            'webhook_payload' => $response,
        ]);

        $user->forceFill(['kyc_status' => KycVerification::STATUS_PENDING])->save();

        app(AuditLogger::class)->record('kyc.session.created', $verification, [
            'provider_session_id' => $sessionId,
        ]);

        return $verification;
    }

    public function verifyWebhookSignature(string $payload, ?string $signature, ?string $timestamp): bool
    {
        $secret = (string) config('services.didit.webhook_secret');

        if ($secret === '' || ! $signature || ! $timestamp) {
            return false;
        }

        $message = $timestamp.'.'.$payload;
        $hex = hash_hmac('sha256', $message, $secret);
        $base64 = base64_encode(hash_hmac('sha256', $message, $secret, true));
        $plain = preg_replace('/^sha256=/i', '', trim($signature));

        return hash_equals($hex, $plain) || hash_equals($base64, $plain) || hash_equals($hex, trim($signature));
    }

    public function applyWebhook(array $payload): ?KycVerification
    {
        $verification = $this->findVerification($payload);

        if (! $verification) {
            Log::warning('didit.kyc.webhook_orphan', ['payload' => $payload]);

            return null;
        }

        $status = $this->normalizeStatus($payload);
        $decision = $this->firstString($payload, ['decision', 'result.decision', 'status']);
        $applicantId = $this->firstString($payload, ['applicant_id', 'applicant.id', 'identity.id']);
        $reason = $this->firstString($payload, ['rejection_reason', 'reject_reason', 'reason', 'result.reason']);

        $updates = [
            'provider_applicant_id' => $applicantId ?: $verification->provider_applicant_id,
            'status' => $status,
            'decision' => $decision,
            'rejection_reason' => $status === KycVerification::STATUS_REJECTED ? $reason : $verification->rejection_reason,
            'webhook_payload' => $payload,
        ];

        if ($status === KycVerification::STATUS_APPROVED) {
            $updates['approved_at'] = $verification->approved_at ?: now();
        } elseif ($status === KycVerification::STATUS_REJECTED) {
            $updates['rejected_at'] = $verification->rejected_at ?: now();
        } elseif ($status === KycVerification::STATUS_EXPIRED) {
            $updates['expired_at'] = $verification->expired_at ?: now();
        }

        $verification->update($updates);
        $this->syncUserStatus($verification->user, $verification);

        app(AuditLogger::class)->record('kyc.webhook.applied', $verification, [
            'status' => $status,
            'provider_session_id' => $verification->provider_session_id,
        ]);

        return $verification;
    }

    public function syncUserStatus(User $user, KycVerification $verification): void
    {
        if ($verification->status === KycVerification::STATUS_APPROVED) {
            $user->forceFill([
                'kyc_status' => KycVerification::STATUS_APPROVED,
                'kyc_verified_at' => $verification->approved_at ?: now(),
                'is_verified' => true,
            ])->save();

            return;
        }

        $user->forceFill([
            'kyc_status' => $verification->status,
            'is_verified' => false,
        ])->save();
    }

    private function findVerification(array $payload): ?KycVerification
    {
        $verificationId = $this->firstString($payload, ['vendor_data', 'metadata.verification_id']);
        if ($verificationId && ctype_digit((string) $verificationId)) {
            $verification = KycVerification::find((int) $verificationId);
            if ($verification) {
                return $verification;
            }
        }

        $sessionId = $this->firstString($payload, ['session_id', 'id', 'session.id', 'verification.id']);

        return $sessionId
            ? KycVerification::where('provider_session_id', $sessionId)->first()
            : null;
    }

    private function normalizeStatus(array $payload): string
    {
        $raw = strtolower((string) $this->firstString($payload, [
            'status',
            'decision',
            'result.status',
            'result.decision',
            'session.status',
        ]));

        return match (true) {
            in_array($raw, ['approved', 'approve', 'accepted', 'success', 'completed', 'verified'], true) => KycVerification::STATUS_APPROVED,
            in_array($raw, ['rejected', 'reject', 'declined', 'denied'], true) => KycVerification::STATUS_REJECTED,
            in_array($raw, ['expired'], true) => KycVerification::STATUS_EXPIRED,
            in_array($raw, ['failed', 'error'], true) => KycVerification::STATUS_FAILED,
            default => KycVerification::STATUS_PENDING,
        };
    }

    private function firstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }
}
