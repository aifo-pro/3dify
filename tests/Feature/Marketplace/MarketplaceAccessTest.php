<?php

namespace Tests\Feature\Marketplace;

use App\Models\ModelFile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\MarketplaceAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_cannot_download_even_if_they_purchased(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create(['is_suspended' => true]);

        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'paid-model',
            'title' => ['uk' => 'Paid model', 'en' => 'Paid model'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 100,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-TEST-SUSPENDED',
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

        $access = app(MarketplaceAccess::class);
        $this->assertFalse($access->canDownload($buyer, $product));
    }

    public function test_active_buyer_can_download_purchased_product(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create(['is_suspended' => false]);

        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'paid-model-active',
            'title' => ['uk' => 'Paid model', 'en' => 'Paid model'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 100,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-TEST-ACTIVE',
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

        $access = app(MarketplaceAccess::class);
        $this->assertTrue($access->canDownload($buyer, $product));
    }

    public function test_suspended_user_download_route_returns_403(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create(['is_suspended' => true]);

        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'download-suspended',
            'title' => ['uk' => 'Download suspended', 'en' => 'Download suspended'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 100,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);

        $order = Order::query()->create([
            'number' => 'ORD-TEST-ROUTE',
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

        $file = ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'source',
            'disk' => 'private',
            'path' => 'models/test-suspended.stl',
            'original_name' => 'test.stl',
            'extension' => 'stl',
            'size' => 1024,
            'is_preview' => false,
        ]);

        $this->actingAs($buyer)
            ->get(route('products.download', [$product, $file]))
            ->assertForbidden();
    }
}
