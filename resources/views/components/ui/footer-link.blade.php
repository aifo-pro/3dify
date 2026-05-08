@props(['href' => '#'])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'inline-flex w-fit text-sm text-zinc-400 transition hover:text-emerald-200 hover:translate-x-0.5']) }}
>
    {{ $slot }}
</a>
