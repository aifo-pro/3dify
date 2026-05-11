<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ModelFile;
use App\Models\Product;
use App\Models\User;
use App\Services\MarketplaceAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    /**
     * Authenticated direct download. Re-checks access via MarketplaceAccess.
     */
    public function __invoke(Product $product, ModelFile $file, MarketplaceAccess $access)
    {
        abort_unless($file->product_id === $product->id, 404);
        abort_unless($access->canDownload(auth()->user(), $product), 403);

        $this->logDownload($product, $file, auth()->id());

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * Signed download endpoint used by slicer custom-protocol opens.
     * The signed URL is generated server-side for users with confirmed access
     * and expires in 5 minutes; the slicer process can fetch it without the
     * browser's auth session.
     */
    public function signed(Request $request, Product $product, ModelFile $file, MarketplaceAccess $access)
    {
        // The 'signed' middleware already validated signature/expiry before
        // reaching the controller; double-check the file belongs to product.
        abort_unless($file->product_id === $product->id, 404);

        $userId = (int) $request->query('uid') ?: null;
        $user = $userId ? User::find($userId) : null;
        abort_unless($access->canDownload($user, $product), 403);

        $this->logDownload($product, $file, $userId);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
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
