<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\MarketplaceAccess;
use Illuminate\Support\Facades\Storage;

class PrintProfileDownloadController extends Controller
{
    public function __invoke(Product $product, MarketplaceAccess $access)
    {
        abort_unless($product->print_profile_path, 404);
        abort_unless($access->canDownload(auth()->user(), $product), 403);
        abort_unless(Storage::disk('private')->exists($product->print_profile_path), 404);

        return Storage::disk('private')->download(
            $product->print_profile_path,
            $product->print_profile_name ?: basename($product->print_profile_path)
        );
    }
}
