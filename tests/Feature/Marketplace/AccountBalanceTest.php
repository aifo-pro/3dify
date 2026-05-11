<?php

namespace Tests\Feature\Marketplace;

use App\Models\AccountBalanceTransaction;
use App\Models\ModelFile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\AccountBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_refunded_order_credits_buyer_account_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$buyer, $order] = $this->createPaidOrder(price: 120);
        $refund = RefundRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $buyer->id,
            'reason' => 'other',
            'status' => RefundRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.refunds.update', $refund), [
                'status' => RefundRequest::STATUS_REFUNDED,
                'admin_notes' => 'Refunded to balance',
            ])
            ->assertRedirect();

        $this->assertSame(120.0, app(AccountBalanceService::class)->availableBalance($buyer));
        $this->assertDatabaseHas('account_balance_transactions', [
            'user_id' => $buyer->id,
            'refund_request_id' => $refund->id,
            'type' => AccountBalanceTransaction::TYPE_CREDIT,
            'status' => AccountBalanceTransaction::STATUS_SETTLED,
            'amount' => 120,
            'currency' => 'UAH',
        ]);
    }

    public function test_checkout_can_partially_use_account_balance_and_settles_it_after_payment_return(): void
    {
        Mail::fake();

        $buyer = User::factory()->create();
        $product = $this->createProduct(price: 100);
        AccountBalanceTransaction::query()->create([
            'user_id' => $buyer->id,
            'type' => AccountBalanceTransaction::TYPE_CREDIT,
            'status' => AccountBalanceTransaction::STATUS_SETTLED,
            'amount' => 40,
            'currency' => 'UAH',
        ]);

        $response = $this->actingAs($buyer)
            ->post(route('checkout.store', $product), [
                'license_type' => 'personal',
                'use_balance' => '1',
                'balance_amount' => 25,
            ]);

        $response->assertOk();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame(75.0, (float) $order->total);
        $this->assertSame(100.0, (float) $order->items()->first()->price);
        $this->assertSame(15.0, app(AccountBalanceService::class)->availableBalance($buyer));
        $this->assertDatabaseHas('account_balance_transactions', [
            'user_id' => $buyer->id,
            'order_id' => $order->id,
            'type' => AccountBalanceTransaction::TYPE_DEBIT,
            'status' => AccountBalanceTransaction::STATUS_PENDING,
            'amount' => 25,
        ]);

        $this->actingAs($buyer)
            ->get('/?invoice=427&status=paid&orderReference='.$order->number)
            ->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseHas('account_balance_transactions', [
            'order_id' => $order->id,
            'type' => AccountBalanceTransaction::TYPE_DEBIT,
            'status' => AccountBalanceTransaction::STATUS_SETTLED,
            'amount' => 25,
        ]);
        $this->assertSame(15.0, app(AccountBalanceService::class)->availableBalance($buyer));
    }

    public function test_failed_payment_return_releases_pending_balance_debit(): void
    {
        $buyer = User::factory()->create();
        $product = $this->createProduct(price: 100);
        AccountBalanceTransaction::query()->create([
            'user_id' => $buyer->id,
            'type' => AccountBalanceTransaction::TYPE_CREDIT,
            'status' => AccountBalanceTransaction::STATUS_SETTLED,
            'amount' => 40,
            'currency' => 'UAH',
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.store', $product), [
                'license_type' => 'personal',
                'use_balance' => '1',
                'balance_amount' => 25,
            ])
            ->assertOk();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->actingAs($buyer)
            ->get('/?invoice=428&status=failed&orderReference='.$order->number)
            ->assertRedirect(route('checkout.failed', $order));

        $this->assertDatabaseHas('account_balance_transactions', [
            'order_id' => $order->id,
            'type' => AccountBalanceTransaction::TYPE_DEBIT,
            'status' => AccountBalanceTransaction::STATUS_VOID,
            'amount' => 25,
        ]);
        $this->assertSame(40.0, app(AccountBalanceService::class)->availableBalance($buyer));
    }

    public function test_refunded_order_no_longer_allows_downloads_or_existing_signed_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$buyer, $order, $product] = $this->createPaidOrder(price: 120);
        $file = ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'source',
            'disk' => 'private',
            'path' => 'models/refunded.stl',
            'original_name' => 'refunded.stl',
            'extension' => 'stl',
            'size' => 1024,
            'is_preview' => false,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'products.download.signed',
            now()->addMinutes(5),
            [
                'product' => $product->slug,
                'file' => $file->id,
                'uid' => $buyer->id,
            ]
        );

        $this->actingAs($buyer)
            ->get(route('products.download-options', $product))
            ->assertOk();

        $refund = RefundRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $buyer->id,
            'reason' => 'other',
            'status' => RefundRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.refunds.update', $refund), [
                'status' => RefundRequest::STATUS_REFUNDED,
            ])
            ->assertRedirect();

        $this->actingAs($buyer)
            ->get(route('products.download-options', $product))
            ->assertForbidden();

        $this->actingAs($buyer)
            ->get(route('products.download', [$product, $file]))
            ->assertForbidden();

        $this->get($signedUrl)->assertForbidden();
    }

    private function createProduct(float $price): Product
    {
        $author = User::factory()->create(['role' => 'author']);

        return Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'balance-product-'.str()->random(6),
            'title' => ['uk' => 'Balance product', 'en' => 'Balance product'],
            'short_description' => ['uk' => 'Short', 'en' => 'Short'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => $price,
            'personal_price' => $price,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);
    }

    /**
     * @return array{0: User, 1: Order, 2: Product}
     */
    private function createPaidOrder(float $price): array
    {
        $buyer = User::factory()->create();
        $product = $this->createProduct($price);
        $order = Order::query()->create([
            'number' => 'ORD-'.now()->format('YmdHis').'-BAL01',
            'user_id' => $buyer->id,
            'status' => 'paid',
            'subtotal' => $price,
            'total' => $price,
            'currency' => 'UAH',
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $product->user_id,
            'price' => $price,
            'currency' => 'UAH',
            'license_type' => 'personal',
        ]);
        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'aifo',
            'provider_payment_id' => $order->number,
            'status' => 'paid',
            'amount' => $price,
            'currency' => 'UAH',
            'payload' => [],
        ]);

        return [$buyer, $order->refresh(), $product];
    }
}
