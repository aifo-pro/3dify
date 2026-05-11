<?php

namespace Tests\Feature\Marketplace;

use App\Mail\PurchaseReceiptMail;
use App\Mail\SaleNotificationMail;
use App\Models\ModelFile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_aifo_order_return_redirects_to_success_page_and_marks_order_paid(): void
    {
        Mail::fake();

        [$buyer, $order] = $this->createPendingOrder();

        $this->actingAs($buyer)
            ->get('/?invoice=427&status=paid&orderReference='.$order->number)
            ->assertRedirect(route('checkout.success', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
        ]);

        Mail::assertQueued(PurchaseReceiptMail::class);
        Mail::assertQueued(SaleNotificationMail::class);
    }

    public function test_failed_aifo_order_return_redirects_to_failed_page(): void
    {
        [$buyer, $order] = $this->createPendingOrder();

        $this->actingAs($buyer)
            ->get('/?invoice=428&status=failed&orderReference='.$order->number)
            ->assertRedirect(route('checkout.failed', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }

    public function test_aifo_order_webhook_can_resolve_payment_by_order_reference(): void
    {
        Mail::fake();

        [, $order] = $this->createPendingOrder();
        $order->payment->update(['provider_payment_id' => '427']);

        $this->post(route('payments.aifo.webhook'), [
            'invoice' => '427',
            'status' => 'paid',
            'orderReference' => $order->number,
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_success_page_shows_purchased_product_and_download_action(): void
    {
        [$buyer, $order, $product] = $this->createPendingOrder();

        $order->update(['status' => 'paid', 'paid_at' => now()]);
        $order->payment->update(['status' => 'paid']);

        ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'source',
            'disk' => 'private',
            'path' => 'models/test.stl',
            'original_name' => 'test.stl',
            'extension' => 'stl',
            'size' => 1024,
            'is_preview' => false,
        ]);

        $this->actingAs($buyer)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('Test product')
            ->assertSee(__('Скачати файли'));
    }

    public function test_direct_aifo_order_checkout_keeps_order_number_as_provider_payment_id(): void
    {
        [$buyer, $order] = $this->createPendingOrder(withPayment: false);

        $this->actingAs($buyer);

        $payment = app(\App\Services\AifoPaymentService::class)->createPayment($order);

        $this->assertSame($order->number, $payment->provider_payment_id);
    }

    /**
     * @return array{0: User, 1: Order, 2: Product}
     */
    private function createPendingOrder(bool $withPayment = true): array
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'test-product',
            'title' => ['uk' => 'Test product', 'en' => 'Test product'],
            'short_description' => ['uk' => 'Short description', 'en' => 'Short description'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'published',
            'price' => 10,
            'personal_price' => 10,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-'.now()->format('YmdHis').'-TEST1',
            'user_id' => $buyer->id,
            'status' => 'pending',
            'subtotal' => 10,
            'total' => 10,
            'currency' => 'UAH',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $author->id,
            'price' => 10,
            'currency' => 'UAH',
            'license_type' => 'personal',
        ]);

        if ($withPayment) {
            Payment::query()->create([
                'order_id' => $order->id,
                'provider' => 'aifo',
                'provider_payment_id' => $order->number,
                'status' => 'created',
                'amount' => 10,
                'currency' => 'UAH',
                'payload' => [],
            ]);
        }

        return [$buyer, $order->refresh(), $product];
    }
}
