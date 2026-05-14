@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $html = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
@endphp
@if($html !== '')
    <blockquote class="rounded-3xl border border-emerald-400/20 bg-emerald-400/[0.06] px-6 py-6 shadow-inner shadow-black/20">
        <div class="prose prose-invert prose-emerald max-w-none text-lg leading-relaxed text-emerald-50/95 prose-p:text-emerald-50/95">
            {!! $html !!}
        </div>
    </blockquote>
@endif
