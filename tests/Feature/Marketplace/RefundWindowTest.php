<?php

namespace Tests\Feature\Marketplace;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundWindowTest extends TestCase
{
    use RefreshDatabase;

    private function createPaidOrder(User $buyer, Product $product, int $daysAgo = 0): Order
    {
        $author = User::query()->find($product->user_id);
        $order = Order::query()->create([
            'number' => 'ORD-REFUND-'.uniqid(),
            'user_id' => $buyer->id,
            'status' => 'paid',
            'subtotal' => 100,
            'total' => 100,
            'currency' => 'UAH',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $author->id,
            'price' => 100,
            'currency' => 'UAH',
            'license_type' => 'personal',
        ]);

        if ($daysAgo > 0) {
            \Illuminate\Support\Facades\DB::table('orders')
                ->where('id', $order->id)
                ->update(['updated_at' => now()->subDays($daysAgo)]);
        }

        return $order->fresh();
    }

    public function test_refund_allowed_within_default_14_day_window(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'refund-within-window',
            'title' => ['uk' => 'Refund model', 'en' => 'Refund model'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 100,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        $order = $this->createPaidOrder($buyer, $product, daysAgo: 5);

        $this->actingAs($buyer)
            ->post(route('refunds.store', $order), ['reason' => 'misleading'])
            ->assertRedirect(route('refunds.index'));

        $this->assertDatabaseHas('refund_requests', [
            'order_id' => $order->id,
            'user_id' => $buyer->id,
        ]);
    }

    public function test_refund_blocked_after_14_day_window(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'refund-expired',
            'title' => ['uk' => 'Refund expired', 'en' => 'Refund expired'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 100,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        $order = $this->createPaidOrder($buyer, $product, daysAgo: 15);

        $this->actingAs($buyer)
            ->post(route('refunds.store', $order), ['reason' => 'misleading'])
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }

    public function test_refund_window_respects_env_override(): void
    {
        config(['marketplace.refund_window_days' => 7]);

        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'refund-short-window',
            'title' => ['uk' => 'Short window', 'en' => 'Short window'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 100,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        // 8 days ago — past 7-day window but within default 14
        $order = $this->createPaidOrder($buyer, $product, daysAgo: 8);

        $this->actingAs($buyer)
            ->post(route('refunds.store', $order), ['reason' => 'misleading'])
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }
}
