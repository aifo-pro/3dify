@props(['post'])

<article class="group overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20 transition duration-300 hover:-translate-y-1 hover:border-emerald-300/30 hover:bg-white/[0.07]">
    <a href="{{ $post->url }}" class="block">
        <div class="aspect-[16/10] overflow-hidden bg-zinc-950">
            @if($post->cover_url)
                <img src="{{ $post->cover_url }}" alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}" loading="lazy" width="800" height="500" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full w-full place-items-center bg-[radial-gradient(circle_at_center,rgba(52,211,153,.18),transparent_55%),#09090b] text-4xl font-black text-emerald-200">3D</div>
            @endif
        </div>
        <div class="p-6">
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach($post->categories->take(2) as $category)
                    <span class="rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-3 py-1 text-xs font-bold text-emerald-100">{{ $category->localized('name') }}</span>
                @endforeach
            </div>
            <h2 class="text-xl font-black leading-tight text-white">{{ $post->localized_title }}</h2>
            <p class="mt-3 line-clamp-3 text-sm leading-6 text-zinc-400">{{ $post->localized_excerpt }}</p>
            <div class="mt-5 flex items-center justify-between text-xs font-semibold text-zinc-500">
                <time datetime="{{ optional($post->published_at)->toAtomString() }}">{{ optional($post->published_at)->translatedFormat('d M Y') }}</time>
                <span class="text-emerald-300">{{ __('blog.read_more') }} →</span>
            </div>
        </div>
    </a>
</article>
