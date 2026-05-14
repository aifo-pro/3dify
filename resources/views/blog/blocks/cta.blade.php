@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $text = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
    $btn = $locale === 'en'
        ? (trim((string) ($d['button_text_en'] ?? '')) ?: trim((string) ($d['button_text_uk'] ?? '')))
        : (trim((string) ($d['button_text_uk'] ?? '')) ?: trim((string) ($d['button_text_en'] ?? '')));
    $url = trim((string) ($d['button_url'] ?? ''));
@endphp
@if($title !== '' || $text !== '' || ($btn !== '' && $url !== ''))
    <div class="relative overflow-hidden rounded-3xl border border-emerald-400/30 bg-gradient-to-br from-emerald-400/20 via-zinc-900 to-zinc-950 p-8 sm:p-10 shadow-2xl shadow-emerald-900/30">
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="relative space-y-4">
            @if($title !== '')
                <h3 class="text-2xl font-black tracking-tight text-white">{{ $title }}</h3>
            @endif
            @if($text !== '')
                <div class="prose prose-lg prose-invert prose-emerald max-w-none text-zinc-200 leading-[1.82] prose-p:leading-[1.82]">{!! $text !!}</div>
            @endif
            @if($btn !== '' && $url !== '')
                <a href="{{ $url }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-8 py-3.5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/30 transition hover:bg-emerald-300">{{ $btn }}</a>
            @endif
        </div>
    </div>
@endif
