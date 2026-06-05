<?php

namespace Tests\Feature\Marketplace;

use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\PromoCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorPromoCodeTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $author, float $price = 1000): Product
    {
        return Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'promo-model-'.uniqid(),
            'title' => ['uk' => 'Promo model', 'en' => 'Promo model'],
            'description' => ['uk' => 'D', 'en' => 'D'],
            'status' => 'published',
            'price' => $price,
            'personal_price' => $price,
            'currency' => 'UAH',
            'is_free' => false,
            'published_at' => now(),
        ]);
    }

    public function test_author_can_create_promo_code_for_their_models(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)
            ->post(route('author.promo-codes.store'), [
                'code' => 'summer20',
                'value' => 20,
                'usage_limit' => 50,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('promo_codes', [
            'author_id' => $author->id,
            'code' => 'SUMMER20',
            'type' => 'percent',
            'value' => 20,
            'usage_limit' => 50,
        ]);
    }

    public function test_author_promo_only_valid_on_that_authors_products(): void
    {
        $authorA = User::factory()->create(['role' => 'author']);
        $authorB = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();

        $promo = PromoCode::create([
            'author_id' => $authorA->id, 'code' => 'AONLY', 'type' => 'percent',
            'value' => 25, 'currency' => 'UAH', 'used_count' => 0, 'is_active' => true,
        ]);

        $productA = $this->product($authorA, 1000);
        $productB = $this->product($authorB, 1000);

        $service = app(PromoCodeService::class);

        // Valid on author A's product.
        $this->assertNotNull($service->validate('AONLY', $buyer, 1000, $productA));
        // Invalid on author B's product.
        $this->assertNull($service->validate('AONLY', $buyer, 1000, $productB));
    }

    public function test_system_promo_keeps_author_earning_base_at_full_price(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();
        $product = $this->product($author, 1000);

        // System promo: author_id null.
        PromoCode::create([
            'author_id' => null, 'code' => 'SITE10', 'type' => 'percent',
            'value' => 10, 'currency' => 'UAH', 'used_count' => 0, 'is_active' => true,
        ]);

        $this->actingAs($buyer)
            ->post(route('products.promo.apply', $product), ['code' => 'SITE10'])
            ->assertRedirect();

        $this->actingAs($buyer)
            ->post(route('checkout.store', $product), [])
            ->assertStatus(200);

        $item = Order::query()->where('user_id', $buyer->id)->latest()->firstOrFail()->items()->firstOrFail();

        // Buyer pays discounted (price), but the author earns on the full price.
        $this->assertSame(900.0, (float) $item->price);
        $this->assertSame(1000.0, (float) $item->author_earning_base);
    }

    public function test_author_promo_reduces_author_earning_base(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $buyer = User::factory()->create();
        $product = $this->product($author, 1000);

        PromoCode::create([
            'author_id' => $author->id, 'code' => 'MINE15', 'type' => 'percent',
            'value' => 15, 'currency' => 'UAH', 'used_count' => 0, 'is_active' => true,
        ]);

        $this->actingAs($buyer)
            ->post(route('products.promo.apply', $product), ['code' => 'MINE15'])
            ->assertRedirect();

        $this->actingAs($buyer)
            ->post(route('checkout.store', $product), [])
            ->assertStatus(200);

        $item = Order::query()->where('user_id', $buyer->id)->latest()->firstOrFail()->items()->firstOrFail();

        // Author funds the discount: both the paid price and the earning base are reduced.
        $this->assertSame(850.0, (float) $item->price);
        $this->assertSame(850.0, (float) $item->author_earning_base);
    }
}
