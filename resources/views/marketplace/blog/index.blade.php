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

        <header class="grid gap-8 lg:grid-cols-[1fr_360px] lg:items-end">
            <div>
                <x-ui.badge>{{ __('blog.hero.badge') }}</x-ui.badge>
                <h1 class="mt-5 max-w-4xl text-5xl font-black tracking-tight text-white sm:text-6xl">{{ __('blog.hero.title') }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-400">{{ __('blog.hero.subtitle') }}</p>
            </div>
            <form method="GET" action="{{ route('blog.index') }}" class="rounded-3xl border border-white/10 bg-white/[0.05] p-2">
                <div class="flex gap-2">
                    <input name="q" value="{{ $q }}" placeholder="{{ __('blog.search_placeholder') }}" class="h-12 min-w-0 flex-1 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white placeholder:text-zinc-500">
                    <button class="rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950">{{ __('blog.search_button') }}</button>
                </div>
            </form>
        </header>

        @if($featured)
            <a href="{{ $featured->url }}" class="mt-10 grid overflow-hidden rounded-3xl border border-emerald-300/20 bg-white/[0.05] shadow-2xl shadow-black/30 transition hover:border-emerald-300/40 lg:grid-cols-[1.1fr_.9fr]">
                <div class="min-h-[280px] bg-zinc-950">
                    @if($featured->cover_url)<img src="{{ $featured->cover_url }}" alt="{{ $featured->localized('cover_alt') ?: $featured->localized_title }}" loading="lazy" width="800" height="500" class="h-full w-full object-cover">@endif
                </div>
                <div class="p-8">
                    <x-ui.badge>{{ __('blog.featured') }}</x-ui.badge>
                    <h2 class="mt-5 text-3xl font-black text-white">{{ $featured->localized_title }}</h2>
                    <p class="mt-4 leading-7 text-zinc-400">{{ $featured->localized_excerpt }}</p>
                    <span class="mt-8 inline-flex h-12 items-center rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950">{{ __('blog.read_article') }} →</span>
                </div>
            </a>
        @endif

        <div class="mt-12 grid gap-8 lg:grid-cols-[1fr_300px]">
            <div>
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($posts as $post)
                        @include('marketplace.blog.partials.card', ['post' => $post])
                    @empty
                        <x-ui.empty-state :title="__('blog.empty_posts')" :description="__('blog.empty_posts_hint')" />
                    @endforelse
                </div>
                <div class="mt-8">{{ $posts->links() }}</div>
            </div>
            <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h3 class="font-black text-white">{{ __('blog.categories') }}</h3>
                    <div class="mt-4 grid gap-2">
                        @foreach($categories as $category)
                            <a href="{{ route('blog.category', $category) }}" class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-bold text-zinc-200 hover:border-emerald-300/30 hover:text-emerald-100">{{ $category->localized('name') }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h3 class="font-black text-white">{{ __('blog.popular_tags') }}</h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <a href="{{ route('blog.tag', $tag) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs font-bold text-zinc-300 hover:border-emerald-300/30 hover:text-emerald-100">#{{ $tag->localized() }}</a>
                        @endforeach
                    </div>
                </div>
                @if($popular->isNotEmpty())
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                        <h3 class="font-black text-white">{{ __('blog.popular_posts') }}</h3>
                        <ul class="mt-4 grid gap-3">
                            @foreach($popular as $pop)
                                <li>
                                    <a href="{{ $pop->url }}" class="group block rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 transition hover:border-emerald-300/30">
                                        <span class="line-clamp-2 text-sm font-bold text-zinc-200 group-hover:text-emerald-100">{{ $pop->localized_title }}</span>
                                        <span class="mt-1 block text-xs text-zinc-500">{{ number_format($pop->views) }} {{ __('blog.views') }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>

        <div class="mt-12">
            @include('marketplace.blog.partials.subscribe')
        </div>
    </section>
</x-layouts.marketplace>
