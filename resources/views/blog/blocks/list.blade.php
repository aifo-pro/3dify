@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $style = $d['style'] ?? 'bullets';
    $key = $locale === 'en' ? 'items_en' : 'items_uk';
    $items = is_array($d[$key] ?? null) ? $d[$key] : [];
    $items = array_values(array_filter($items, fn ($i) => is_string($i) && trim($i) !== ''));
@endphp
@if($title !== '' || $items !== [])
    <div class="rounded-3xl border border-white/10 bg-zinc-950/40 p-6 sm:p-8">
        @if($title !== '')
            <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
        @endif
        @if($items !== [])
            <ul @class([
                'mt-4 space-y-2 text-zinc-300',
                'list-decimal pl-5' => $style === 'numbers',
                'list-none pl-0' => $style !== 'numbers',
            ])>
                @foreach($items as $item)
                    @if($style === 'numbers')
                        <li class="leading-relaxed marker:text-emerald-400/90 prose prose-invert prose-sm prose-emerald max-w-none prose-p:my-0 prose-a:text-emerald-300">{!! $item !!}</li>
                    @else
                        <li class="flex gap-3 leading-relaxed">
                            @if($style === 'checks')
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border border-emerald-400/40 bg-emerald-400/15 text-emerald-300" aria-hidden="true">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                </span>
                            @else
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400/80" aria-hidden="true"></span>
                            @endif
                            <span class="min-w-0 flex-1 prose prose-invert prose-sm prose-emerald max-w-none prose-p:my-0 prose-a:text-emerald-300">{!! $item !!}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </div>
@endif
