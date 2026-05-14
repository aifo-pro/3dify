@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $body = $locale === 'en'
        ? (trim((string) ($d['body_en'] ?? '')) ?: trim((string) ($d['body_uk'] ?? '')))
        : (trim((string) ($d['body_uk'] ?? '')) ?: trim((string) ($d['body_en'] ?? '')));
    $href = trim((string) ($d['href'] ?? ''));
@endphp
@if($title !== '' || $body !== '' || $href !== '')
    <div class="rounded-[1.75rem] border border-emerald-400/25 bg-gradient-to-br from-emerald-400/15 via-zinc-950 to-zinc-950 p-7 sm:p-9">
        @if($title !== '')
            <h3 class="text-xl font-bold tracking-tight text-white">{{ $title }}</h3>
        @endif
        @if($body !== '')
            <div class="mt-4 prose prose-invert prose-base prose-emerald max-w-none text-zinc-200 leading-[1.78]">{!! $body !!}</div>
        @endif
        @if($href !== '')
            <a href="{{ $href }}" class="mt-5 inline-flex items-center rounded-2xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.read_more') }} →</a>
        @endif
    </div>
@endif
