@props([
    'title' => null,
    'description' => null,
    'href' => null,
    'action' => null,
    'padded' => true,
])

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20']) }}>
    @if($title || $description || isset($actions))
        <header class="flex flex-col gap-3 border-b border-white/5 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                @if($title)
                    <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $title }}</h3>
                @endif
                @if($description)
                    <p class="mt-0.5 text-xs text-zinc-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @elseif($href && $action)
                <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-emerald-200 transition hover:text-emerald-100">
                    {{ $action }}
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            @endif
        </header>
    @endif

    <div @class(['p-5' => $padded, '' => ! $padded])>
        {{ $slot }}
    </div>
</section>
