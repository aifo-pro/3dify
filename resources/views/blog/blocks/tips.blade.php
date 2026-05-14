@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $icon = trim((string) ($d['icon'] ?? ''));
    $key = $locale === 'en' ? 'items_en' : 'items_uk';
    $items = is_array($d[$key] ?? null) ? $d[$key] : [];
    $items = array_values(array_filter($items, fn ($i) => is_string($i) && trim(strip_tags($i)) !== ''));
@endphp
@if($title !== '' || $items !== [])
    <div class="rounded-3xl border border-emerald-400/25 bg-gradient-to-br from-emerald-400/[0.12] via-emerald-500/[0.05] to-transparent p-6 sm:p-8 shadow-lg shadow-emerald-900/20">
        <div class="flex items-start gap-3">
            @if($icon !== '')
                <span class="text-2xl leading-none" aria-hidden="true">{{ $icon }}</span>
            @else
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-emerald-400/30 bg-emerald-400/15 text-emerald-200" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18"/></svg>
                </span>
            @endif
            <div class="min-w-0 flex-1 space-y-3">
                @if($title !== '')
                    <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
                @endif
                @if($items !== [])
                    <ul class="space-y-2 text-sm leading-relaxed text-emerald-50/90">
                        @foreach($items as $item)
                            <li class="flex gap-2">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-300/90"></span>
                                <span class="prose prose-invert prose-sm prose-emerald max-w-none prose-p:my-0">{!! $item !!}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endif
