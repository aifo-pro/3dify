<?php

namespace App\Services;

use App\Models\ModelFile;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ModelDownloadBundler
{
    /**
     * Build a ZIP containing the model file, product photos, and license text,
     * then stream it to the browser and clean up the temp file.
     */
    public function download(Product $product, ModelFile $file): StreamedResponse
    {
        $product->loadMissing(['license', 'author']);

        $zipPath = $this->buildZip($product, $file);
        $zipName = $this->zipName($product, $file);
        $zipSize = filesize($zipPath);

        return response()->streamDownload(function () use ($zipPath) {
            $handle = fopen($zipPath, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 65536);
                flush();
            }
            fclose($handle);
            @unlink($zipPath);
        }, $zipName, [
            'Content-Type' => 'application/zip',
            'Content-Length' => $zipSize,
            'Content-Disposition' => 'attachment; filename="'.$zipName.'"',
        ]);
    }

    private function buildZip(Product $product, ModelFile $file): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), '3dify_dl_');

        $zip = new ZipArchive;
        $zip->open($tmpPath, ZipArchive::OVERWRITE);

        $folder = $this->sanitizeName((string) $product->localized('title')) ?: 'model';

        // Model file
        $modelContents = Storage::disk($file->disk)->get($file->path);
        if ($modelContents !== null) {
            $zip->addFromString("{$folder}/{$file->original_name}", $modelContents);
        }

        // Cover image
        if ($product->cover_path) {
            $coverContents = $this->fetchImage($product->cover_path);
            if ($coverContents !== null) {
                $ext = pathinfo($product->cover_path, PATHINFO_EXTENSION) ?: 'jpg';
                $zip->addFromString("{$folder}/cover.{$ext}", $coverContents);
            }
        }

        // Gallery images
        $gallery = array_filter((array) ($product->gallery ?? []));
        foreach (array_values($gallery) as $idx => $galleryPath) {
            if (! is_string($galleryPath)) {
                continue;
            }
            $imgContents = $this->fetchImage($galleryPath);
            if ($imgContents !== null) {
                $ext = pathinfo($galleryPath, PATHINFO_EXTENSION) ?: 'jpg';
                $zip->addFromString("{$folder}/gallery/".($idx + 1).".{$ext}", $imgContents);
            }
        }

        // License text
        $zip->addFromString("{$folder}/LICENSE.txt", $this->buildLicenseText($product));

        $zip->close();

        return $tmpPath;
    }

    private function fetchImage(string $path): ?string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $content = @file_get_contents($path, false, $ctx);
            return $content !== false ? $content : null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->get($path)
            : null;
    }

    private function buildLicenseText(Product $product): string
    {
        $title = $product->localized('title');
        $author = $product->author?->displayName() ?? 'Unknown';
        $site = rtrim(config('app.url'), '/');
        $productUrl = $site.'/models/'.$product->slug;

        $lines = [
            str_repeat('=', 60),
            strtoupper($title),
            str_repeat('=', 60),
            '',
            'Author : '.$author,
            'Source : '.$productUrl,
            'Date   : '.now()->toDateString(),
            '',
        ];

        $license = $product->license;
        if ($license) {
            $lines[] = str_repeat('-', 60);
            $lines[] = 'LICENSE: '.$license->localized('name');
            $lines[] = str_repeat('-', 60);
            $description = trim($license->localized('description'));
            if ($description !== '') {
                $lines[] = '';
                $lines[] = $description;
            }
            $lines[] = '';
            $lines[] = 'Permissions:';
            $lines[] = '  Commercial use   : '.($license->allows_commercial_use ? 'Allowed' : 'Not allowed');
            $lines[] = '  Redistribution   : '.($license->allows_redistribution ? 'Allowed' : 'Not allowed');
            $lines[] = '  Remix / modify   : '.($license->allows_remix ? 'Allowed' : 'Not allowed');
            $lines[] = '  Sell prints      : '.($license->allows_selling_prints ? 'Allowed' : 'Not allowed');
            $lines[] = '  Resell files     : '.($license->forbids_file_resale ? 'Not allowed' : 'Allowed');
            if ($license->requires_attribution) {
                $lines[] = '  Attribution      : Required — credit "'.$author.'" and link to '.$productUrl;
            }
        } else {
            $lines[] = 'License: All rights reserved. No redistribution without permission.';
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 60);
        $lines[] = 'This file was downloaded from '.$site;
        $lines[] = str_repeat('-', 60);

        return implode("\n", $lines)."\n";
    }

    private function zipName(Product $product, ModelFile $file): string
    {
        $title = $this->sanitizeName((string) $product->localized('title'));
        $base = Str::slug($title ?: $product->slug);
        $ext = pathinfo($file->original_name, PATHINFO_EXTENSION);

        return $base.($ext ? '-'.$ext : '').'.zip';
    }

    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^\w\s\-_()]/u', '', $name) ?? $name;
    }
}
