<?php

namespace App\Services;

class BlogContentSanitizer
{
    public function clean(?string $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return null;
        }

        $html = preg_replace('#<(script|style|object|embed|form|input|button)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#</?span\b[^>]*>#i', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/is', '$1="#"', $html) ?? $html;

        $placeholders = [];
        $i = 0;
        $html = preg_replace_callback(
            '/<iframe\b[^>]*\bsrc\s*=\s*("|\')(https?:\/\/(?:www\.)?(?:youtube\.com\/embed\/|youtube-nocookie\.com\/embed\/)[a-zA-Z0-9_\-?=&]+)\1[^>]*>\s*<\/iframe>/i',
            function (array $m) use (&$placeholders, &$i) {
                $key = '___BLOG_YT_'.$i++.'___';
                $src = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (! preg_match('#^https?://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/)#i', $src)) {
                    return '';
                }
                $placeholders[$key] = '<iframe src="'.e($src).'" width="560" height="315" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy" title="YouTube video"></iframe>';

                return $key;
            },
            $html
        ) ?? $html;

        $allowed = '<p><br><h2><h3><h4><strong><b><em><i><u><a><ul><ol><li><blockquote><pre><code><img><figure><figcaption><table><thead><tbody><tr><th><td><hr><div>';
        $html = strip_tags($html, $allowed);

        foreach ($placeholders as $token => $iframe) {
            $html = str_replace($token, $iframe, $html);
        }

        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($match) {
            $attrs = $this->filterAttributes($match[1], ['href', 'title', 'target', 'rel']);
            if (str_contains($attrs, 'target=')) {
                $attrs .= ' rel="noopener noreferrer"';
            }

            return '<a'.$attrs.'>';
        }, $html) ?? $html;

        $html = preg_replace_callback('/<img\b([^>]*)>/i', function ($match) {
            $attrs = $this->filterAttributes($match[1], ['src', 'alt', 'title', 'width', 'height']);

            return '<img'.$attrs.' loading="lazy" decoding="async">';
        }, $html) ?? $html;

        $html = preg_replace_callback('/<div\b([^>]*)>/i', function ($match) {
            $attrs = $this->filterAttributes($match[1], ['class']);
            if (! preg_match('/class\s*=\s*"blog-gallery"/i', $attrs)) {
                return '<div>';
            }

            return '<div class="blog-gallery">';
        }, $html) ?? $html;

        $html = preg_replace_callback('/<figure\b([^>]*)>/i', function ($match) {
            return '<figure'.$this->filterAttributes($match[1], ['class']).'>';
        }, $html) ?? $html;

        return trim($html);
    }

    private function filterAttributes(string $raw, array $allowed): string
    {
        preg_match_all('/([a-zA-Z0-9_-]+)\s*=\s*("([^"]*)"|\'([^\']*)\')/', $raw, $matches, PREG_SET_ORDER);

        $attrs = [];
        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            if (! in_array($name, $allowed, true)) {
                continue;
            }

            $value = $match[3] !== '' ? $match[3] : ($match[4] ?? '');
            if (in_array($name, ['href', 'src'], true) && preg_match('/^\s*javascript:/i', $value)) {
                continue;
            }

            $attrs[$name] = e($value);
        }

        return collect($attrs)
            ->map(fn ($value, $name) => ' '.$name.'="'.$value.'"')
            ->implode('');
    }
}
