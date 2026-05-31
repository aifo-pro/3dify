<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ModelFile;
use App\Models\Product;
use App\Models\ProductAccessEvent;
use App\Models\User;
use App\Services\MarketplaceAccess;
use App\Services\ModelDownloadBundler;
use App\Services\ProductAccessLogger;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    /**
     * Authenticated direct download. Re-checks access via MarketplaceAccess.
     */
    public function __invoke(Product $product, ModelFile $file, MarketplaceAccess $access, ProductAccessLogger $logger, ModelDownloadBundler $bundler)
    {
        abort_unless($file->product_id === $product->id, 404);
        abort_unless($access->canDownload(auth()->user(), $product), 403);

        $this->logDownload($product, $file, auth()->id());
        $logger->log($product, auth()->user(), ProductAccessEvent::EVENT_DOWNLOAD, $file);

        return $bundler->download($product, $file);
    }

    /**
     * Signed download endpoint used by slicer custom-protocol opens.
     */
    public function signed(Request $request, Product $product, ModelFile $file, MarketplaceAccess $access, ProductAccessLogger $logger, ModelDownloadBundler $bundler)
    {
        abort_unless($file->product_id === $product->id, 404);

        $userId = (int) $request->query('uid') ?: null;
        $user = $userId ? User::find($userId) : null;
        abort_unless($access->canDownload($user, $product), 403);

        $this->logDownload($product, $file, $userId);
        $logger->log(
            $product,
            $user,
            ProductAccessEvent::EVENT_SIGNED_DOWNLOAD,
            $file,
            $request,
            $request->query('via') ?: 'signed-url'
        );

        return $bundler->download($product, $file);
    }

    private function logDownload(Product $product, ModelFile $file, ?int $userId): void
    {
        $product->increment('downloads_count');
        $product->downloads()->create([
            'user_id' => $userId,
            'model_file_id' => $file->id,
            'ip_address' => request()->ip(),
            'downloaded_at' => now(),
        ]);
    }
}
