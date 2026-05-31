<div class="hidden rounded-2xl border border-white/10 bg-zinc-900/40 p-4 lg:block">
    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-zinc-600">{{ __('blog.share') }}</p>
    <div class="space-y-2">
        <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonicalUrl) }}&text={{ urlencode($post->localized_title) }}"
           target="_blank" rel="noopener"
           class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-zinc-300 transition hover:bg-white/10 hover:text-white">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63ZM17.2 19.77h1.833L6.886 4.126H4.92L17.2 19.77Z"/></svg>
            X (Twitter)
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}"
           target="_blank" rel="noopener"
           class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-zinc-300 transition hover:bg-blue-400/10 hover:text-blue-200">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
            Facebook
        </a>
        <button type="button"
            x-data="{c:false}"
            @click="navigator.clipboard.writeText('{{ $canonicalUrl }}').then(()=>{c=true;setTimeout(()=>c=false,2000)})"
            class="flex w-full items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-zinc-300 transition hover:bg-emerald-400/10 hover:text-emerald-200">
            <svg x-show="!c" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <svg x-show="c" class="h-4 w-4 shrink-0 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span x-text="c ? '✓ {{ __('blog.copied') }}' : '{{ __('blog.copy_link') }}'">{{ __('blog.copy_link') }}</span>
        </button>
    </div>
</div>
