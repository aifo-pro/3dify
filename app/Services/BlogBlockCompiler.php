<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogBlockCompiler
{
    /**
     * @param  array<string, mixed>|null  $document
     * @return array{uk: string, en: string}
     */
    public function compile(?array $document): array
    {
        if (! is_array($document)) {
            return ['uk' => '', 'en' => ''];
        }

        $blocks = $document['blocks'] ?? null;
        if (! is_array($blocks)) {
            return ['uk' => '', 'en' => ''];
        }

        $uk = [];
        $en = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            $uk[] = $this->renderBlock($type, $block, 'uk');
            $en[] = $this->renderBlock($type, $block, 'en');
        }

        return [
            'uk' => trim(implode("\n\n", array_filter($uk))),
            'en' => trim(implode("\n\n", array_filter($en))),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $postContentUk
     * @param  array<string, mixed>|null  $postContentEn
     * @return array<string, mixed>
     */
    public function defaultDocumentFromLegacy(?string $postContentUk, ?string $postContentEn): array
    {
        return [
            'version' => 1,
            'blocks' => [
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'richtext',
                    'uk' => ['html' => $postContentUk ?? ''],
                    'en' => ['html' => $postContentEn ?? ''],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function renderBlock(string $type, array $block, string $locale): string
    {
        $loc = is_array($block[$locale] ?? null) ? $block[$locale] : [];

        return match ($type) {
            'heading' => $this->renderHeading($loc),
            'richtext' => $this->renderRich($loc),
            'image' => $this->renderImage($block, $locale),
            'quote' => $this->renderQuote($loc),
            'divider' => '<hr>',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $loc
     */
    private function renderHeading(array $loc): string
    {
        $text = trim((string) ($loc['text'] ?? ''));
        if ($text === '') {
            return '';
        }
        $level = (int) ($loc['level'] ?? 2);
        if (! in_array($level, [2, 3], true)) {
            $level = 2;
        }

        return '<h'.$level.'>'.e($text).'</h'.$level.'>';
    }

    /**
     * @param  array<string, mixed>  $loc
     */
    private function renderRich(array $loc): string
    {
        return trim((string) ($loc['html'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function renderImage(array $block, string $locale): string
    {
        $path = trim((string) ($block['path'] ?? ''));
        if ($path === '') {
            return '';
        }

        $loc = is_array($block[$locale] ?? null) ? $block[$locale] : [];
        $alt = trim((string) ($loc['alt'] ?? ''));
        $caption = trim((string) ($loc['caption'] ?? ''));

        $url = Storage::disk('public')->url($path);
        if (! str_starts_with($url, 'http')) {
            $url = url($url);
        }

        $img = '<img src="'.e($url).'" alt="'.e($alt).'" loading="lazy" decoding="async">';

        if ($caption === '') {
            return '<figure>'.$img.'</figure>';
        }

        return '<figure>'.$img.'<figcaption>'.e($caption).'</figcaption></figure>';
    }

    /**
     * @param  array<string, mixed>  $loc
     */
    private function renderQuote(array $loc): string
    {
        $text = trim((string) ($loc['text'] ?? ''));
        if ($text === '') {
            return '';
        }

        return '<blockquote><p>'.e($text).'</p></blockquote>';
    }
}
