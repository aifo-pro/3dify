@php
    $title = $post->localized('seo_title') ?: $post->localized_title;
    $description = $post->localized('seo_description') ?: $post->localized_excerpt;
    $image = $post->og_image_url ?: $post->cover_url;
    $canonicalUrl = $post->url;
    $siteUrl = rtrim((string) config('app.url'), '/');
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->localized_title,
        'description' => $description,
        'image' => $image ? [$image] : [],
        'datePublished' => optional($post->published_at)->toAtomString(),
        'dateModified' => optional($post->updated_at)->toAtomString(),
        'author' => ['@type' => 'Person', 'name' => $post->author?->displayName() ?: '3Dify'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => '3Dify',
            'url' => $siteUrl,
        ],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
    ];
    $primaryCategory = $post->categories->first();
    $readPlain = trim(strip_tags($post->localized_content ?? ''));
    preg_match_all('/\S+/u', $readPlain, $readMatches);
    $wordCount = count($readMatches[0] ?? []);
    $readMinutes = max(1, (int) ceil($wordCount / 200));
    $authorName = $post->author?->displayName() ?: '3Dify';
    $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1));
    $hasToc = is_array($toc) && count($toc) > 0;
@endphp

<x-layouts.marketplace
    :seo-title="$title . ' · 3Dify'"
    :seo-description="$description"
    :seo-image="$image"
    :seo-canonical="$canonicalUrl"
    og-type="article"
