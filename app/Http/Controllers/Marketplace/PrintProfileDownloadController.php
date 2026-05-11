<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAccessEvent;
use App\Services\MarketplaceAccess;
use App\Services\ProductAccessLogger;
use Illuminate\Support\Facades\Storage;

class PrintProfileDownloadController extends Controller
{
    public function __invoke(Product $product, MarketplaceAccess $access, ProductAccessLogger $logger)
    {
        abort_unless($product->print_profile_path, 404);
        abort_unless($access->canDownload(auth()->user(), $product), 403);
        abort_unless(Storage::disk('private')->exists($product->print_profile_path), 404);

        $logger->log(
            $product,
            auth()->user(),
            ProductAccessEvent::EVENT_PRINT_PROFILE_DOWNLOAD,
            null,
            request(),
            'print-profile',
            ['file_name' => $product->print_profile_name ?: basename($product->print_profile_path)]
        );

        return Storage::disk('private')->download(
            $product->print_profile_path,
            $product->print_profile_name ?: basename($product->print_profile_path)
        );
    }
}
