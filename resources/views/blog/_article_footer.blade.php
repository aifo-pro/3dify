{{-- Tags + mobile share --}}
@if($post->tags->isNotEmpty())
    <div class="border-t border-white/10 pt-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-widest text-zinc-600">{{ __('blog.tags_label') }}</span>
            @foreach($post->tags as $tag)
                <a href="{{ route('blog.tag', $tag) }}"
                   class="rounded-full border border-white/10 bg-white/5 px-3 py-0.5 text-xs font-medium text-zinc-400 transition hover:border-emerald-400/30 hover:text-emerald-300">
                    #{{ $tag->localized() }}
                </a>
            @endforeach
        </div>
    </div>
@endif

<div class="border-t border-white/10 pt-6 lg:hidden">
    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-zinc-600">{{ __('blog.share') }}</p>
    <div class="flex flex-wrap gap-2">
        <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonicalUrl) }}&text={{ urlencode($post->localized_title) }}"
           target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-white/10 hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63ZM17.2 19.77h1.833L6.886 4.126H4.92L17.2 19.77Z"/></svg>
            X (Twitter)
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}"
           target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-blue-400/10 hover:text-blue-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
            Facebook
        </a>
    </div>
</div>
