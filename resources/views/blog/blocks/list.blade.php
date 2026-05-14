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
    $hasTitle = $title !== '';
@endphp
@if($hasTitle || $items !== [])
    @if($hasTitle)
        <div class="space-y-3 rounded-2xl border border-white/5 bg-zinc-900/20 p-4 sm:p-5">
            <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
            @if($items !== [])
                <ul @class([
                    'space-y-2 text-zinc-300',
                    'list-decimal pl-5 marker:text-zinc-500' => $style === 'numbers',
                    'list-none pl-0' => $style !== 'numbers',
                ])>
                    @foreach($items as $item)
                        @if($style === 'numbers')
                            <li class="leading-relaxed marker:font-medium prose prose-invert prose-sm max-w-none prose-p:my-0 prose-a:text-emerald-400/90">{!! $item !!}</li>
                        @else
                            <li class="flex gap-3 leading-relaxed">
                                @if($style === 'checks')
                                    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border border-emerald-500/35 bg-emerald-500/10 text-emerald-400/90" aria-hidden="true">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                    </span>
                                @else
                                    <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-zinc-500" aria-hidden="true"></span>
                                @endif
                                <span class="min-w-0 flex-1 prose prose-invert prose-sm max-w-none prose-p:my-0 prose-a:text-emerald-400/90">{!! $item !!}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    @elseif($items !== [])
        <ul @class([
            'my-1 space-y-2 text-zinc-300',
            'list-decimal pl-5 marker:text-zinc-500' => $style === 'numbers',
            'list-none pl-0' => $style !== 'numbers',
        ])>
            @foreach($items as $item)
                @if($style === 'numbers')
                    <li class="leading-relaxed marker:font-medium prose prose-invert prose-sm max-w-none prose-p:my-0 prose-a:text-emerald-400/90">{!! $item !!}</li>
                @else
                    <li class="flex gap-3 leading-relaxed">
                        @if($style === 'checks')
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border border-emerald-500/35 bg-emerald-500/10 text-emerald-400/90" aria-hidden="true">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>
                        @else
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-zinc-500" aria-hidden="true"></span>
                        @endif
                        <span class="min-w-0 flex-1 prose prose-invert prose-sm max-w-none prose-p:my-0 prose-a:text-emerald-400/90">{!! $item !!}</span>
                    </li>
                @endif
            @endforeach
        </ul>
    @endif
@endif
