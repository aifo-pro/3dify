<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\AdInjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdInjectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_grid_injection_returns_products_when_ads_table_is_unavailable(): void
    {
        Schema::dropIfExists('advertisements');

        $product = new Product(['slug' => 'safe-catalog-item']);

        $items = app(AdInjector::class)->injectIntoGrid([$product], 'catalog');

        $this->assertCount(1, $items);
        $this->assertSame($product, $items[0]);
    }
}
