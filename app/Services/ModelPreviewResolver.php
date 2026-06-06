<?php

namespace App\Services;

use App\Models\ModelFile;
use App\Models\Product;
use App\Models\User;

/**
 * Decides which file (if any) may be rendered in the public 3D viewer for a
 * given product + viewer, enforcing access control.
 *
 * Rules:
 *  - An explicit 3D preview file (uploaded deliberately, lives on the public
 *    disk) is viewable by everyone.
 *  - Otherwise the first viewable source file may be shown, but only when the
 *    viewer is allowed to access it: free models → anyone, paid models → only
 *    buyers / the author / moderators (same gate as downloads).
 *  - ZIP archives are never rendered directly.
 */
class ModelPreviewResolver
{
    /** Formats the front-end Three.js viewer can render. */
    public const VIEWABLE = ['stl', 'obj', 'glb', 'gltf', '3mf'];

    public function __construct(private MarketplaceAccess $access) {}

    /**
     * Resolve the ModelFile to render in the viewer, or null when none is
     * available / permitted.
     */
    public function resolve(Product $product, ?User $user): ?ModelFile
    {
        $files = $this->files($product);

        // 1. Explicit 3D preview file (public disk) — safe for everyone.
        $preview = $files->first(fn (ModelFile $f) => $f->is_preview
            && $f->disk === 'public'
            && $this->isViewable($f));

        if ($preview) {
            return $preview;
        }

        // 2. First viewable source file — gated by access.
        if ($this->canViewSource($product, $user)) {
            return $files->first(fn (ModelFile $f) => ! $f->is_preview && $this->isViewable($f));
        }

        return null;
    }

    /**
     * Build the data the Blade viewer component needs, including a reason code
     * when no preview is available so a helpful message can be shown.
     *
     * @return array{available: bool, format: ?string, src: ?string, reason: ?string}
     */
    public function viewerData(Product $product, ?User $user): array
    {
        $file = $this->resolve($product, $user);

        if ($file) {
            return [
                'available' => true,
                'format' => $this->format($file),
                'src' => route('products.preview-file', $product),
                'reason' => null,
            ];
        }

        $files = $this->files($product);
        $hasViewableSource = $files->contains(fn (ModelFile $f) => ! $f->is_preview && $this->isViewable($f));
        $hasFiles = $files->isNotEmpty();
        $onlyArchives = $hasFiles && $files->every(
            fn (ModelFile $f) => strtolower((string) $f->extension) === 'zip'
        );

        // 'unauthorized' → a viewable file exists but the viewer can't access it
        // 'zip'          → only archives uploaded; cannot be previewed directly
        // 'none'         → no 3D-viewable file at all
        $reason = match (true) {
            $hasViewableSource => 'unauthorized',
            $onlyArchives => 'zip',
            default => 'none',
        };

        return [
            'available' => false,
            'format' => null,
            'src' => null,
            'reason' => $reason,
        ];
    }

    public function isViewable(ModelFile $file): bool
    {
        return in_array(strtolower((string) $file->extension), self::VIEWABLE, true);
    }

    public function format(ModelFile $file): string
    {
        return strtolower((string) $file->extension);
    }

    private function canViewSource(Product $product, ?User $user): bool
    {
        if ($product->is_free) {
            return true;
        }

        return $this->access->canDownload($user, $product);
    }

    /** @return \Illuminate\Support\Collection<int, ModelFile> */
    private function files(Product $product)
    {
        return $product->relationLoaded('files')
            ? $product->files
            : $product->files()->get();
    }
}