>
    @push('head')
        <meta name="robots" content="{{ $post->allow_index ? 'index,follow' : 'noindex,nofollow' }}">
        <meta property="article:published_time" content="{{ optional($post->published_at)->toAtomString() }}">
        <meta property="article:modified_time" content="{{ optional($post->updated_at)->toAtomString() }}">
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
    @endpush

    <article class="relative pb-16 pt-6 sm:pt-10">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-400/25 to-transparent"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-8 rounded-2xl border border-emerald-300/25 bg-emerald-300/[0.10] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="max-w-3xl text-left">
                {{-- Breadcrumbs --}}
                <nav class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500 sm:text-sm" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="transition hover:text-emerald-200">{{ __('blog.breadcrumb_home') }}</a>
                    <span class="text-zinc-600" aria-hidden="true">›</span>
                    <a href="{{ route('blog.index') }}" class="transition hover:text-emerald-200">{{ __('blog.breadcrumb_blog') }}</a>
                    @if($primaryCategory)
                        <span class="text-zinc-600" aria-hidden="true">›</span>
                        <a href="{{ route('blog.category', $primaryCategory) }}" class="max-w-[10rem] truncate transition hover:text-emerald-200 sm:max-w-xs">{{ $primaryCategory->localized('name') }}</a>
                    @endif
                    <span class="text-zinc-600" aria-hidden="true">›</span>
                    <span class="line-clamp-2 font-medium text-zinc-300 sm:line-clamp-none">{{ $post->localized_title }}</span>
                </nav>

                {{-- Title block --}}
                <header class="mt-7 sm:mt-8">
                    <div class="-mx-1 flex max-w-full flex-nowrap gap-1.5 overflow-x-auto pb-1 [scrollbar-width:thin]">
                        @forelse($post->categories as $category)
                            <a href="{{ route('blog.category', $category) }}" class="inline-flex shrink-0 items-center rounded-full border border-emerald-400/30 bg-emerald-400/[0.08] px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-200/95 transition hover:border-emerald-300/45 hover:bg-emerald-400/12">{{ $category->localized('name') }}</a>
                        @empty
                            <span class="inline-flex shrink-0 items-center rounded-full border border-white/10 bg-white/[0.05] px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-500">Blog</span>
                        @endforelse
                    </div>
                    <h1 class="mt-4 text-2xl font-bold leading-snug tracking-tight text-white sm:text-3xl sm:leading-tight lg:text-[2rem] lg:leading-tight">{{ $post->localized_title }}</h1>
                    @if($post->localized_excerpt)
                        <p class="mt-3 text-sm leading-relaxed text-zinc-400 sm:text-base sm:leading-relaxed">{{ $post->localized_excerpt }}</p>
                    @endif
                </header>

                {{-- Meta + share --}}
                <div class="mt-7 flex flex-col gap-5 border-y border-white/10 py-6 sm:mt-8 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:py-7">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-3 text-sm text-zinc-400">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-emerald-400/35 bg-emerald-400/12 text-xs font-bold text-emerald-100" aria-hidden="true">{{ $authorInitial }}</span>
                            <div class="leading-tight">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('blog.author_label') }}</p>
                                <p class="text-sm font-semibold text-white">{{ $authorName }}</p>
                            </div>
                        </div>
                        <div class="hidden h-7 w-px bg-white/10 sm:block" aria-hidden="true"></div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <time class="text-sm font-medium text-zinc-300" datetime="{{ optional($post->published_at)->toAtomString() }}">{{ optional($post->published_at)->translatedFormat('d M Y') }}</time>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span class="text-sm font-medium text-zinc-300">{{ number_format($post->views) }} {{ __('blog.views') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span class="text-sm font-medium text-zinc-300">{{ __('blog.reading_time', ['count' => $readMinutes]) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5 sm:shrink-0">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('blog.share') }}</span>
                        <div class="flex items-center gap-1.5">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url) }}" target="_blank" rel="noopener noreferrer" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.05] text-zinc-300 transition hover:border-emerald-400/30 hover:bg-emerald-400/10 hover:text-emerald-100" title="{{ __('blog.share_facebook') }}" aria-label="{{ __('blog.share_facebook') }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="https://t.me/share/url?url={{ urlencode($post->url) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener noreferrer" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.05] text-zinc-300 transition hover:border-emerald-400/30 hover:bg-emerald-400/10 hover:text-emerald-100" title="{{ __('blog.share_telegram') }}" aria-label="{{ __('blog.share_telegram') }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                            </a>
                            <a href="https://x.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener noreferrer" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.05] text-zinc-300 transition hover:border-emerald-400/30 hover:bg-emerald-400/10 hover:text-emerald-100" title="{{ __('blog.share_x') }}" aria-label="{{ __('blog.share_x') }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if($post->cover_url)
                <div class="mx-auto mt-7 max-w-3xl sm:mt-8">
                    <div class="relative h-[clamp(11rem,32vw,20rem)] overflow-hidden rounded-2xl border border-white/10 bg-zinc-950 shadow-lg shadow-black/30 sm:h-[clamp(12rem,28vw,22rem)]">
                        <img src="{{ $post->cover_url }}" alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}" width="1200" height="630" loading="eager" fetchpriority="high" class="absolute inset-0 h-full w-full object-cover">
                    </div>
                </div>
            @endif

            @if($hasToc)
                <nav class="mx-auto mt-6 max-w-3xl rounded-xl border border-white/10 bg-zinc-950/40 px-3 py-3.5 lg:hidden" aria-label="{{ __('blog.toc') }}">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-400">{{ __('blog.toc') }}</p>
                    <ol class="mt-2 flex list-none flex-col gap-0.5 p-0 text-[13px] leading-snug text-zinc-400">
                        @foreach($toc as $i => $item)
                            <li>
                                <a href="#{{ $item['id'] }}" class="flex gap-2 rounded-md py-1 transition hover:bg-white/[0.04] hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-5' : 'pl-1' }}">
                                    <span class="w-5 shrink-0 font-mono text-[11px] text-emerald-500/85">{{ $i + 1 }}.</span>
                                    <span>{{ $item['text'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            <div @class([
                'mt-8 grid gap-8 lg:mt-10 lg:items-start lg:gap-10',
                'lg:grid-cols-[12.5rem_minmax(0,1fr)_16.5rem]' => $hasToc,
                'lg:grid-cols-[minmax(0,1fr)_16.5rem]' => ! $hasToc,
            ])>
                @if($hasToc)
                    <aside class="hidden lg:block">
                        <nav class="sticky top-28 rounded-xl border border-white/10 bg-zinc-950/50 px-3 py-3 shadow-md shadow-black/20" aria-label="{{ __('blog.toc') }}">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-400">{{ __('blog.toc') }}</p>
                            <ol class="mt-2 flex list-none flex-col gap-0 p-0 text-[12px] leading-snug text-zinc-400">
                                @foreach($toc as $i => $item)
                                    <li>
                                        <a href="#{{ $item['id'] }}" class="flex gap-1.5 rounded-md border-l-2 border-transparent py-1 pl-2 transition hover:border-emerald-500/50 hover:bg-white/[0.03] hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                            <span class="w-4 shrink-0 font-mono text-[10px] text-emerald-500/90">{{ $i + 1 }}.</span>
                                            <span>{{ $item['text'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </aside>
                @endif

                <div class="min-w-0">
                    <div class="blog-article-body">
                        <div class="blog-content">
                            {!! $contentHtml !!}
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-2 border-t border-white/10 pt-6">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('blog.tag', $tag) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/35 hover:bg-emerald-400/10 hover:text-emerald-100">#{{ $tag->localized() }}</a>
                        @endforeach
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('blog.share') }}</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url) }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/30 hover:text-emerald-100">{{ __('blog.share_facebook') }}</a>
                        <a href="https://t.me/share/url?url={{ urlencode($post->url) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/30 hover:text-emerald-100">{{ __('blog.share_telegram') }}</a>
                        <a href="https://x.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/30 hover:text-emerald-100">{{ __('blog.share_x') }}</a>
                    </div>

                    <div class="mt-10 lg:hidden">
                        @include('marketplace.blog.partials.subscribe')
                    </div>
                </div>

                <aside class="min-w-0 space-y-6 lg:sticky lg:top-28">
                    <div class="hidden lg:block">
                        @include('marketplace.blog.partials.subscribe', ['compact' => true])
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-lg shadow-black/15">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-300">{{ __('blog.sidebar_models_title') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ __('blog.sidebar_models_desc') }}</p>
                        <a href="{{ route('products.index') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-emerald-400/40 bg-emerald-400/10 py-2.5 text-sm font-bold text-emerald-100 transition hover:border-emerald-300/60 hover:bg-emerald-400/15">{{ __('blog.sidebar_models_button') }}</a>
                    </div>
                </aside>
            </div>
        </div>

        @if($related->isNotEmpty())
            <div class="mx-auto mt-16 max-w-7xl border-t border-white/10 px-4 pt-14 sm:px-6 lg:px-8">
                <x-ui.section-heading :eyebrow="__('blog.related_heading_eyebrow')" :title="__('blog.related_heading_title')" />
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach($related as $relatedPost)
                        @include('marketplace.blog.partials.card', ['post' => $relatedPost])
                    @endforeach
                </div>
            </div>
        @endif
    </article>
</x-layouts.marketplace>
