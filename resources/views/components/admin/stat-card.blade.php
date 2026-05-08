@props([
    'label' => '',
    'value' => 0,
    'delta' => null,
    'trend' => 'flat',
    'sparkline' => [],
    'color' => 'emerald',
    'icon' => null,
    'href' => null,
    'suffix' => null,
    'helper' => null,
])

@php
    $colors = [
        'emerald' => ['icon' => 'bg-emerald-300/15 text-emerald-100', 'tint' => 'from-emerald-400/15 to-emerald-400/0'],
        'sky' => ['icon' => 'bg-sky-300/15 text-sky-100', 'tint' => 'from-sky-400/15 to-sky-400/0'],
        'amber' => ['icon' => 'bg-amber-300/15 text-amber-100', 'tint' => 'from-amber-400/15 to-amber-400/0'],
        'violet' => ['icon' => 'bg-violet-300/15 text-violet-100', 'tint' => 'from-violet-400/15 to-violet-400/0'],
        'rose' => ['icon' => 'bg-rose-300/15 text-rose-100', 'tint' => 'from-rose-400/15 to-rose-400/0'],
    ];
    $palette = $colors[$color] ?? $colors['emerald'];

    $deltaClass = match ($trend) {
        'up' => 'text-emerald-200 bg-emerald-300/10 border-emerald-300/20',
        'down' => 'text-rose-200 bg-rose-300/10 border-rose-300/20',
        default => 'text-zinc-400 bg-white/[0.04] border-white/10',
    };

    $deltaSign = $delta === null ? '' : ($delta > 0 ? '+' : '');
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/20 transition '.($href ? 'hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/[0.07]' : '')]) }}
>
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br {{ $palette['tint'] }} opacity-70"></div>

    <div class="relative flex items-start justify-between gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl {{ $palette['icon'] }}">
            @if($icon)
                {!! $icon !!}
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/></svg>
            @endif
        </span>
        @if($delta !== null)
            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $deltaClass }}">
                @if($trend === 'up')
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 14 12 8 18 14"/></svg>
                @elseif($trend === 'down')
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 10 12 16 18 10"/></svg>
                @else
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                @endif
                {{ $deltaSign }}{{ $delta }}%
            </span>
        @endif
    </div>

    <p class="relative mt-4 text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $label }}</p>
    <div class="relative mt-1 flex items-end justify-between gap-3">
        <strong class="block text-3xl font-black text-white">
            {{ $value }}@if($suffix)<span class="text-base font-bold text-zinc-400 ml-0.5">{{ $suffix }}</span>@endif
        </strong>
        @if(! empty($sparkline))
            <x-admin.sparkline :data="$sparkline" :color="$color" class="h-7 w-24 shrink-0" :width="96" :height="28" />
        @endif
    </div>
    @if($helper)
        <p class="relative mt-2 text-xs text-zinc-500">{{ $helper }}</p>
    @endif
</{{ $tag }}>
