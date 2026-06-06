<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ModelPreviewResolver;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a product's 3D-viewable file inline for the in-browser viewer.
 *
 * Access is re-checked server-side on every request via ModelPreviewResolver,
 * so a private paid source file is never exposed to an unauthorized viewer even
 * if the URL is guessed or shared. The file to serve is resolved from the
 * product (never from user input), preventing path traversal.
 */
class ModelPreviewController extends Controller
{
    public function __invoke(Product $product, ModelPreviewResolver $resolver)
    {
        abort_unless(
            $product->status === 'published'
                || auth()->id() === $product->user_id
                || auth()->user()?->canModerate(),
            404
        );

        $file = $resolver->resolve($product, auth()->user());
        abort_if($file === null, 404);

        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404);

        $mime = match (strtolower((string) $file->extension)) {
            'glb' => 'model/gltf-binary',
            'gltf' => 'model/gltf+json',
            'obj' => 'text/plain',
            'stl' => 'model/stl',
            '3mf' => 'model/3mf',
            default => 'application/octet-stream',
        };

        $name = $file->original_name ?: ('model.'.$file->extension);
        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($name).'"',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
            // Allow the in-page fetch even if assets are served from a CDN host.
            'Access-Control-Allow-Origin' => '*',
        ];

        // Prefer a BinaryFileResponse for local disks: it sets Content-Length and
        // supports range requests, which streams binary models far more reliably
        // through nginx / Cloudflare than a chunked StreamedResponse.
        $localPath = method_exists($disk, 'path') ? $disk->path($file->path) : null;
        if ($localPath !== null && is_file($localPath)) {
            return response()->file($localPath, $headers);
        }

        return $disk->response($file->path, $name, $headers);
    }
}
