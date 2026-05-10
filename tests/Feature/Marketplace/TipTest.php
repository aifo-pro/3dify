<?php

namespace Tests\Feature\Marketplace;

use App\Models\Product;
use App\Models\Tip;
use App\Models\User;
use App\Notifications\NewTipNotification;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TipTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_tip_is_created_in_uah_and_added_to_author_balance(): void
    {
        Notification::fake();

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
            ->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('tips', [
            'product_id' => $product->id,
            'author_id' => $author->id,
            'user_id' => $buyer->id,
            'currency' => 'UAH',
            'status' => Tip::STATUS_PAID,
        ]);

        $this->assertSame(100.0, app(PayoutService::class)->availableBalance($author));

        Notification::assertSentTo($author, NewTipNotification::class);
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
