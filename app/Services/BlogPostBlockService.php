<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostBlock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BlogPostBlockService
{
    public const TYPES = [
        'heading', 'paragraph', 'image', 'image_text', 'quote', 'list', 'table',
        'tips', 'warning', 'steps', 'product_cards', 'related_models', 'faq', 'cta', 'divider',
        'gallery', 'code', 'filament_card', 'printer_card', 'material_card',
        'subscribe_box', 'spacer',
    ];

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function syncBlocks(BlogPost $post, array $blocks, BlogContentSanitizer $sanitizer): void
    {
        if (! Schema::hasTable('blog_post_blocks')) {
            return;
        }

        $normalized = $this->validateAndNormalize($blocks);

        DB::transaction(function () use ($post, $normalized, $sanitizer) {
            $post->blocks()->delete();
            foreach ($normalized as $i => $row) {
                BlogPostBlock::create([
                    'blog_post_id' => $post->id,
                    'type' => $row['type'],
                    'sort_order' => $i,
                    'is_active' => $row['is_active'],
                    'data' => $this->sanitizeData($row['type'], $row['data'], $sanitizer),
                ]);
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array{type:string,data:array<string,mixed>,is_active:bool}>
     */
    public function validateAndNormalize(array $blocks): array
    {
        if (count($blocks) > 80) {
            throw ValidationException::withMessages(['blocks_json' => [__('blog.admin.blocks_too_many')]]);
        }

        $out = [];
        foreach ($blocks as $i => $raw) {
            if (! is_array($raw)) {
                throw ValidationException::withMessages(['blocks_json' => [__('blog.admin.blocks_invalid', ['idx' => $i])]]);
            }
            $type = (string) ($raw['type'] ?? '');
            if (! in_array($type, self::TYPES, true)) {
                throw ValidationException::withMessages(['blocks_json' => [__('blog.admin.block_bad_type', ['type' => $type])]]);
            }
            $data = is_array($raw['data'] ?? null) ? $raw['data'] : [];
            $isActive = filter_var($raw['is_active'] ?? true, FILTER_VALIDATE_BOOL);
            $out[] = ['type' => $type, 'data' => $data, 'is_active' => $isActive];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeData(string $type, array $data, BlogContentSanitizer $sanitizer): array
    {
        $clean = fn (?string $html) => $sanitizer->clean($html);

        return match ($type) {
            'heading' => [
                'level' => in_array((int) ($data['level'] ?? 2), [2, 3], true) ? (int) $data['level'] : 2,
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'anchor' => trim((string) ($data['anchor'] ?? '')),
            ],
            'paragraph' => [
                'text_uk' => $clean($data['text_uk'] ?? null),
                'text_en' => $clean($data['text_en'] ?? null),
            ],
            'image' => [
                'path' => trim((string) ($data['path'] ?? '')),
                'alt_uk' => trim((string) ($data['alt_uk'] ?? '')),
                'alt_en' => trim((string) ($data['alt_en'] ?? '')),
                'caption_uk' => trim((string) ($data['caption_uk'] ?? '')),
                'caption_en' => trim((string) ($data['caption_en'] ?? '')),
            ],
            'image_text' => [
                'path' => trim((string) ($data['path'] ?? '')),
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'text_uk' => $clean($data['text_uk'] ?? null),
                'text_en' => $clean($data['text_en'] ?? null),
                'image_position' => ($data['image_position'] ?? 'left') === 'right' ? 'right' : 'left',
            ],
            'quote' => [
                'text_uk' => $clean($data['text_uk'] ?? null),
                'text_en' => $clean($data['text_en'] ?? null),
            ],
            'list' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'style' => in_array($data['style'] ?? 'bullets', ['bullets', 'checks', 'numbers'], true)
                    ? $data['style']
                    : 'bullets',
                'items_uk' => $this->sanitizeStringList($data['items_uk'] ?? [], $sanitizer),
                'items_en' => $this->sanitizeStringList($data['items_en'] ?? [], $sanitizer),
            ],
            'table' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'headers' => $this->sanitizeStringList($data['headers'] ?? [], $sanitizer, allowHtml: false),
                'rows' => $this->sanitizeTableRows($data['rows'] ?? [], $sanitizer),
            ],
            'tips' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'icon' => trim((string) ($data['icon'] ?? '')),
                'items_uk' => $this->sanitizeStringList($data['items_uk'] ?? [], $sanitizer),
                'items_en' => $this->sanitizeStringList($data['items_en'] ?? [], $sanitizer),
            ],
            'warning' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'text_uk' => $clean($data['text_uk'] ?? null),
                'text_en' => $clean($data['text_en'] ?? null),
                'tone' => ($data['tone'] ?? 'amber') === 'red' ? 'red' : 'amber',
            ],
            'steps' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'steps' => $this->sanitizeSteps($data['steps'] ?? [], $sanitizer),
            ],
            'product_cards', 'related_models' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'body_uk' => $clean($data['body_uk'] ?? null),
                'body_en' => $clean($data['body_en'] ?? null),
                'href' => trim((string) ($data['href'] ?? '')),
            ],
            'faq' => ['items' => $this->sanitizeFaqItems($data['items'] ?? [], $sanitizer)],
            'cta' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'text_uk' => $clean($data['text_uk'] ?? null),
                'text_en' => $clean($data['text_en'] ?? null),
                'button_text_uk' => trim((string) ($data['button_text_uk'] ?? '')),
                'button_text_en' => trim((string) ($data['button_text_en'] ?? '')),
                'button_url' => trim((string) ($data['button_url'] ?? '')),
            ],
            'divider' => [],
            'gallery' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'style' => in_array($data['style'] ?? 'grid', ['grid', 'masonry', 'slider'], true) ? $data['style'] : 'grid',
                'images' => $this->sanitizeGalleryImages($data['images'] ?? []),
            ],
            'code' => [
                'language' => trim(strip_tags((string) ($data['language'] ?? 'plaintext'))),
                'code' => trim((string) ($data['code'] ?? '')),
                'caption' => trim(strip_tags((string) ($data['caption'] ?? ''))),
            ],
            'filament_card' => [
                'name_uk' => trim((string) ($data['name_uk'] ?? '')),
                'name_en' => trim((string) ($data['name_en'] ?? '')),
                'brand' => trim(strip_tags((string) ($data['brand'] ?? ''))),
                'material' => trim(strip_tags((string) ($data['material'] ?? 'PLA'))),
                'temp_nozzle' => trim(strip_tags((string) ($data['temp_nozzle'] ?? ''))),
                'temp_bed' => trim(strip_tags((string) ($data['temp_bed'] ?? ''))),
                'color' => trim(strip_tags((string) ($data['color'] ?? ''))),
                'price' => trim(strip_tags((string) ($data['price'] ?? ''))),
                'href' => trim((string) ($data['href'] ?? '')),
            ],
            'printer_card' => [
                'name_uk' => trim((string) ($data['name_uk'] ?? '')),
                'name_en' => trim((string) ($data['name_en'] ?? '')),
                'brand' => trim(strip_tags((string) ($data['brand'] ?? ''))),
                'build_volume' => trim(strip_tags((string) ($data['build_volume'] ?? ''))),
                'tech' => in_array($data['tech'] ?? 'FDM', ['FDM', 'MSLA', 'SLA'], true) ? $data['tech'] : 'FDM',
                'price' => trim(strip_tags((string) ($data['price'] ?? ''))),
                'href' => trim((string) ($data['href'] ?? '')),
            ],
            'material_card' => [
                'name_uk' => trim((string) ($data['name_uk'] ?? '')),
                'name_en' => trim((string) ($data['name_en'] ?? '')),
                'brand' => trim(strip_tags((string) ($data['brand'] ?? ''))),
                'type' => trim(strip_tags((string) ($data['type'] ?? ''))),
                'items_uk' => $this->sanitizeStringList($data['items_uk'] ?? [], $sanitizer, false),
                'items_en' => $this->sanitizeStringList($data['items_en'] ?? [], $sanitizer, false),
                'href' => trim((string) ($data['href'] ?? '')),
            ],
            'subscribe_box' => [
                'title_uk' => trim((string) ($data['title_uk'] ?? '')),
                'title_en' => trim((string) ($data['title_en'] ?? '')),
                'text_uk' => trim((string) ($data['text_uk'] ?? '')),
                'text_en' => trim((string) ($data['text_en'] ?? '')),
            ],
            'spacer' => [
                'size' => in_array($data['size'] ?? 'md', ['sm', 'md', 'lg', 'xl'], true) ? $data['size'] : 'md',
            ],
            default => $data,
        };
    }

    /**
     * @param  list<mixed>  $images
     * @return list<array<string,string>>
     */
    private function sanitizeGalleryImages(array $images): array
    {
        $out = [];
        foreach (array_slice($images, 0, 50) as $img) {
            if (! is_array($img)) {
                continue;
            }
            $path = trim((string) ($img['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $out[] = [
                'path' => $path,
                'alt_uk' => trim(strip_tags((string) ($img['alt_uk'] ?? ''))),
                'alt_en' => trim(strip_tags((string) ($img['alt_en'] ?? ''))),
            ];
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $list
     * @return list<string>
     */
    private function sanitizeStringList(array $list, BlogContentSanitizer $sanitizer, bool $allowHtml = true): array
    {
        $out = [];
        foreach (array_slice($list, 0, 80) as $item) {
            $s = is_string($item) ? $item : '';
            if ($allowHtml) {
                $out[] = (string) ($sanitizer->clean($s) ?? '');
            } else {
                $out[] = trim(strip_tags($s));
            }
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<list<string>>
     */
    private function sanitizeTableRows(array $rows, BlogContentSanitizer $sanitizer): array
    {
        $out = [];
        foreach (array_slice($rows, 0, 50) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $line = [];
            foreach (array_slice($row, 0, 20) as $cell) {
                $line[] = (string) ($sanitizer->clean(is_string($cell) ? $cell : '') ?? '');
            }
            $out[] = $line;
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $steps
     * @return list<array<string, string>>
     */
    private function sanitizeSteps(array $steps, BlogContentSanitizer $sanitizer): array
    {
        $out = [];
        foreach (array_slice($steps, 0, 40) as $step) {
            if (! is_array($step)) {
                continue;
            }
            $out[] = [
                'title_uk' => trim((string) ($step['title_uk'] ?? '')),
                'title_en' => trim((string) ($step['title_en'] ?? '')),
                'text_uk' => $sanitizer->clean($step['text_uk'] ?? null),
                'text_en' => $sanitizer->clean($step['text_en'] ?? null),
            ];
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array<string, string|null>>
     */
    private function sanitizeFaqItems(array $items, BlogContentSanitizer $sanitizer): array
    {
        $out = [];
        foreach (array_slice($items, 0, 60) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = [
                'question_uk' => trim((string) ($item['question_uk'] ?? '')),
                'answer_uk' => $sanitizer->clean($item['answer_uk'] ?? null),
                'question_en' => trim((string) ($item['question_en'] ?? '')),
                'answer_en' => $sanitizer->clean($item['answer_en'] ?? null),
            ];
        }

        return $out;
    }
}
