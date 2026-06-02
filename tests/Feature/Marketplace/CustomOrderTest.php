<?php

namespace Tests\Feature\Marketplace;

use App\Models\AccountBalanceTransaction;
use App\Models\Category;
use App\Models\CustomOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_custom_order_page_opens_with_marketplace_categories(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);
        Category::query()->create([
            'slug' => 'gadgets',
            'name' => ['uk' => 'Гаджети', 'en' => 'Gadgets'],
            'description' => ['uk' => 'Корисні моделі', 'en' => 'Useful models'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($buyer)
            ->get(route('custom-orders.create', ['author' => $author->id]))
            ->assertOk()
            ->assertSee('Гаджети')
            ->assertSee($author->displayName());
    }

    public function test_buyer_can_create_custom_order_and_author_can_send_offer(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($buyer)
            ->post(route('custom-orders.store'), [
                'author_id' => $author->id,
                'type' => CustomOrder::TYPE_MODEL_CREATION,
                'title' => 'Cosplay helmet STL',
                'description' => 'Need a printable cosplay helmet with separated STL parts and visor slot.',
                'budget_amount' => 2500,
                'budget_is_negotiable' => 1,
            ])
            ->assertRedirect();

        $order = CustomOrder::query()->firstOrFail();
        $this->assertSame(CustomOrder::STATUS_PENDING_REVIEW, $order->status);
        $this->assertSame($buyer->id, $order->buyer_id);
        $this->assertSame($author->id, $order->author_id);

        $this->actingAs($author)
            ->post(route('custom-orders.offer', $order), [
                'price' => 3000,
                'delivery_days' => 7,
                'offer_description' => 'I will model the helmet and prepare STL source files.',
                'offer_terms' => 'Two revision rounds included.',
                'milestones' => ['Blockout', 'Final STL'],
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(CustomOrder::STATUS_WAITING_BUYER_ACCEPT, $order->status);
        $this->assertSame(3000.0, (float) $order->price);
        $this->assertSame(2700.0, (float) $order->author_amount);
        $this->assertCount(2, $order->milestones);
    }

    public function test_custom_order_completion_releases_author_balance(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);
        $order = CustomOrder::query()->create([
            'buyer_id' => $buyer->id,
            'author_id' => $author->id,
            'type' => CustomOrder::TYPE_MODEL_CREATION,
            'status' => CustomOrder::STATUS_WAITING_PAYMENT,
            'title' => 'Desk organizer model',
            'description' => 'Need a desk organizer for 3D printing.',
            'price' => 1000,
            'currency' => 'UAH',
            'escrow_amount' => 1000,
            'platform_fee_amount' => 100,
            'author_amount' => 900,
        ]);

        $this->actingAs($buyer)
            ->post(route('custom-orders.demo-pay', $order))
            ->assertRedirect();

        $this->actingAs($buyer)
            ->post(route('custom-orders.complete', $order->refresh()))
            ->assertRedirect();

        $this->assertDatabaseHas('custom_orders', [
            'id' => $order->id,
            'status' => CustomOrder::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('account_balance_transactions', [
            'user_id' => $author->id,
            'type' => AccountBalanceTransaction::TYPE_CREDIT,
            'status' => AccountBalanceTransaction::STATUS_SETTLED,
            'amount' => 900,
            'currency' => 'UAH',
        ]);
    }
}
