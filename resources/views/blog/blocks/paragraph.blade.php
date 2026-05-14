@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $html = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
@endphp
@if($html !== '')
    <div class="prose prose-invert prose-emerald max-w-none text-base leading-relaxed text-zinc-300 prose-p:text-zinc-300 prose-a:text-emerald-300 prose-a:no-underline hover:prose-a:text-emerald-200 prose-strong:text-white">
        {!! $html !!}
    </div>
@endif
