<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\MarketplaceAccess;
use App\Services\ModelDownloadBundler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BulkDownloadController extends Controller
{
    /**
     * Download all source files from a single product as one ZIP.
     */
    public function product(Request $request, Product $product, MarketplaceAccess $access)
    {
        abort_unless($access->canDownload($request->user(), $product), 403);

        $product->loadMissing(['files', 'license', 'author']);

        $sourceFiles = $product->files->where('is_preview', false)->where('type', 'source')->values();
        abort_if($sourceFiles->isEmpty(), 404, 'No source files.');

        $tmpPath = tempnam(sys_get_temp_dir(), '3dify_bulk_');
        $zip     = new ZipArchive;
        $zip->open($tmpPath, ZipArchive::OVERWRITE);

        $folder = Str::slug((string) $product->localized('title')) ?: $product->slug;

        foreach ($sourceFiles as $file) {
            $contents = Storage::disk($file->disk)->get($file->path);
            if ($contents !== null) {
                $zip->addFromString("{$folder}/{$file->original_name}", $contents);
            }
        }

        // License text
        $licenseText = $this->buildLicenseText($product);
        $zip->addFromString("{$folder}/LICENSE.txt", $licenseText);

        $zip->close();

        $product->increment('downloads_count');

        return response()->streamDownload(function () use ($tmpPath) {
            $handle = fopen($tmpPath, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 65536);
                flush();
            }
            fclose($handle);
            @unlink($tmpPath);
        }, $folder.'.zip', [
            'Content-Type'        => 'application/zip',
            'Content-Length'      => filesize($tmpPath),
            'Content-Disposition' => 'attachment; filename="'.$folder.'.zip"',
        ]);
    }

    /**
     * Download all purchased products as one big ZIP (library bulk export).
     */
    public function library(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->with(['items.product.files', 'items.product.license', 'items.product.author'])
            ->get();

        $products = $orders->flatMap(fn ($o) => $o->items->map(fn ($i) => $i->product))
            ->filter()
            ->unique('id')
            ->values();

        abort_if($products->isEmpty(), 404, 'No purchased products.');

        $tmpPath = tempnam(sys_get_temp_dir(), '3dify_library_');
        $zip     = new ZipArchive;
        $zip->open($tmpPath, ZipArchive::OVERWRITE);

        foreach ($products as $product) {
            $folder      = Str::slug((string) $product->localized('title')) ?: 'model-'.$product->id;
            $sourceFiles = $product->files->where('is_preview', false)->where('type', 'source');

            foreach ($sourceFiles as $file) {
                $contents = Storage::disk($file->disk)->get($file->path);
                if ($contents !== null) {
                    $zip->addFromString("{$folder}/{$file->original_name}", $contents);
                }
            }

            $zip->addFromString("{$folder}/LICENSE.txt", $this->buildLicenseText($product));
        }

        $zip->close();

        return response()->streamDownload(function () use ($tmpPath) {
            $handle = fopen($tmpPath, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 65536);
                flush();
            }
            fclose($handle);
            @unlink($tmpPath);
        }, '3dify-library-'.now()->format('Y-m-d').'.zip', [
            'Content-Type'        => 'application/zip',
            'Content-Length'      => filesize($tmpPath),
            'Content-Disposition' => 'attachment; filename="3dify-library-'.now()->format('Y-m-d').'.zip"',
        ]);
    }

    private function buildLicenseText(Product $product): string
    {
        $title  = $product->localized('title');
        $author = $product->author?->displayName() ?? 'Unknown';
        $url    = rtrim(config('app.url'), '/').'/models/'.$product->slug;
        $lines  = [str_repeat('=', 60), strtoupper($title), str_repeat('=', 60), '', "Author : {$author}", "Source : {$url}", "Date   : ".now()->toDateString(), ''];

        if ($license = $product->license) {
            $lines[] = 'LICENSE: '.$license->localized('name');
            $desc    = trim($license->localized('description'));
            if ($desc) {
                $lines[] = '';
                $lines[] = $desc;
            }
            $lines[] = '';
            $lines[] = 'Commercial use   : '.($license->allows_commercial_use ? 'Allowed' : 'Not allowed');
            $lines[] = 'Redistribution   : '.($license->allows_redistribution ? 'Allowed' : 'Not allowed');
        } else {
            $lines[] = 'License: All rights reserved.';
        }

        return implode("\n", $lines)."\n";
    }
}
