@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $text = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
@endphp
<div class="overflow-hidden rounded-[1.75rem] border border-emerald-400/25 bg-gradient-to-br from-emerald-400/[0.12] via-zinc-900/60 to-zinc-950 px-7 py-7 shadow-xl shadow-emerald-900/20 sm:px-8 sm:py-8">
    @if($title !== '')
        <h3 class="text-xl font-bold tracking-tight text-white">{{ $title }}</h3>
    @else
        <h3 class="text-xl font-bold tracking-tight text-white">{{ __('blog.subscribe_title') }}</h3>
    @endif
    @if($text !== '')
        <p class="mt-2 text-sm leading-relaxed text-zinc-300">{{ $text }}</p>
    @else
        <p class="mt-2 text-sm leading-relaxed text-zinc-300">{{ __('blog.subscribe_hint') }}</p>
    @endif
    @include('marketplace.blog.partials.subscribe', ['inlineForm' => true, 'hideLabel' => true])
</div>
