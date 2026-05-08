@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'inline-flex h-8 items-center rounded-full px-3 text-[13px] font-medium leading-none transition '.($active
            ? 'bg-emerald-300/15 text-emerald-100 shadow-inner shadow-emerald-500/10'
            : 'text-zinc-300 hover:bg-white/[0.06] hover:text-white')
    ]) }}
>
    {{ $slot }}
</a>
