<?php

namespace Tests\Feature\Marketplace;

use App\Models\AccountBalanceTransaction;
use App\Models\Category;
use App\Models\CustomOrder;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_model_creation_order_does_not_store_print_delivery_fields(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($buyer)
            ->post(route('custom-orders.store'), [
                'author_id' => $author->id,
                'type' => CustomOrder::TYPE_MODEL_CREATION,
                'title' => 'Digital STL model',
                'description' => 'Create a printable STL model based on the references.',
                'quantity' => 4,
                'material' => 'PLA',
                'color' => 'Black',
                'dimensions' => '100x100x100',
                'delivery_service' => 'Nova Poshta',
                'delivery_address' => 'Branch 1',
                'extra_comment' => 'Ship quickly',
            ])
            ->assertRedirect();

        $order = CustomOrder::query()->firstOrFail();
        $this->assertSame(CustomOrder::TYPE_MODEL_CREATION, $order->type);
        $this->assertNull($order->quantity);
        $this->assertNull($order->material);
        $this->assertNull($order->color);
        $this->assertNull($order->dimensions);
        $this->assertNull($order->delivery_service);
        $this->assertNull($order->delivery_address);
        $this->assertNull($order->extra_comment);
    }

    public function test_custom_order_completion_releases_author_balance(): void
    {
        Storage::fake('public');

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

        app(CustomOrderService::class)->markPaid($order, $buyer, $order->number, ['source' => 'test']);

        $this->actingAs($author)
            ->post(route('custom-orders.result', $order->refresh()), [
                'result_comment' => 'Finished model files are ready.',
                'result_files' => [
                    UploadedFile::fake()->create('desk-organizer.stl', 128, 'model/stl'),
                ],
            ])
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

    public function test_buyer_pays_custom_order_through_aifo_checkout_after_accepting_offer(): void
    {
        Setting::query()->create(['group' => 'payments', 'key' => 'payments.merchant_id', 'value' => '34']);
        Setting::query()->create(['group' => 'payments', 'key' => 'payments.secret_key', 'value' => 'test-secret']);
        Setting::query()->create(['group' => 'payments', 'key' => 'payments.aifo_endpoint', 'value' => 'https://legacy.example.test/checkout']);

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
            'accepted_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->post(route('custom-orders.pay', $order))
            ->assertRedirectContains('https://aifo.pro/pay/');

        $this->assertDatabaseHas('custom_order_payments', [
            'custom_order_id' => $order->id,
            'provider' => 'aifo',
            'provider_payment_id' => $order->number,
            'status' => 'created',
            'amount' => 1000,
            'currency' => 'UAH',
        ]);
    }

    public function test_print_order_requires_delivery_before_completion(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);
        $order = CustomOrder::query()->create([
            'buyer_id' => $buyer->id,
            'author_id' => $author->id,
            'type' => CustomOrder::TYPE_PRINT_SERVICE,
            'status' => CustomOrder::STATUS_IN_PROGRESS,
            'title' => 'Print phone stand',
            'description' => 'Print an existing phone stand model.',
            'price' => 500,
            'currency' => 'UAH',
            'escrow_amount' => 500,
            'platform_fee_amount' => 50,
            'author_amount' => 450,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)
            ->post(route('custom-orders.complete', $order))
            ->assertStatus(422);
    }

    public function test_print_order_delivery_is_selected_after_author_offer_before_payment(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);
        $order = CustomOrder::query()->create([
            'buyer_id' => $buyer->id,
            'author_id' => $author->id,
            'type' => CustomOrder::TYPE_PRINT_SERVICE,
            'status' => CustomOrder::STATUS_WAITING_BUYER_ACCEPT,
            'title' => 'Print phone stand',
            'description' => 'Print an existing phone stand model.',
            'price' => 500,
            'currency' => 'UAH',
            'escrow_amount' => 500,
            'platform_fee_amount' => 50,
            'author_amount' => 450,
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)
            ->post(route('custom-orders.accept', $order))
            ->assertSessionHasErrors('delivery_address');

        $this->assertFalse($order->refresh()->canBePaid());

        $this->actingAs($buyer)
            ->post(route('custom-orders.delivery', $order), [
                'delivery_service' => 'nova_poshta',
                'delivery_city' => 'Київ',
                'delivery_city_ref' => 'city-ref',
                'delivery_warehouse_ref' => 'warehouse-ref',
                'delivery_address' => 'Відділення №1',
                'extra_comment' => 'Call before delivery',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('nova_poshta', $order->delivery_service);
        $this->assertSame('Київ', $order->delivery_city);
        $this->assertSame('Відділення №1', $order->delivery_address);
        $this->assertNotNull($order->delivery_selected_at);

        $this->actingAs($buyer)
            ->post(route('custom-orders.accept', $order))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($order->refresh()->canBePaid());
    }

    public function test_custom_order_chat_can_send_and_fetch_messages_as_json(): void
    {
        $buyer = User::factory()->create();
        $author = User::factory()->create(['role' => 'author']);
        $order = CustomOrder::query()->create([
            'buyer_id' => $buyer->id,
            'author_id' => $author->id,
            'type' => CustomOrder::TYPE_MODEL_CREATION,
            'status' => CustomOrder::STATUS_DISCUSSING,
            'title' => 'Custom model',
            'description' => 'Create a custom printable model.',
        ]);

        $response = $this->actingAs($buyer)
            ->postJson(route('custom-orders.messages.store', $order), [
                'body' => 'Hello author',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message.body', 'Hello author')
            ->assertJsonPath('message.own', true);

        $messageId = $response->json('message.id');

        $this->actingAs($author)
            ->getJson(route('custom-orders.messages.index', $order).'?after=0')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Hello author']);

        $this->actingAs($author)
            ->getJson(route('custom-orders.messages.index', $order).'?after='.$messageId)
            ->assertOk()
            ->assertJsonCount(0, 'messages');
    }
}
