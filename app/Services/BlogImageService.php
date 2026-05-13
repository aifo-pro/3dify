<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

class BlogImageService
{
    /** Cover / OG: 1200×630, WebP when possible. */
    public function storeCover(UploadedFile $file): string
    {
        return $this->process($file, cover: true, maxWidth: 1200, maxHeight: 630, directory: 'blog/covers');
    }

    /** Open Graph image — same dimensions as cover. */
    public function storeOg(UploadedFile $file): string
    {
        return $this->process($file, cover: true, maxWidth: 1200, maxHeight: 630, directory: 'blog/og');
    }

    /** Inline editor uploads: max width 1400, preserve aspect ratio. */
    public function storeContentImage(UploadedFile $file): string
    {
        return $this->process($file, cover: false, maxWidth: 1400, maxHeight: null, directory: 'blog/content');
    }

    /**
     * @deprecated Use {@see storeCover}, {@see storeOg}, or {@see storeContentImage}.
     */
    public function store(UploadedFile $file, string $directory = 'blog'): string
    {
        return $this->process($file, cover: false, maxWidth: 1400, maxHeight: null, directory: $directory);
    }

    public function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        return str_starts_with($url, 'http') ? $url : url($url);
    }

    private function process(
        UploadedFile $file,
        bool $cover,
        int $maxWidth,
        ?int $maxHeight,
        string $directory,
    ): string {
        $manager = $this->imageManager();
        if ($manager === null) {
            return $file->store($directory, 'public');
        }

        try {
            $image = $manager->decodeSplFileInfo($file);
            if ($cover && $maxHeight !== null) {
                $image->cover($maxWidth, $maxHeight);
            } else {
                $image->scaleDown(width: $maxWidth);
            }

            $encoded = $this->encodeWebpOrJpeg($image);
            $ext = $encoded->mediaType() === 'image/webp' ? 'webp' : 'jpg';
            $path = $directory.'/'.Str::uuid().'.'.$ext;
            Storage::disk('public')->put($path, (string) $encoded);

            return $path;
        } catch (Throwable) {
            return $file->store($directory, 'public');
        }
    }

    private function encodeWebpOrJpeg(ImageInterface $image): EncodedImageInterface
    {
        try {
            return $image->encodeUsingFormat(Format::WEBP, 85);
        } catch (Throwable) {
            return $image->encodeUsingFormat(Format::JPEG, 88);
        }
    }

    private function imageManager(): ?ImageManager
    {
        try {
            if (extension_loaded('imagick')) {
                return new ImageManager(Driver::class);
            }

            return new ImageManager(\Intervention\Image\Drivers\Gd\Driver::class);
        } catch (Throwable) {
            return null;
        }
    }
}
