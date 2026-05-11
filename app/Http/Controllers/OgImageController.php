<?php

namespace App\Http\Controllers;

use App\Services\SiteSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class OgImageController extends Controller
{
    public function __invoke(SiteSettings $settings): Response
    {
        $customPath = $settings->string('brand.og_image_path');

        if ($customPath && Storage::disk('public')->exists($customPath)) {
            $contents = Storage::disk('public')->get($customPath);
            $mime = Storage::disk('public')->mimeType($customPath);

            return response($contents, 200)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'public, max-age=86400');
        }

        $cached = Cache::remember('og:default-image', 86400, function () use ($settings) {
            return $this->generate($settings->string('site.name', '3Dify'));
        });

        return response($cached, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    private function generate(string $siteName): string
    {
        $w = 1200;
        $h = 630;
        $img = imagecreatetruecolor($w, $h);

        $bg = imagecolorallocate($img, 9, 9, 11);
        imagefill($img, 0, 0, $bg);

        for ($i = 0; $i < $h; $i++) {
            $r = (int) (9 + (16 - 9) * ($i / $h));
            $g = (int) (9 + (185 - 9) * max(0, 1 - $i / ($h * 0.6)) * 0.12);
            $b = (int) (11 + (129 - 11) * max(0, 1 - $i / ($h * 0.6)) * 0.12);
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $i, $w, $i, $c);
        }

        $emerald = imagecolorallocate($img, 52, 211, 153);
        $white = imagecolorallocate($img, 255, 255, 255);
        $gray = imagecolorallocate($img, 161, 161, 170);

        $fontSize = 64;
        $subSize = 24;

        $fontPath = resource_path('fonts/figtree-bold.ttf');
        $hasFont = file_exists($fontPath);

        if ($hasFont) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $siteName);
            $textW = $bbox[2] - $bbox[0];
            $x = (int) (($w - $textW) / 2);
            imagettftext($img, $fontSize, 0, $x, 280, $emerald, $fontPath, $siteName);

            $tagline = 'Marketplace for 3D printing models';
            $bbox2 = imagettfbbox($subSize, 0, $fontPath, $tagline);
            $textW2 = $bbox2[2] - $bbox2[0];
            $x2 = (int) (($w - $textW2) / 2);
            imagettftext($img, $subSize, 0, $x2, 350, $gray, $fontPath, $tagline);

            $urlText = '3dify.dev';
            $bbox3 = imagettfbbox(18, 0, $fontPath, $urlText);
            $textW3 = $bbox3[2] - $bbox3[0];
            $x3 = (int) (($w - $textW3) / 2);
            imagettftext($img, 18, 0, $x3, 540, $white, $fontPath, $urlText);
        } else {
            $nameLen = strlen($siteName);
            $x = (int) (($w - $nameLen * imagefontwidth(5)) / 2);
            imagestring($img, 5, $x, 290, $siteName, $emerald);

            $tagline = 'Marketplace for 3D printing models';
            $tagLen = strlen($tagline);
            $x2 = (int) (($w - $tagLen * imagefontwidth(4)) / 2);
            imagestring($img, 4, $x2, 340, $tagline, $gray);
        }

        ob_start();
        imagepng($img, null, 6);
        $data = ob_get_clean();
        imagedestroy($img);

        return $data;
    }
}
