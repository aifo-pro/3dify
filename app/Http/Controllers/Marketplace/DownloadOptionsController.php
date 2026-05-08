<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\MarketplaceAccess;
use Illuminate\Support\Facades\URL;

class DownloadOptionsController extends Controller
{
    /**
     * JSON endpoint consumed by the download modal.
     * Returns the file list and short-lived signed URLs that can be fed into
     * slicer custom-protocol handlers (e.g. orcaslicer://open?file=...).
     */
    public function show(Product $product, MarketplaceAccess $access)
    {
        $user = auth()->user();
        abort_unless($access->canDownload($user, $product), 403);

        // Hide preview-only files; only downloadable source files are returned.
        $files = $product->files
            ->reject(fn ($f) => (bool) $f->is_preview)
            ->map(function ($file) use ($product, $user) {
                $signedUrl = URL::temporarySignedRoute(
                    'products.download.signed',
                    now()->addMinutes(5),
                    [
                        'product' => $product->slug,
                        'file' => $file->id,
                        // user id helps tie the link to a specific user for audit.
                        'uid' => $user->id,
                    ]
                );

                return [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'extension' => strtolower($file->extension),
                    'extension_label' => strtoupper($file->extension),
                    'size' => $this->formatBytes((int) $file->size),
                    'is_slicer_compatible' => in_array(strtolower($file->extension), ['stl', '3mf', 'obj'], true),
                    'download_url' => route('products.download', [$product, $file]),
                    'signed_url' => $signedUrl,
                ];
            })
            ->values();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'title' => $product->localized('title'),
            ],
            'files' => $files,
            'expires_in_seconds' => 5 * 60,
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
