@props([
    'label' => '',
    'value' => 0,
    'helper' => null,
    'tone' => 'zinc',
])
@php
    $tones = [
        'emerald' => 'border-emerald-300/30 bg-emerald-300/[0.06] text-emerald-100',
        'amber' => 'border-amber-300/30 bg-amber-300/[0.06] text-amber-100',
        'rose' => 'border-rose-300/30 bg-rose-300/[0.06] text-rose-100',
        'sky' => 'border-sky-300/30 bg-sky-300/[0.06] text-sky-100',
        'zinc' => 'border-white/10 bg-white/[0.04] text-zinc-300',
    ];
    $cls = $tones[$tone] ?? $tones['zinc'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border p-4 ' . $cls]) }}>
    <p class="text-[11px] font-bold uppercase tracking-[0.14em] opacity-80">{{ $label }}</p>
    <p class="mt-1 text-2xl font-black text-white truncate">{{ $value }}</p>
    @if($helper)<p class="mt-1 text-[11px] opacity-70">{{ $helper }}</p>@endif
</div>
