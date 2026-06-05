@props([
    'user' => null,
    'tier' => null,
    'size' => 'sm', // xs | sm | md
    'showLabel' => true,
])

@php
    $tier = $tier ?? ($user?->verificationTier());
    if (! $tier) return;

    // Square (icon-only) sizes when there's no label, pill sizes with text otherwise.
    if ($showLabel) {
        $sizes = [
            'xs' => 'h-4 px-1.5 text-[9px] gap-1 [&_svg]:h-2.5 [&_svg]:w-2.5',
            'sm' => 'h-5 px-2 text-[10px] gap-1 [&_svg]:h-3 [&_svg]:w-3',
            'md' => 'h-6 px-2.5 text-xs gap-1.5 [&_svg]:h-3.5 [&_svg]:w-3.5',
        ];
    } else {
        $sizes = [
            'xs' => 'h-4 w-4 justify-center [&_svg]:h-2.5 [&_svg]:w-2.5',
            'sm' => 'h-5 w-5 justify-center [&_svg]:h-3 [&_svg]:w-3',
            'md' => 'h-6 w-6 justify-center [&_svg]:h-4 [&_svg]:w-4',
        ];
    }
    $palette = $tier === 'verified'
        ? 'border-sky-300/40 bg-sky-300/[0.10] text-sky-100'
        : 'border-amber-300/40 bg-amber-300/[0.08] text-amber-100';
    $label = $tier === 'verified' ? __('Підтверджений автор') : __('Перевірений');
    $title = $tier === 'verified'
        ? __('5+ опублікованих моделей · 30+ днів · рейтинг 4★+')
        : __('Має успішні продажі та активний акаунт 90+ днів');
@endphp

<span title="{{ $title }}" {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border font-bold tracking-wide $palette {$sizes[$size]}"]) }}>
    @if($tier === 'verified')
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1l3.09 2.26L19 3l.74 3.91L23 8.5l-1.85 3.5L23 15.5l-3.26 1.59L19 21l-3.91-.26L12 23l-3.09-2.26L5 21l-.74-3.91L1 15.5l1.85-3.5L1 8.5l3.26-1.59L5 3l3.91.26L12 1zm-1.41 14L17 9.41 15.59 8 10.59 13l-2.59-2.6L6.59 11.81 10.59 15z"/></svg>
    @else
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    @endif
    @if($showLabel)<span>{{ $label }}</span>@endif
</span>
