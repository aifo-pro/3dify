@props(['eyebrow' => null, 'title', 'description' => null, 'href' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if($eyebrow)
            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-400">{{ $eyebrow }}</p>
        @endif
        <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $title }}</h2>
        @if($description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400 sm:text-base">{{ $description }}</p>
        @endif
    </div>
    @if($href && $action)
        <a href="{{ $href }}" class="shrink-0 text-sm font-semibold text-zinc-400 transition hover:text-emerald-300">
            {{ $action }} →
        </a>
    @endif
</div>
