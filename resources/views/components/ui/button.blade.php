@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-semibold transition duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-300/70 focus:ring-offset-2 focus:ring-offset-zinc-950';
    $variants = [
        'primary' => 'bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/20 hover:-translate-y-0.5 hover:bg-emerald-300',
        'secondary' => 'border border-white/15 bg-white/[0.08] text-white hover:-translate-y-0.5 hover:border-white/25 hover:bg-white/[0.12]',
        'ghost' => 'text-zinc-300 hover:bg-white/10 hover:text-white',
        'danger' => 'border border-red-400/40 bg-red-500/10 text-red-100 hover:bg-red-500/20',
    ];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' '.$variants[$variant]]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.$variants[$variant]]) }}>{{ $slot }}</button>
@endif
