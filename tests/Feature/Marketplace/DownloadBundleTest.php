<?php

namespace Tests\Feature\Marketplace;

use App\Models\License;
use App\Models\ModelFile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DownloadBundleTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_returns_zip_containing_model_file_and_license(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $author = User::factory()->create(['name' => 'Test Author']);
        $license = License::query()->create([
            'slug' => 'cc-by',
            'name' => ['uk' => 'Creative Commons BY', 'en' => 'Creative Commons BY'],
            'description' => ['uk' => 'Attribution required.', 'en' => 'Attribution required.'],
            'badge_label' => 'CC-BY',
            'badge_color' => 'sky',
            'allows_commercial_use' => true,
            'requires_attribution' => true,
            'allows_redistribution' => true,
            'allows_remix' => true,
            'allows_selling_prints' => true,
            'forbids_file_resale' => false,
        ]);

        $product = Product::query()->create([
            'user_id' => $author->id,
            'license_id' => $license->id,
            'slug' => 'bundle-test-model',
            'title' => ['uk' => 'Bundle Test Model', 'en' => 'Bundle Test Model'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
            'cover_path' => 'covers/test-cover.jpg',
        ]);

        Storage::disk('public')->put('covers/test-cover.jpg', 'fake-image-data');
        Storage::disk('private')->put('models/1/model.stl', 'fake-stl-content');

        $file = ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'source',
            'disk' => 'private',
            'path' => 'models/1/model.stl',
            'original_name' => 'model.stl',
            'extension' => 'stl',
            'size' => 18,
            'is_preview' => false,
        ]);

        $buyer = User::factory()->create();
        $order = Order::query()->create([
            'number' => 'ORD-BUNDLE-TEST',
            'user_id' => $buyer->id,
            'status' => 'paid',
            'subtotal' => 0,
            'total' => 0,
            'currency' => 'UAH',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $author->id,
            'price' => 0,
            'currency' => 'UAH',
            'license_type' => 'personal',
        ]);

        $response = $this->actingAs($buyer)
            ->get(route('products.download', [$product, $file]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');

        ob_start();
        $response->sendContent();
        $zipContent = ob_get_clean();

        $tmpZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        file_put_contents($tmpZip, $zipContent);

        $zip = new ZipArchive;
        $opened = $zip->open($tmpZip);
        $this->assertTrue($opened === true, 'Response is not a valid ZIP archive');

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        @unlink($tmpZip);

        // Model file and license must be present
        $this->assertTrue(
            collect($names)->contains(fn ($n) => str_ends_with($n, 'model.stl')),
            'ZIP does not contain model.stl. Entries: '.implode(', ', $names)
        );
        $this->assertTrue(
            collect($names)->contains(fn ($n) => str_ends_with($n, 'LICENSE.txt')),
            'ZIP does not contain LICENSE.txt. Entries: '.implode(', ', $names)
        );
        $this->assertTrue(
            collect($names)->contains(fn ($n) => str_contains($n, 'cover')),
            'ZIP does not contain cover image. Entries: '.implode(', ', $names)
        );
    }

    public function test_download_zip_license_contains_author_and_permissions(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $author = User::factory()->create(['name' => 'Jane Doe']);
        $license = License::query()->create([
            'slug' => 'personal-only',
            'name' => ['uk' => 'Personal Use Only', 'en' => 'Personal Use Only'],
            'description' => ['uk' => 'For personal use only.', 'en' => 'For personal use only.'],
            'badge_label' => 'Personal',
            'badge_color' => 'amber',
            'allows_commercial_use' => false,
            'requires_attribution' => false,
            'allows_redistribution' => false,
            'allows_remix' => false,
            'allows_selling_prints' => false,
            'forbids_file_resale' => true,
        ]);

        $product = Product::query()->create([
            'user_id' => $author->id,
            'license_id' => $license->id,
            'slug' => 'license-text-test',
            'title' => ['uk' => 'License Text Test', 'en' => 'License Text Test'],
            'description' => ['uk' => 'Desc', 'en' => 'Desc'],
            'status' => 'published',
            'price' => 0,
            'currency' => 'UAH',
            'is_free' => true,
            'published_at' => now(),
        ]);

        Storage::disk('private')->put('models/2/part.3mf', 'fake-3mf');

        $file = ModelFile::query()->create([
            'product_id' => $product->id,
            'type' => 'source',
            'disk' => 'private',
            'path' => 'models/2/part.3mf',
            'original_name' => 'part.3mf',
            'extension' => '3mf',
            'size' => 8,
            'is_preview' => false,
        ]);

        $buyer = User::factory()->create();
        $order = Order::query()->create([
            'number' => 'ORD-LIC-TEXT-TEST',
            'user_id' => $buyer->id,
            'status' => 'paid',
            'subtotal' => 0,
            'total' => 0,
            'currency' => 'UAH',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'author_id' => $author->id,
            'price' => 0,
            'currency' => 'UAH',
            'license_type' => 'personal',
        ]);

        ob_start();
        $this->actingAs($buyer)
            ->get(route('products.download', [$product, $file]))
            ->sendContent();
        $zipContent = ob_get_clean();

        $tmpZip = tempnam(sys_get_temp_dir(), 'test_lic_');
        file_put_contents($tmpZip, $zipContent);

        $zip = new ZipArchive;
        $zip->open($tmpZip);
        $licenseText = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, 'LICENSE.txt')) {
                $licenseText = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();
        @unlink($tmpZip);

        $this->assertStringContainsString('Jane Doe', $licenseText);
        $this->assertStringContainsString('Personal Use Only', $licenseText);
        $this->assertStringContainsString('Commercial use', $licenseText);
        $this->assertStringContainsString('Not allowed', $licenseText);
    }
}
