<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAccessEvent;
use App\Services\MarketplaceAccess;
use App\Services\ProductAccessLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DownloadOptionsController extends Controller
{
    /**
     * JSON endpoint consumed by the download modal.
     * Returns the file list and short-lived signed URLs that can be fed into
     * slicer custom-protocol handlers (e.g. orcaslicer://open?file=...).
     */
    public function show(Product $product, MarketplaceAccess $access, ProductAccessLogger $logger)
    {
        $user = auth()->user();
        abort_unless($access->canDownload($user, $product), 403);
        $logger->log($product, $user, ProductAccessEvent::EVENT_DOWNLOAD_MODAL_OPEN);

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

                $ext = strtolower($file->extension);
                $isSlicerCompatible = in_array($ext, ['stl', '3mf', 'obj'], true);

                return [
                    'id'                  => $file->id,
                    'name'                => $file->original_name,
                    'extension'           => $ext,
                    'extension_label'     => strtoupper($file->extension),
                    'size'                => $this->formatBytes((int) $file->size),
                    'is_slicer_compatible' => $isSlicerCompatible,
                    'download_url'        => route('products.download', [$product, $file]),
                    'signed_url'          => $signedUrl,
                    // Slicer deep-link protocol URLs
                    'slicer_urls' => $isSlicerCompatible ? [
                        'bambu'   => 'bambustudio://open?url='.urlencode($signedUrl),
                        'orca'    => 'orcaslicer://open?url='.urlencode($signedUrl),
                        'prusa'   => 'prusaslicer://open?url='.urlencode($signedUrl),
                    ] : [],
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
            'slicer_log_url' => route('products.download-options.slicer-log', $product),
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

    public function logSlicer(Request $request, Product $product, MarketplaceAccess $access, ProductAccessLogger $logger)
    {
        abort_unless($access->canDownload($request->user(), $product), 403);

        $data = $request->validate([
            'file_id' => ['nullable', 'integer'],
            'slicer' => ['nullable', 'string', 'max:80'],
        ]);

        $file = isset($data['file_id'])
            ? $product->files()->whereKey($data['file_id'])->first()
            : null;

        $logger->log(
            $product,
            $request->user(),
            ProductAccessEvent::EVENT_SLICER_OPEN,
            $file,
            $request,
            $data['slicer'] ?? null
        );

        return response()->noContent();
    }
}
