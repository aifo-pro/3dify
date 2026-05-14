<x-layouts.marketplace
    :seo-title="__('blog.meta.index_title') . ' · 3Dify'"
    :seo-description="__('blog.meta.index_description')"
    :seo-canonical="route('blog.index')"
>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/25 bg-emerald-300/[0.10] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
        @endif
        @if(! empty($blogAwaitingMigration))
            <div class="mb-6 rounded-2xl border border-amber-300/30 bg-amber-400/[0.08] px-4 py-3 text-sm font-semibold text-amber-100">{{ __('blog.awaiting_migration_banner') }}</div>
        @endif

        <header class="grid gap-8 pb-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:items-end lg:gap-10">
            <div class="min-w-0">
                <x-ui.badge>{{ __('blog.hero.badge') }}</x-ui.badge>
                <h1 class="mt-5 max-w-4xl text-5xl font-black tracking-tight text-white sm:text-6xl">{{ __('blog.hero.title') }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-400">{{ __('blog.hero.subtitle') }}</p>
            </div>
            <form method="GET" action="{{ route('blog.index') }}" class="min-w-0 rounded-3xl border border-white/10 bg-white/[0.05] p-2 lg:self-stretch">
                <div class="flex gap-2">
                    <input name="q" value="{{ $q }}" placeholder="{{ __('blog.search_placeholder') }}" class="h-12 min-w-0 flex-1 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white placeholder:text-zinc-500">
                    <button type="submit" class="shrink-0 rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950">{{ __('blog.search_button') }}</button>
                </div>
            </form>
        </header>

        @if($featured)
            <a href="{{ $featured->url }}" class="mt-10 grid overflow-hidden rounded-3xl border border-emerald-300/20 bg-white/[0.05] shadow-2xl shadow-black/30 transition hover:border-emerald-300/40 lg:grid-cols-[1.1fr_.9fr]">
                <div class="min-h-[280px] min-w-0 bg-zinc-950">
                    @if($featured->cover_url)<img src="{{ $featured->cover_url }}" alt="{{ $featured->localized('cover_alt') ?: $featured->localized_title }}" loading="lazy" width="800" height="500" class="h-full w-full object-cover">@endif
                </div>
                <div class="min-w-0 p-8">
                    <x-ui.badge>{{ __('blog.featured') }}</x-ui.badge>
                    <h2 class="mt-5 text-3xl font-black text-white">{{ $featured->localized_title }}</h2>
                    <p class="mt-4 leading-7 text-zinc-400">{{ $featured->localized_excerpt }}</p>
                    <span class="mt-8 inline-flex h-12 items-center rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950">{{ __('blog.read_article') }} →</span>
                </div>
            </a>
        @endif

        {{-- Main + sidebar: without featured, extra air + hairline under hero/search; with featured, standard gap under card --}}
        <div @class([
            'grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_22rem] xl:gap-10',
            'mt-14 lg:mt-16' => $featured,
            'mt-14 border-t border-white/10 pt-12 sm:mt-16 sm:pt-14 lg:mt-20 lg:pt-16' => ! $featured,
        ])>
            <div class="min-w-0">
                @if($posts->isEmpty())
                    <x-ui.empty-state :title="__('blog.empty_posts')" :description="__('blog.empty_posts_hint')" />
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach($posts as $post)
                            @include('marketplace.blog.partials.card', ['post' => $post])
                        @endforeach
                    </div>
                @endif
                @if($posts->hasPages())
                    <div class="mt-8">{{ $posts->links() }}</div>
                @endif
            </div>

            <aside class="flex min-w-0 flex-col gap-8 lg:sticky lg:top-28 lg:self-start">
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 shadow-lg shadow-black/20 ring-1 ring-white/[0.04]">
                    <h3 class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('blog.categories') }}</h3>
                    @if($categories->isEmpty())
                        <p class="mt-3 text-xs leading-relaxed text-zinc-500">{{ __('blog.empty_categories') }}</p>
                    @else
                        <nav class="mt-2 max-h-44 space-y-0.5 overflow-y-auto overscroll-contain pr-0.5 [scrollbar-width:thin]" aria-label="{{ __('blog.categories') }}">
                            @foreach($categories as $category)
                                <a href="{{ route('blog.category', $category) }}" class="block truncate rounded-lg px-2 py-1.5 text-[13px] font-semibold leading-snug text-zinc-300 transition hover:bg-white/[0.07] hover:text-emerald-100">{{ $category->localized('name') }}</a>
                            @endforeach
                        </nav>
                    @endif
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 shadow-lg shadow-black/20 ring-1 ring-white/[0.04]">
                    <h3 class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('blog.popular_tags') }}</h3>
                    @if($tags->isEmpty())
                        <p class="mt-3 text-xs leading-relaxed text-zinc-500">{{ __('blog.empty_tags') }}</p>
                    @else
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach($tags as $tag)
                                <a href="{{ route('blog.tag', $tag) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-[11px] font-bold text-zinc-400 hover:border-emerald-300/30 hover:text-emerald-100">#{{ $tag->localized() }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($popular->isNotEmpty())
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 shadow-lg shadow-black/20 ring-1 ring-white/[0.04]">
                        <h3 class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('blog.popular_posts') }}</h3>
                        <ul class="mt-2 space-y-1">
                            @foreach($popular as $pop)
                                <li>
                                    <a href="{{ $pop->url }}" class="group block rounded-lg px-2 py-2 transition hover:bg-white/[0.05]">
                                        <span class="line-clamp-2 text-[13px] font-semibold leading-snug text-zinc-300 group-hover:text-emerald-100">{{ $pop->localized_title }}</span>
                                        <span class="mt-0.5 block text-[11px] text-zinc-600 group-hover:text-zinc-500">{{ number_format($pop->views) }} {{ __('blog.views') }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="hidden lg:block lg:pt-1">
                    @include('marketplace.blog.partials.subscribe', ['compact' => true])
                </div>
            </aside>
        </div>

        <div class="mt-10 lg:hidden">
            @include('marketplace.blog.partials.subscribe')
        </div>
    </section>
</x-layouts.marketplace>
