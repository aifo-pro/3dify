<?php

namespace App\Support;

use App\Models\BlogPostBlock;
use Illuminate\Support\Str;

class BlogBlockPlainText
{
    /**
     * @param  iterable<BlogPostBlock>  $blocks
     */
    public static function concatenate(iterable $blocks, string $locale, string $extraFallback = ''): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            if (! $block->is_active) {
                continue;
            }
            $p = self::forBlock($block, $locale);
            if ($p !== '') {
                $parts[] = $p;
            }
        }
        $out = trim(implode(' ', $parts));

        return $out !== '' ? $out : trim($extraFallback);
    }

    /**
     * @param  iterable<BlogPostBlock>  $blocks
     */
    public static function readingMinutes(iterable $blocks, string $locale, string $extraFallback = ''): int
    {
        $plain = self::concatenate($blocks, $locale, $extraFallback);
        preg_match_all('/\S+/u', $plain, $m);

        return max(1, (int) ceil(count($m[0] ?? []) / 200));
    }

    public static function forBlock(BlogPostBlock $block, string $locale): string
    {
        $d = $block->data ?? [];

        return match ($block->type) {
            'heading' => self::pick($d, $locale, 'title_uk', 'title_en'),
            'paragraph', 'quote' => trim(strip_tags(self::pick($d, $locale, 'text_uk', 'text_en'))),
            'image' => trim(strip_tags(self::pick($d, $locale, 'alt_uk', 'alt_en').' '.self::pick($d, $locale, 'caption_uk', 'caption_en'))),
            'image_text' => trim(strip_tags(self::pick($d, $locale, 'title_uk', 'title_en').' '.self::pick($d, $locale, 'text_uk', 'text_en'))),
            'list' => self::listPlain($d, $locale),
            'table' => self::tablePlain($d, $locale),
            'tips' => self::tipsPlain($d, $locale),
            'warning' => trim(strip_tags(self::pick($d, $locale, 'title_uk', 'title_en').' '.self::pick($d, $locale, 'text_uk', 'text_en'))),
            'steps' => self::stepsPlain($d, $locale),
            'product_cards', 'related_models' => trim(strip_tags(self::pick($d, $locale, 'title_uk', 'title_en').' '.self::pick($d, $locale, 'body_uk', 'body_en'))),
            'faq' => self::faqPlain($d, $locale),
            'cta' => trim(strip_tags(self::pick($d, $locale, 'title_uk', 'title_en').' '.self::pick($d, $locale, 'text_uk', 'text_en').' '.self::pick($d, $locale, 'button_text_uk', 'button_text_en'))),
            'divider' => '',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $d
     */
    public static function headingFragmentId(BlogPostBlock $block, array $d): string
    {
        $raw = trim((string) ($d['anchor'] ?? ''));
        if ($raw !== '') {
            $s = Str::slug($raw);

            return $s !== '' ? $s : 'blog-h-'.$block->id;
        }

        return 'blog-h-'.$block->id;
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private static function pick(array $d, string $locale, string $ukKey, string $enKey): string
    {
        $uk = trim((string) ($d[$ukKey] ?? ''));
        $en = trim((string) ($d[$enKey] ?? ''));

        return $locale === 'en' ? ($en !== '' ? $en : $uk) : ($uk !== '' ? $uk : $en);
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private static function listPlain(array $d, string $locale): string
    {
        $title = self::pick($d, $locale, 'title_uk', 'title_en');
        $key = $locale === 'en' ? 'items_en' : 'items_uk';
        $items = $d[$key] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $lines = array_map(fn ($i) => trim(strip_tags(is_string($i) ? $i : '')), $items);

        return trim($title.' '.implode(' ', $lines));
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private static function tablePlain(array $d, string $locale): string
    {
        $title = self::pick($d, $locale, 'title_uk', 'title_en');
        $headers = $d['headers'] ?? [];
        $rows = $d['rows'] ?? [];
        $cells = [];
        if (is_array($headers)) {
            foreach ($headers as $h) {
                $cells[] = trim(strip_tags(is_string($h) ? $h : ''));
            }
        }
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                foreach ($row as $cell) {
                    $cells[] = trim(strip_tags(is_string($cell) ? $cell : ''));
                }
            }
        }

        return trim($title.' '.implode(' ', $cells));
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private static function tipsPlain(array $d, string $locale): string
    {
        $title = self::pick($d, $locale, 'title_uk', 'title_en');
        $key = $locale === 'en' ? 'items_en' : 'items_uk';
        $items = $d[$key] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $lines = array_map(fn ($i) => trim(strip_tags(is_string($i) ? $i : '')), $items);

        return trim($title.' '.implode(' ', $lines));
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private static function stepsPlain(array $d, string $locale): string
    {
        $title = self::pick($d, $locale, 'title_uk', 'title_en');
        $steps = $d['steps'] ?? [];
        $parts = [$title];
        if (! is_array($steps)) {
            return $title;
        }
        foreach ($steps as $st) {
            if (! is_array($st)) {
                continue;
            }
            $tu = self::pick($st, $locale, 'title_uk', 'title_en');
            $tx = trim(strip_tags(self::pick($st, $locale, 'text_uk', 'text_en')));
            $parts[] = $tu.' '.$tx;
        }

        return trim(implode(' ', $parts));
    }

    /**
     * @param  array<string, mixed>  $d
     */
    private static function faqPlain(array $d, string $locale): string
    {
        $items = $d['items'] ?? [];
        if (! is_array($items)) {
            return '';
        }
        $parts = [];
        foreach ($items as $it) {
            if (! is_array($it)) {
                continue;
            }
            $q = self::pick($it, $locale, 'question_uk', 'question_en');
            $a = trim(strip_tags(self::pick($it, $locale, 'answer_uk', 'answer_en')));
            $parts[] = $q.' '.$a;
        }

        return trim(implode(' ', $parts));
    }
}
