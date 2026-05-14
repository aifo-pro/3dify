<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostBlock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BlogPostBlockService
{
    public const TYPES = [
        'heading', 'paragraph', 'image', 'image_text', 'quote', 'list', 'table',
        'tips', 'warning', 'steps', 'product_cards', 'related_models', 'faq', 'cta', 'divider',
    ];

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public function syncBlocks(BlogPost $post, array $blocks, BlogContentSanitizer $sanitizer): void
    {
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
            default => $data,
        };
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
