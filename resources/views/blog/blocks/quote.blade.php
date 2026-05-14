@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $html = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
@endphp
@if($html !== '')
        <blockquote class="rounded-[1.75rem] border border-emerald-400/20 bg-emerald-400/[0.06] px-7 py-7 shadow-inner shadow-black/20 sm:px-8 sm:py-8">
        <div class="prose prose-lg prose-invert prose-emerald max-w-none text-emerald-50/95 leading-[1.82] prose-p:text-emerald-50/95 prose-p:leading-[1.82]">
            {!! $html !!}
        </div>
    </blockquote>
@endif
