@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $html = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
@endphp
@if($html !== '')
    <div class="prose prose-lg prose-invert prose-emerald max-w-none text-zinc-200 leading-[1.85] prose-p:leading-[1.85] prose-p:text-zinc-200 prose-li:marker:text-emerald-500/70 prose-a:text-emerald-300 prose-a:no-underline hover:prose-a:text-emerald-200 prose-strong:text-white">
        {!! $html !!}
    </div>
@endif
