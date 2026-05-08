@props([
    'current' => '30d',
    'options' => [
        '7d' => 'Тиждень',
        '30d' => '30 днів',
        '90d' => '90 днів',
    ],
])

<div {{ $attributes->merge(['class' => 'inline-flex h-9 items-center gap-0.5 rounded-full border border-white/10 bg-white/[0.04] p-1']) }}>
    @foreach($options as $key => $label)
        <a
            href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
            class="inline-flex h-7 items-center rounded-full px-3 text-xs font-semibold transition
                {{ $current === $key
                    ? 'bg-emerald-300/15 text-emerald-100 shadow-inner shadow-emerald-500/10'
                    : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white' }}"
        >{{ $label }}</a>
    @endforeach
</div>
