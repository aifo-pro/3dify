@props(['ad'])

<div class="group relative overflow-hidden rounded-3xl border border-amber-400/20 bg-zinc-900/60 shadow-xl shadow-black/20 transition duration-300 hover:-translate-y-1 hover:border-amber-400/40"
     x-data
     x-init="fetch('{{ route('ads.impression', $ad) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}})">

    {{-- Ad badge --}}
    <div class="absolute left-3 top-3 z-10">
        <span class="inline-flex items-center rounded-full border border-amber-400/40 bg-zinc-950/80 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-300 backdrop-blur-sm">
            {{ $ad->badge_label }}
        </span>
    </div>

    <a href="{{ route('ads.click', $ad) }}" target="_blank" rel="noopener sponsored" class="block">
        {{-- Image --}}
        <div class="relative aspect-[4/3] overflow-hidden bg-zinc-800">
            @if($ad->imageUrl())
                <img src="{{ $ad->imageUrl() }}"
                     alt="{{ $ad->localized('title') }}"
                     width="400" height="300"
                     loading="lazy"
                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center bg-gradient-to-br from-amber-400/10 to-zinc-900 text-3xl font-black text-amber-400/30">AD</div>
            @endif
        </div>

        {{-- Content --}}
        <div class="p-5">
            <h3 class="line-clamp-2 text-lg font-semibold leading-snug text-white group-hover:text-amber-100">
                {{ $ad->localized('title') }}
            </h3>
            @if($ad->localized('description'))
                <p class="mt-2 line-clamp-2 text-sm leading-5 text-zinc-400">{{ $ad->localized('description') }}</p>
            @endif
            <div class="mt-4 flex items-center justify-between border-t border-white/[0.06] pt-4">
                <span class="text-xs text-zinc-500">{{ parse_url($ad->target_url, PHP_URL_HOST) }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/15 px-3 py-1 text-xs font-bold text-amber-300">
                    Дізнатися більше
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
            </div>
        </div>
    </a>
</div>
