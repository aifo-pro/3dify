<x-layouts.marketplace
    :seo-title="__('blog.meta.index_title') . ' · 3Dify'"
    :seo-description="__('blog.meta.index_description')"
    :seo-canonical="route('blog.index')"
>
    {{-- ═══ HERO ═══ --}}
    <div class="relative border-b border-white/[0.06] bg-zinc-950 py-14 sm:py-20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(52,211,153,.1),transparent)]"></div>
        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if(session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-300/25 bg-emerald-300/[0.08] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
            @endif
            @if(! empty($blogAwaitingMigration))
                <div class="mb-6 rounded-2xl border border-amber-300/25 bg-amber-400/[0.07] px-4 py-3 text-sm font-semibold text-amber-100">{{ __('blog.awaiting_migration_banner') }}</div>
            @endif

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] lg:items-center lg:gap-12">
                <div>
                    <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/[0.08] px-3 py-1 text-[11px] font-black uppercase tracking-widest text-emerald-300">{{ __('blog.hero.badge') }}</span>
                    <h1 class="mt-5 text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">{{ __('blog.hero.title') }}</h1>
                    <p class="mt-4 max-w-xl text-lg leading-relaxed text-zinc-400">{{ __('blog.hero.subtitle') }}</p>
                </div>

                {{-- Search --}}
                <form method="GET" action="{{ route('blog.index') }}" class="rounded-2xl border border-white/[0.08] bg-white/[0.04] p-1.5">
                    @if(! empty($activeCategorySlug))
                        <input type="hidden" name="category" value="{{ $activeCategorySlug }}">
                    @endif
                    <div class="flex gap-1.5">
                        <input name="q" value="{{ $q }}" placeholder="{{ __('blog.search_placeholder') }}"
                               class="h-11 min-w-0 flex-1 rounded-xl border border-white/[0.08] bg-zinc-950/80 px-4 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-400/40 focus:outline-none focus:ring-1 focus:ring-emerald-400/30">
                        <button type="submit" class="h-11 shrink-0 rounded-xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.search_button') }}</button>
                    </div>
                </form>
            </div>

            {{-- Category filter --}}
            @if(empty($blogAwaitingMigration) && $categories->isNotEmpty())
                <div class="mt-8 flex flex-wrap items-center gap-2">
                    <a href="{{ route('blog.index', array_filter(['q' => $q])) }}"
                       @class(['rounded-full border px-3.5 py-1.5 text-xs font-bold transition', $activeCategorySlug === '' ? 'border-emerald-400/50 bg-emerald-400/15 text-emerald-100' : 'border-white/[0.08] bg-white/[0.03] text-zinc-400 hover:border-emerald-400/30 hover:text-emerald-200'])>{{ __('blog.filter_all') }}</a>
                    @foreach($categories as $category)
                        <a href="{{ route('blog.index', array_filter(['q' => $q, 'category' => $category->slug])) }}"
                           @class(['rounded-full border px-3.5 py-1.5 text-xs font-bold transition', $activeCategorySlug === $category->slug ? 'border-emerald-400/50 bg-emerald-400/15 text-emerald-100' : 'border-white/[0.08] bg-white/[0.03] text-zinc-400 hover:border-emerald-400/30 hover:text-emerald-200'])>{{ $category->localized('name') }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">

        {{-- ═══ FEATURED POST ═══ --}}
        @if($featured)
            <a href="{{ $featured->url }}"
               class="group mb-14 grid overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-900/50 shadow-2xl shadow-black/30 transition hover:border-emerald-400/25 lg:mb-16 lg:grid-cols-2">
                <div class="aspect-[16/9] overflow-hidden bg-zinc-950 lg:aspect-auto lg:min-h-[340px]">
                    @if($featured->cover_url)
                        <img src="{{ $featured->cover_url }}" alt="{{ $featured->localized('cover_alt') ?: $featured->localized_title }}"
                             loading="lazy" width="900" height="540"
                             class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                    @else
                        <div class="grid h-full w-full place-items-center bg-[radial-gradient(ellipse_at_center,rgba(52,211,153,.18),transparent_60%),#09090b] text-5xl font-black text-emerald-400/30">3D</div>
                    @endif
                </div>
                <div class="flex flex-col justify-center p-7 sm:p-9 lg:p-10">
                    <span class="mb-4 inline-flex w-fit items-center rounded-full border border-emerald-400/30 bg-emerald-400/[0.08] px-3 py-1 text-[11px] font-black uppercase tracking-widest text-emerald-300">{{ __('blog.featured') }}</span>
                    <h2 class="text-2xl font-black leading-snug text-white transition group-hover:text-emerald-50 sm:text-3xl">{{ $featured->localized_title }}</h2>
                    @if($featured->localized_excerpt)
                        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-zinc-400">{{ $featured->localized_excerpt }}</p>
                    @endif
                    <div class="mt-6 flex items-center justify-between">
                        <div class="flex items-center gap-4 text-xs text-zinc-500">
                            <time datetime="{{ optional($featured->published_at)->toAtomString() }}">{{ optional($featured->published_at)->translatedFormat('d M Y') }}</time>
                            <span>{{ __('blog.reading_time', ['count' => $featured->readingMinutes()]) }}</span>
                        </div>
                        <span class="inline-flex items-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 transition group-hover:bg-emerald-300">{{ __('blog.read_article') }} →</span>
                    </div>
                </div>
            </a>
        @endif

        {{-- ═══ MAIN GRID + SIDEBAR ═══ --}}
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_23rem] xl:gap-12">

            {{-- Posts --}}
            <div class="min-w-0">
                @if($posts->isEmpty())
                    <x-ui.empty-state :title="__('blog.empty_posts')" :description="__('blog.empty_posts_hint')" />
                @else
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach($posts as $post)
                            @include('marketplace.blog.partials.card', ['post' => $post])
                        @endforeach
                    </div>
                    @if($posts->hasPages())
                        <div class="mt-8">{{ $posts->links() }}</div>
                    @endif
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="flex min-w-0 flex-col gap-5 lg:sticky lg:top-28 lg:self-start">

                {{-- Categories --}}
                @if($categories->isNotEmpty())
                    <div class="rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ __('blog.categories') }}</h3>
                        <nav class="mt-3 flex flex-wrap gap-1.5" aria-label="{{ __('blog.categories') }}">
                            @foreach($categories as $category)
                                <a href="{{ route('blog.category', $category) }}"
                                   class="rounded-full border border-white/[0.08] bg-zinc-950/50 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:border-emerald-400/30 hover:bg-emerald-400/[0.08] hover:text-emerald-200">
                                    {{ $category->localized('name') }}
                                </a>
                            @endforeach
                        </nav>
                    </div>
                @endif

                {{-- Popular tags --}}
                @if($tags->isNotEmpty())
                    <div class="rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ __('blog.popular_tags') }}</h3>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach($tags as $tag)
                                <a href="{{ route('blog.tag', $tag) }}"
                                   class="rounded-full border border-white/[0.06] px-2.5 py-0.5 text-[11px] font-medium text-zinc-500 transition hover:border-emerald-400/25 hover:text-emerald-300">
                                    #{{ $tag->localized() }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Popular posts --}}
                @if($popular->isNotEmpty())
                    <div class="rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5">
                        <h3 class="text-[10px] font-black uppercase tracking-widest text-zinc-500">{{ __('blog.popular_posts') }}</h3>
                        <ul class="mt-2 -mx-2 space-y-0.5">
                            @foreach($popular as $i => $pop)
                                <li>
                                    <a href="{{ $pop->url }}" class="group flex items-start gap-3 rounded-xl px-2 py-2.5 transition hover:bg-white/[0.04]">
                                        <span class="mt-0.5 w-4 shrink-0 text-center font-mono text-xs text-zinc-600">{{ $i + 1 }}</span>
                                        <div class="min-w-0">
                                            <span class="line-clamp-2 text-sm font-semibold leading-snug text-zinc-300 group-hover:text-emerald-100">{{ $pop->localized_title }}</span>
                                            <span class="mt-0.5 block text-[11px] text-zinc-600">{{ number_format($pop->views) }} {{ __('blog.views') }}</span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Subscribe --}}
                <div class="hidden lg:block">
                    @include('marketplace.blog.partials.subscribe', ['compact' => true])
                </div>

            </aside>
        </div>

        {{-- Mobile subscribe --}}
        <div class="mt-10 lg:hidden">
            @include('marketplace.blog.partials.subscribe')
        </div>
    </div>
</x-layouts.marketplace>
