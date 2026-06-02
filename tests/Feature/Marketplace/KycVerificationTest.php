<?php

namespace Tests\Feature\Marketplace;

use App\Models\KycVerification;
use App\Models\Payout;
use App\Models\Product;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_without_approved_kyc_cannot_request_payout(): void
    {
        $author = User::factory()->create(['kyc_status' => KycVerification::STATUS_PENDING]);
        $this->seedAuthorBalance($author);

        $this->actingAs($author)
            ->post(route('author.payouts.store'), [
                'amount' => 800,
                'method' => 'card',
                'details' => 'Test card',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('payouts', ['author_id' => $author->id]);
    }

    public function test_approved_kyc_author_can_request_payout(): void
    {
        $author = User::factory()->create([
            'kyc_status' => KycVerification::STATUS_APPROVED,
            'kyc_verified_at' => now(),
            'is_verified' => true,
        ]);
        $this->seedAuthorBalance($author);

        $this->actingAs($author)
            ->post(route('author.payouts.store'), [
                'amount' => 800,
                'method' => 'card',
                'details' => 'Test card',
            ])
            ->assertRedirect(route('author.payouts', absolute: false));

        $this->assertDatabaseHas('payouts', [
            'author_id' => $author->id,
            'amount' => 800,
            'status' => 'pending',
        ]);
    }

    public function test_didit_session_is_created_and_redirects_user(): void
    {
        config([
            'services.didit.api_key' => 'didit-key',
            'services.didit.workflow_id' => 'workflow-1',
        ]);

        Http::fake([
            'verification.didit.me/*' => Http::response([
                'session_id' => 'didit-session-1',
                'url' => 'https://verification.didit.me/session/abc',
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('kyc.start'))
            ->assertRedirect('https://verification.didit.me/session/abc');

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'provider_session_id' => 'didit-session-1',
            'status' => KycVerification::STATUS_PENDING,
        ]);
        $this->assertSame(KycVerification::STATUS_PENDING, $user->refresh()->kyc_status);
    }

    public function test_didit_signed_webhook_approves_user(): void
    {
        config(['services.didit.webhook_secret' => 'webhook-secret']);

        $user = User::factory()->create(['kyc_status' => KycVerification::STATUS_PENDING]);
        $verification = KycVerification::create([
            'user_id' => $user->id,
            'provider' => KycVerification::PROVIDER_DIDIT,
            'provider_session_id' => 'didit-session-2',
            'status' => KycVerification::STATUS_PENDING,
        ]);
        $payload = json_encode([
            'vendor_data' => (string) $verification->id,
            'session_id' => 'didit-session-2',
            'status' => 'approved',
            'applicant_id' => 'applicant-1',
        ]);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'webhook-secret');

        $this->postJson(route('webhooks.didit'), json_decode($payload, true), [
            'X-Timestamp' => $timestamp,
            'X-Signature-V2' => $signature,
        ])->assertOk();

        $this->assertDatabaseHas('kyc_verifications', [
            'id' => $verification->id,
            'status' => KycVerification::STATUS_APPROVED,
            'provider_applicant_id' => 'applicant-1',
        ]);
        $this->assertTrue($user->refresh()->hasApprovedKyc());
    }

    public function test_didit_simple_signature_webhook_approves_user(): void
    {
        config(['services.didit.webhook_secret' => 'webhook-secret']);

        $user = User::factory()->create(['kyc_status' => KycVerification::STATUS_PENDING]);
        $verification = KycVerification::create([
            'user_id' => $user->id,
            'provider' => KycVerification::PROVIDER_DIDIT,
            'provider_session_id' => 'didit-session-3',
            'status' => KycVerification::STATUS_PENDING,
        ]);

        $timestamp = (string) now()->timestamp;
        $payload = [
            'vendor_data' => (string) $verification->id,
            'session_id' => 'didit-session-3',
            'status' => 'Approved',
            'webhook_type' => 'status.updated',
            'timestamp' => (int) $timestamp,
        ];
        $signature = hash_hmac('sha256', implode(':', [
            $timestamp,
            'didit-session-3',
            'Approved',
            'status.updated',
        ]), 'webhook-secret');

        $this->postJson(route('webhooks.didit'), $payload, [
            'X-Timestamp' => $timestamp,
            'X-Signature-Simple' => $signature,
        ])->assertOk();

        $this->assertDatabaseHas('kyc_verifications', [
            'id' => $verification->id,
            'status' => KycVerification::STATUS_APPROVED,
        ]);
        $this->assertTrue($user->refresh()->hasApprovedKyc());
    }

    public function test_didit_dashboard_test_webhook_returns_ok_without_matching_session(): void
    {
        config(['services.didit.webhook_secret' => 'webhook-secret']);

        $timestamp = (string) now()->timestamp;
        $payload = [
            'status' => 'Approved',
            'vendor_data' => 'test-vendor-data-123',
            'webhook_type' => 'user.status.updated',
            'timestamp' => (int) $timestamp,
            'created_at' => (int) $timestamp,
            'workflow_id' => '296356f4-13ec-46fc-8147-dc9e0caee1dc',
            'metadata' => ['test_webhook' => true],
            'vendor_user_id' => 'c52f1f03-1809-4738-958f-6e0e7e38ea3',
            'previous_status' => 'In Review',
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'webhook-secret');

        $this->call('POST', route('webhooks.didit'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_SIGNATURE_V2' => $signature,
            'HTTP_X_DIDIT_TEST_WEBHOOK' => 'true',
        ], $body)
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'test' => true,
                'applied' => false,
            ]);
    }

    private function seedAuthorBalance(User $author): void
    {
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'kyc-payout-model-'.str()->random(6),
            'title' => ['uk' => 'Model', 'en' => 'Model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        Tip::create([
            'product_id' => $product->id,
            'author_id' => $author->id,
            'user_id' => User::factory()->create()->id,
            'amount' => 1000,
            'currency' => 'UAH',
            'status' => Tip::STATUS_PAID,
        ]);
    }
}
