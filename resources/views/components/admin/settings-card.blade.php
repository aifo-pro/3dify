@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7']) }}>
    @if($title)
        <header class="mb-6 flex items-start justify-between gap-4 border-b border-white/5 pb-5">
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
                @if($description)
                    <p class="mt-1 text-sm leading-6 text-zinc-400">{{ $description }}</p>
                @endif
            </div>
            @isset($action)
                <div class="shrink-0">{{ $action }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}
</section>
