<?php

namespace Tests\Feature\Marketplace;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Tip;
use App\Models\User;
use App\Notifications\NewTipNotification;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TipTest extends TestCase
{
    use RefreshDatabase;

    public function test_tip_is_pending_until_aifo_webhook_marks_it_paid_and_then_counts_toward_balance(): void
    {
        Notification::fake();
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'data' => [
                    'payment_url' => 'https://pay.example/checkout/abc',
                    'invoice_id' => 789,
                ],
            ], 200),
        ]);

        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_merchant_id',
            'value' => '1',
        ]);
        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_webhook_secret',
            'value' => 'test_secret',
        ]);

        $author = User::factory()->create();
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'tip-model',
            'title' => ['uk' => 'Tip model', 'en' => 'Tip model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->post(route('products.tip', $product), [
                'amount' => 100,
                'message' => 'Thanks!',
            ])
            ->assertRedirect('https://pay.example/checkout/abc');

        $this->assertDatabaseHas('tips', [
            'product_id' => $product->id,
            'author_id' => $author->id,
            'user_id' => $buyer->id,
            'currency' => 'UAH',
            'status' => Tip::STATUS_PENDING,
        ]);

        $tip = Tip::query()->firstOrFail();
        $this->assertDatabaseHas('tip_payments', [
            'tip_id' => $tip->id,
            'provider_payment_id' => '789',
            'status' => 'created',
        ]);

        $this->assertSame(0.0, app(PayoutService::class)->availableBalance($author));
        Notification::assertNothingSent();

        $webhookBody = json_encode(['invoice' => '789', 'status' => 'paid'], JSON_UNESCAPED_SLASHES);
        $this->call(
            'POST',
            route('payments.aifo.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_AIFO_SIGNATURE' => hash_hmac('sha256', $webhookBody, 'test_secret'),
            ],
            $webhookBody,
        )->assertOk();

        $this->assertDatabaseHas('tips', [
            'id' => $tip->id,
            'status' => Tip::STATUS_PAID,
        ]);
        $this->assertDatabaseHas('tip_payments', [
            'tip_id' => $tip->id,
            'provider_payment_id' => '789',
            'status' => 'paid',
        ]);

        $this->assertSame(100.0, app(PayoutService::class)->availableBalance($author));
        Notification::assertSentTo($author, NewTipNotification::class);
    }

    public function test_tip_checkout_ignores_webhook_url_mistakenly_saved_as_api_endpoint(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'data' => [
                    'payment_url' => 'https://pay.example/checkout/fixed',
                    'invoice_id' => 999,
                ],
            ], 200),
        ]);

        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_merchant_id',
            'value' => '2',
        ]);
        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_webhook_secret',
            'value' => 'tip_secret',
        ]);
        Setting::query()->create([
            'group' => 'payments',
            'key' => 'payments.aifo_endpoint',
            'value' => 'https://3dify.dev/payments/aifo/webhook',
        ]);

        $author = User::factory()->create();
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'tip-webhook-confusion',
            'title' => ['uk' => 'W', 'en' => 'W'],
            'description' => ['uk' => 'D', 'en' => 'D'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->post(route('products.tip', $product), ['amount' => 50])
            ->assertRedirect('https://pay.example/checkout/fixed');
    }

    public function test_when_aifo_not_configured_redirects_to_product_with_error(): void
    {
        $author = User::factory()->create();
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'tip-no-aifo',
            'title' => ['uk' => 'No AIFO', 'en' => 'No AIFO'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->post(route('products.tip', $product), [
                'amount' => 100,
            ])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('error');

        $this->assertSame(0, Tip::query()->count());
    }

    public function test_get_tip_url_redirects_to_product_page(): void
    {
        $author = User::factory()->create();
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'tip-redirect-model',
            'title' => ['uk' => 'Tip redirect model', 'en' => 'Tip redirect model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('products.tip.redirect', $product))
            ->assertRedirect(route('products.show', $product));
    }

    public function test_guest_can_open_tip_shortcut_and_redirects_to_product(): void
    {
        $author = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'tip-guest-redirect',
            'title' => ['uk' => 'Guest tip model', 'en' => 'Guest tip model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        $this->get(route('products.tip.redirect', $product))
            ->assertRedirect(route('products.show', $product));
    }
}
