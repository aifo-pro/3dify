@props(['eyebrow' => null, 'title', 'description' => null, 'href' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        @if($eyebrow)
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ $eyebrow }}</p>
        @endif
        <h2 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $title }}</h2>
        @if($description)
            <p class="mt-3 max-w-2xl text-base leading-7 text-zinc-400">{{ $description }}</p>
        @endif
    </div>
    @if($href && $action)
        <x-ui.button :href="$href" variant="secondary">{{ $action }}</x-ui.button>
    @endif
</div>
