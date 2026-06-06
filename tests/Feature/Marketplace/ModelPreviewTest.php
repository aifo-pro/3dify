<?php

namespace Tests\Feature\Marketplace;

use App\Models\License;
use App\Models\ModelFile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ModelPreviewResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function license(): License
    {
        return License::query()->create([
            'slug' => 'cc-by-'.uniqid(),
            'name' => ['uk' => 'CC BY', 'en' => 'CC BY'],
            'description' => ['uk' => 'Attr.', 'en' => 'Attr.'],
            'badge_label' => 'CC-BY',
            'badge_color' => 'sky',
            'allows_commercial_use' => true,
            'requires_attribution' => true,
            'allows_redistribution' => true,
            'allows_remix' => true,
            'allows_selling_prints' => true,
            'forbids_file_resale' => false,
        ]);
    }

    private function product(array $attrs = []): Product
    {
        return Product::query()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'license_id' => $this->license()->id,
            'slug' => 'preview-'.uniqid(),
            'title' => ['uk' => 'Preview Model', 'en' => 'Preview Model'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ], $attrs));
    }

    private function sourceFile(Product $product, string $ext, string $contents = 'data'): ModelFile
    {
        $path = 'models/'.$product->id.'/file.'.$ext;
        Storage::disk('private')->put($path, $contents);

        return ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'source',
            'disk' => 'private',
            'path' => $path,
            'original_name' => 'file.'.$ext,
            'extension' => $ext,
            'size' => strlen($contents),
            'is_preview' => false,
        ]);
    }

    private function previewFile(Product $product, string $ext, string $contents = 'data'): ModelFile
    {
        $path = 'previews/'.$product->id.'/preview.'.$ext;
        Storage::disk('public')->put($path, $contents);

        return ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'preview',
            'disk' => 'public',
            'path' => $path,
            'original_name' => 'preview.'.$ext,
            'extension' => $ext,
            'size' => strlen($contents),
            'is_preview' => true,
        ]);
    }

    private function paidBuyer(Product $product): User
    {
        $buyer = User::factory()->create();
        $order = Order::query()->create([
            'number' => 'ORD-'.uniqid(),
            'user_id' => $buyer->id,
            'status' => 'paid',
            'subtotal' => 10,
            'total' => 10,
            'currency' => 'UAH',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $product->user_id,
            'price' => 10,
            'currency' => 'UAH',
            'license_type' => 'personal',
        ]);

        return $buyer;
    }

    /** 1. STL file opens in the viewer (served inline). */
    public function test_stl_preview_file_is_served_inline(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product();
        $this->previewFile($product, 'stl', 'solid stl');

        $response = $this->get(route('products.preview-file', $product));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'model/stl');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    /** 2. OBJ source file is previewable on a free model — even for guests. */
    public function test_obj_source_previewable_on_free_model_for_guest(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product(['is_free' => true]);
        $this->sourceFile($product, 'obj', '# obj');

        $response = $this->get(route('products.preview-file', $product));

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
    }

    /** 3. GLB/GLTF opens in the viewer. */
    public function test_glb_preview_is_served(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product();
        $this->previewFile($product, 'glb', 'glTF-binary');

        $response = $this->get(route('products.preview-file', $product));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'model/gltf-binary');
    }

    /** 4. ZIP-only model is not previewable and reports the 'zip' reason. */
    public function test_zip_only_model_is_not_previewable(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product();
        $this->sourceFile($product, 'zip', 'PK..');

        $this->get(route('products.preview-file', $product))->assertNotFound();

        $data = app(ModelPreviewResolver::class)->viewerData($product->fresh()->load('files'), null);
        $this->assertFalse($data['available']);
        $this->assertSame('zip', $data['reason']);
    }

    /** 5. Paid model does NOT serve the private source file to an unauthorized user. */
    public function test_paid_model_source_not_served_to_unauthorized_user(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product(['is_free' => false, 'price' => 10]);
        $this->sourceFile($product, 'stl', 'secret stl');

        // Guest
        $this->get(route('products.preview-file', $product))->assertNotFound();

        // Logged-in non-buyer
        $this->actingAs(User::factory()->create())
            ->get(route('products.preview-file', $product))
            ->assertNotFound();

        $data = app(ModelPreviewResolver::class)->viewerData($product->fresh()->load('files'), null);
        $this->assertFalse($data['available']);
        $this->assertSame('unauthorized', $data['reason']);
    }

    /** 6. Paid model serves the source file to a buyer. */
    public function test_paid_model_source_served_to_buyer(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product(['is_free' => false, 'price' => 10]);
        $this->sourceFile($product, 'stl', 'paid stl');
        $buyer = $this->paidBuyer($product);

        $this->actingAs($buyer)
            ->get(route('products.preview-file', $product))
            ->assertOk()
            ->assertHeader('Content-Type', 'model/stl');
    }

    /** 6b. Free model preview is shown — resolver returns the source file. */
    public function test_free_model_preview_available(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product(['is_free' => true]);
        $this->sourceFile($product, 'glb', 'glb');

        $data = app(ModelPreviewResolver::class)->viewerData($product->load('files'), null);
        $this->assertTrue($data['available']);
        $this->assertSame('glb', $data['format']);
    }

    /** Preview file is preferred over a source file. */
    public function test_explicit_preview_file_is_preferred(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product();
        $this->sourceFile($product, 'obj', '# obj');
        $this->previewFile($product, 'stl', 'preview stl');

        $resolved = app(ModelPreviewResolver::class)->resolve($product->load('files'), null);
        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is_preview);
        $this->assertSame('stl', $resolved->extension);
    }

    /** The product page renders the 3D viewer for a previewable free model. */
    public function test_product_page_renders_viewer_for_free_model(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product(['is_free' => true]);
        $this->previewFile($product, 'glb', 'glb');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-model-viewer-root', false)
            ->assertSee(route('products.preview-file', $product), false);
    }

    /** Unauthorized paid model shows the purchase-required message, no viewer. */
    public function test_paid_model_shows_purchase_message_without_viewer(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $product = $this->product(['is_free' => false, 'price' => 10]);
        $this->sourceFile($product, 'stl', 'secret');

        $response = $this->get(route('products.show', $product));
        $response->assertOk();
        $response->assertDontSee('data-model-viewer-root', false);
        $response->assertSee(__('3D-перегляд недоступний'), false);
    }
}
