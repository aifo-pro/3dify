@props(['post'])

<article class="group flex flex-col overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-900/50 shadow-lg shadow-black/20 transition duration-200 hover:border-emerald-400/25 hover:bg-zinc-900/80">
    <a href="{{ $post->url }}" class="flex flex-col flex-1">
        {{-- Thumbnail --}}
        <div class="aspect-[16/9] overflow-hidden bg-zinc-950">
            @if($post->cover_url)
                <img
                    src="{{ $post->cover_url }}"
                    alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}"
                    loading="lazy" width="800" height="450"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                >
            @else
                <div class="grid h-full w-full place-items-center bg-[radial-gradient(ellipse_at_center,rgba(52,211,153,.15),transparent_60%),#09090b] text-3xl font-black text-emerald-400/40">3D</div>
            @endif
        </div>

        {{-- Body --}}
        <div class="flex flex-1 flex-col p-5 sm:p-6">
            {{-- Categories --}}
            @if($post->categories->isNotEmpty())
                <div class="mb-3 flex flex-wrap gap-1.5">
                    @foreach($post->categories->take(2) as $cat)
                        <span class="inline-block rounded-full border border-emerald-400/25 bg-emerald-400/[0.08] px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-300">{{ $cat->localized('name') }}</span>
                    @endforeach
                </div>
            @endif

            {{-- Title --}}
            <h2 class="text-base font-black leading-snug text-white transition group-hover:text-emerald-50 sm:text-lg">{{ $post->localized_title }}</h2>

            {{-- Excerpt --}}
            @if($post->localized_excerpt)
                <p class="mt-2.5 line-clamp-2 flex-1 text-sm leading-relaxed text-zinc-400">{{ $post->localized_excerpt }}</p>
            @endif

            {{-- Footer --}}
            <div class="mt-4 flex items-center justify-between border-t border-white/[0.06] pt-4 text-xs text-zinc-500">
                <time datetime="{{ optional($post->published_at)->toAtomString() }}">
                    {{ optional($post->published_at)->translatedFormat('d M Y') }}
                </time>
                <span class="font-medium text-emerald-400/80">{{ __('blog.reading_time', ['count' => $post->readingMinutes()]) }}</span>
            </div>
        </div>
    </a>
</article>
