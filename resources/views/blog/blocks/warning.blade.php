@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $text = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
    $tone = ($d['tone'] ?? 'amber') === 'red' ? 'red' : 'amber';
@endphp
@if($title !== '' || $text !== '')
    <div @class([
        'rounded-3xl border p-6 sm:p-8',
        'border-amber-400/30 bg-amber-400/[0.08] text-amber-50' => $tone === 'amber',
        'border-rose-400/35 bg-rose-500/[0.10] text-rose-50' => $tone === 'red',
    ])>
        @if($title !== '')
            <h3 class="text-lg font-bold">{{ $title }}</h3>
        @endif
        @if($text !== '')
            <div @class([
                'prose prose-invert max-w-none text-sm leading-relaxed prose-p:my-1 prose-a:font-semibold',
                'prose-amber prose-a:text-amber-200' => $tone === 'amber',
                'prose-rose prose-a:text-rose-200' => $tone === 'red',
            ])>{!! $text !!}</div>
        @endif
    </div>
@endif
