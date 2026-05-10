<?php

namespace Tests\Feature\Marketplace;

use App\Models\ModelFile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductFileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_delete_product_file(): void
    {
        Storage::fake('private');

        $author = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'test-model',
            'title' => ['uk' => 'Test model', 'en' => 'Test model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'pending',
            'price' => 10,
            'currency' => 'EUR',
            'is_free' => false,
        ]);
        Storage::disk('private')->put('models/1/source.stl', 'solid test');
        $file = $product->files()->create([
            'type' => 'source',
            'disk' => 'private',
            'path' => 'models/1/source.stl',
            'original_name' => 'source.stl',
            'extension' => 'stl',
            'size' => 10,
        ]);

        $this->actingAs($author)
            ->delete(route('author.products.files.destroy', [$product, $file]))
            ->assertRedirect();

        $this->assertDatabaseMissing('model_files', ['id' => $file->id]);
        Storage::disk('private')->assertMissing('models/1/source.stl');
    }

    public function test_uploading_new_preview_file_replaces_active_preview_marker(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $author = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'preview-model',
            'title' => ['uk' => 'Preview model', 'en' => 'Preview model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'pending',
            'price' => 10,
            'currency' => 'EUR',
            'is_free' => false,
        ]);
        $oldPreview = $product->files()->create([
            'type' => 'preview',
            'disk' => 'public',
            'path' => 'previews/1/old.glb',
            'original_name' => 'old.glb',
            'extension' => 'glb',
            'size' => 10,
            'is_preview' => true,
        ]);

        $this->actingAs($author)
            ->patch(route('author.products.update', $product), [
                'title_uk' => 'Preview model',
                'description_uk' => 'Description',
                'price' => 10,
                'currency' => 'EUR',
                'tags' => [],
                'preview_file' => UploadedFile::fake()->create('new.glb', 12, 'model/gltf-binary'),
            ])
            ->assertRedirect();

        $this->assertFalse($oldPreview->fresh()->is_preview);
        $this->assertSame(1, ModelFile::query()->where('product_id', $product->id)->where('is_preview', true)->count());
    }

    public function test_author_can_upload_gallery_images(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $author = User::factory()->create();
        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'gallery-model',
            'title' => ['uk' => 'Gallery model', 'en' => 'Gallery model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'pending',
            'price' => 10,
            'currency' => 'EUR',
            'is_free' => false,
        ]);

        $this->actingAs($author)
            ->patch(route('author.products.update', $product), [
                'title_uk' => 'Gallery model',
                'description_uk' => 'Description',
                'price' => 10,
                'currency' => 'EUR',
                'tags' => [],
                'gallery' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.png'),
                ],
            ])
            ->assertRedirect();

        $gallery = $product->fresh()->gallery;

        $this->assertCount(2, $gallery);
        foreach ($gallery as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_author_can_remove_gallery_image(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $author = User::factory()->create();
        Storage::disk('public')->put('gallery/keep.jpg', 'keep');
        Storage::disk('public')->put('gallery/remove.jpg', 'remove');

        $product = Product::query()->create([
            'user_id' => $author->id,
            'slug' => 'gallery-remove-model',
            'title' => ['uk' => 'Gallery remove model', 'en' => 'Gallery remove model'],
            'description' => ['uk' => 'Description', 'en' => 'Description'],
            'status' => 'pending',
            'price' => 10,
            'currency' => 'EUR',
            'is_free' => false,
            'gallery' => ['gallery/keep.jpg', 'gallery/remove.jpg'],
        ]);

        $this->actingAs($author)
            ->patch(route('author.products.update', $product), [
                'title_uk' => 'Gallery remove model',
                'description_uk' => 'Description',
                'price' => 10,
                'currency' => 'EUR',
                'tags' => [],
                'gallery_remove' => [1],
            ])
            ->assertRedirect();

        $this->assertSame(['gallery/keep.jpg'], $product->fresh()->gallery);
        Storage::disk('public')->assertExists('gallery/keep.jpg');
        Storage::disk('public')->assertMissing('gallery/remove.jpg');
    }
}
