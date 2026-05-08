@props(['variant' => 'neutral'])

@php
    $variants = [
        'free' => 'border-emerald-300/30 bg-emerald-300/15 text-emerald-100',
        'paid' => 'border-sky-300/30 bg-sky-300/15 text-sky-100',
        'neutral' => 'border-white/10 bg-white/10 text-zinc-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold '.$variants[$variant]]) }}>{{ $slot }}</span>
