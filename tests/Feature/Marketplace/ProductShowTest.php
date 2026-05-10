<?php

namespace Tests\Feature\Marketplace;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_still_renders_when_view_stats_table_is_missing_and_gallery_is_dirty(): void
    {
        Schema::dropIfExists('product_view_stats');

        $author = User::factory()->create(['name' => 'Author Name']);
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'safe-product-page',
            'title' => ['uk' => 'Safe product page', 'en' => 'Safe product page'],
            'short_description' => ['uk' => 'Short text', 'en' => 'Short text'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'gallery' => [['broken' => 'value'], null, 'missing-gallery-image.png'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Safe product page');
    }
}
