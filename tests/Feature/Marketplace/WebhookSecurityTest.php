<?php

namespace Tests\Feature\Marketplace;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_with_invalid_hmac_signature_returns_403(): void
    {
        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_webhook_secret',
            'value' => 'real_secret',
        ]);

        $body = json_encode(['invoice' => '999', 'status' => 'paid']);
        $this->call(
            'POST',
            route('payments.aifo.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_AIFO_SIGNATURE' => 'invalid_signature',
            ],
            $body,
        )->assertForbidden();
    }

    public function test_webhook_with_invalid_direct_signature_returns_403(): void
    {
        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_webhook_secret',
            'value' => 'real_secret',
        ]);

        $this->post(route('payments.aifo.webhook'), [
            'shop_id' => '1',
            'invoice' => 'ORD-FAKE',
            'sum' => '10.00',
            'http_auth_signature' => 'wrong_signature',
        ])->assertForbidden();
    }

    public function test_webhook_invalid_signature_is_logged(): void
    {
        Log::spy();

        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_webhook_secret',
            'value' => 'real_secret',
        ]);

        $this->post(route('payments.aifo.webhook'), [
            'shop_id' => '1',
            'invoice' => 'ORD-FAKE',
            'sum' => '10.00',
            'http_auth_signature' => 'bad_sig',
        ])->assertForbidden();

        Log::shouldHaveReceived('warning')->once();
    }
}
