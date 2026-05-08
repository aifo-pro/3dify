@props(['title', 'description' => null, 'href' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'rounded-3xl border border-dashed border-white/15 bg-white/[0.04] p-10 text-center']) }}>
    <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-300/15 text-2xl text-emerald-200">⌁</div>
    <h3 class="mt-5 text-xl font-semibold text-white">{{ $title }}</h3>
    @if($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-400">{{ $description }}</p>
    @endif
    @if($href && $action)
        <x-ui.button :href="$href" class="mt-6">{{ $action }}</x-ui.button>
    @endif
</div>
