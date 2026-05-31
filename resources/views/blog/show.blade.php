@php
    use App\Support\BlogBlockPlainText;
    $title = $post->localized('seo_title') ?: $post->localized_title;
    $description = $post->localized('seo_description') ?: $post->localized_excerpt;
    $image = $post->og_image_url ?: $post->cover_url;
    $canonicalUrl = $post->url;
    $siteUrl = rtrim((string) config('app.url'), '/');
    $articlePlain = BlogBlockPlainText::concatenate($blocks ?? collect(), app()->getLocale(), trim(strip_tags($post->localized('excerpt'))));
    preg_match_all('/\S+/u', $articlePlain, $readMatches);
    $wordCount = count($readMatches[0] ?? []);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->localized_title,
        'description' => $description,
        'image' => $image ? [$image] : [],
        'datePublished' => optional($post->published_at)->toAtomString(),
        'dateModified' => optional($post->updated_at)->toAtomString(),
        'wordCount' => $wordCount,
        'author' => ['@type' => 'Person', 'name' => $post->author?->displayName() ?: '3Dify'],
        'publisher' => ['@type' => 'Organization', 'name' => '3Dify', 'url' => $siteUrl],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
    ];
    $primaryCategory = $post->categories->first();
    $authorName = $post->author?->displayName() ?: '3Dify';
    $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1));
    $authorAvatar = $post->author?->avatarUrl();
    $hasToc = ($hasActiveBlocks ?? false) && is_array($toc ?? null) && count($toc) > 0;
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
        @if(! empty($faqJsonLd))
            <script type="application/ld+json">{!! json_encode($faqJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
        @endif
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('blog.breadcrumb_home'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('blog.breadcrumb_blog'), 'item' => route('blog.index')],
                $primaryCategory ? ['@type' => 'ListItem', 'position' => 3, 'name' => $primaryCategory->localized('name'), 'item' => route('blog.category', $primaryCategory)] : null,
                ['@type' => 'ListItem', 'position' => $primaryCategory ? 4 : 3, 'name' => $post->localized_title, 'item' => $canonicalUrl],
            ])),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endpush

    <article class="pb-24">

        {{-- ══════════════════════════════════════
             HERO — cover image + overlaid header
        ══════════════════════════════════════ --}}
        @if($post->cover_url)
            <div class="relative w-full overflow-hidden bg-zinc-950" style="min-height:260px;max-height:560px;aspect-ratio:21/8;">
                <img
                    src="{{ $post->cover_url }}"
                    alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}"
                    width="1600" height="686"
                    loading="eager" fetchpriority="high"
                    class="absolute inset-0 h-full w-full object-cover"
                >
                {{-- gradient overlay --}}
                <div class="absolute inset-0" style="background:linear-gradient(to top,#09090b 0%,rgba(9,9,11,.55) 45%,rgba(9,9,11,.15) 100%)"></div>
                {{-- content on top of image --}}
                <div class="absolute inset-x-0 bottom-0 z-10">
                    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 sm:pb-10 lg:px-8">
                        {{-- Breadcrumb --}}
                        <nav class="mb-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-300" aria-label="Breadcrumb">
                            <a href="{{ route('home') }}" class="transition hover:text-white">{{ __('blog.breadcrumb_home') }}</a>
                            <span class="text-zinc-500">›</span>
                            <a href="{{ route('blog.index') }}" class="transition hover:text-white">{{ __('blog.breadcrumb_blog') }}</a>
                            @if($primaryCategory)
                                <span class="text-zinc-500">›</span>
                                <a href="{{ route('blog.category', $primaryCategory) }}" class="transition hover:text-white">{{ $primaryCategory->localized('name') }}</a>
                            @endif
                        </nav>
                        {{-- Categories --}}
                        @if($post->categories->isNotEmpty())
                            <div class="mb-4 flex flex-wrap gap-2">
                                @foreach($post->categories as $cat)
                                    <a href="{{ route('blog.category', $cat) }}"
                                       class="inline-flex items-center rounded-full border border-emerald-400/50 bg-emerald-400/20 px-3 py-1 text-xs font-bold uppercase tracking-widest text-emerald-200 backdrop-blur-sm transition hover:bg-emerald-400/30">{{ $cat->localized('name') }}</a>
                                @endforeach
                            </div>
                        @endif
                        {{-- Title --}}
                        <h1 class="max-w-3xl text-2xl font-black leading-tight tracking-tight text-white drop-shadow sm:text-4xl lg:text-5xl">{{ $post->localized_title }}</h1>
                    </div>
                </div>
            </div>
        @else
            {{-- No cover: plain header --}}
            <div class="border-b border-white/10 bg-zinc-950 py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <nav class="mb-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}" class="transition hover:text-white">{{ __('blog.breadcrumb_home') }}</a>
                        <span class="text-zinc-700">›</span>
                        <a href="{{ route('blog.index') }}" class="transition hover:text-white">{{ __('blog.breadcrumb_blog') }}</a>
                        @if($primaryCategory)
                            <span class="text-zinc-700">›</span>
                            <a href="{{ route('blog.category', $primaryCategory) }}" class="transition hover:text-white">{{ $primaryCategory->localized('name') }}</a>
                        @endif
                    </nav>
                    @if($post->categories->isNotEmpty())
                        <div class="mb-4 flex flex-wrap gap-2">
                            @foreach($post->categories as $cat)
                                <a href="{{ route('blog.category', $cat) }}"
                                   class="inline-flex items-center rounded-full border border-emerald-400/35 bg-emerald-400/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-emerald-300 transition hover:bg-emerald-400/20">{{ $cat->localized('name') }}</a>
                            @endforeach
                        </div>
                    @endif
                    <h1 class="max-w-3xl text-2xl font-black leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $post->localized_title }}</h1>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════
             META BAR
        ══════════════════════════════════════ --}}
        <div class="border-b border-white/10 bg-zinc-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4 py-4">
                    {{-- Author + stats --}}
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2.5">
                            @if($authorAvatar)
                                <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" width="32" height="32" class="h-8 w-8 rounded-full object-cover ring-2 ring-emerald-400/30">
                            @else
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-400/15 text-sm font-black text-emerald-300">{{ $authorInitial }}</span>
                            @endif
                            <span class="text-sm font-semibold text-white">{{ $authorName }}</span>
                        </div>
                        <time class="text-sm text-zinc-400" datetime="{{ optional($post->published_at)->toAtomString() }}">{{ optional($post->published_at)->translatedFormat('d M Y') }}</time>
                        <span class="flex items-center gap-1.5 text-sm text-zinc-400">
                            <svg class="h-4 w-4 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            {{ number_format($post->views) }}
                        </span>
                        <span class="rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-300">
                            {{ __('blog.reading_time', ['count' => $readMinutes]) }}
                        </span>
                    </div>
                    {{-- Share icons --}}
                    <div class="flex items-center gap-2">
                        <span class="hidden text-xs text-zinc-600 sm:block">{{ __('blog.share') }}</span>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonicalUrl) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener"
                           class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 text-zinc-400 transition hover:bg-white/10 hover:text-white" title="X">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63ZM17.2 19.77h1.833L6.886 4.126H4.92L17.2 19.77Z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}" target="_blank" rel="noopener"
                           class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 text-zinc-400 transition hover:bg-blue-400/10 hover:text-blue-300" title="Facebook">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                        </a>
                        <button type="button"
                            x-data="{c:false}"
                            @click="navigator.clipboard.writeText('{{ $canonicalUrl }}').then(()=>{c=true;setTimeout(()=>c=false,2000)})"
                            class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/5 text-zinc-400 transition hover:bg-emerald-400/10 hover:text-emerald-300">
                            <svg x-show="!c" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            <svg x-show="c" class="h-3.5 w-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════
             EXCERPT
        ══════════════════════════════════════ --}}
        @if($post->localized_excerpt)
            <div class="border-b border-white/10 bg-zinc-950/80">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <p class="max-w-3xl text-lg leading-relaxed text-zinc-300">{{ $post->localized_excerpt }}</p>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════
             CONTENT
        ══════════════════════════════════════ --}}
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">

            @if(! ($hasActiveBlocks ?? false))
                {{-- No blocks --}}
                <div class="grid gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <div class="rounded-2xl border border-amber-400/25 bg-amber-400/5 p-8">
                            <p class="text-lg font-black text-amber-50">{{ __('blog.blocks_empty_title') }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-amber-100/80">{{ __('blog.blocks_empty_hint') }}</p>
                            @auth
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.blog.edit', $post) }}" class="mt-5 inline-flex items-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.blocks_empty_admin_cta') }}</a>
                                @endif
                            @endauth
                        </div>
                    </div>
                    <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                        @include('marketplace.blog.partials.subscribe', ['compact' => true])
                        @include('blog._sidebar_models_cta')
                    </aside>
                </div>

            @else

                {{-- Mobile TOC --}}
                @if($hasToc)
                    <nav class="mb-8 rounded-2xl border border-white/10 bg-zinc-900/60 px-5 py-5 lg:hidden" aria-label="{{ __('blog.toc') }}">
                        <p class="mb-3 text-xs font-black uppercase tracking-widest text-emerald-400">{{ __('blog.toc') }}</p>
                        <ol class="list-none space-y-0.5 p-0">
                            @foreach($toc as $i => $item)
                                <li>
                                    <a href="#{{ $item['id'] }}" class="flex gap-2.5 rounded-xl px-1 py-2 text-sm text-zinc-400 transition hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-4' : '' }}">
                                        <span class="w-5 shrink-0 font-mono text-xs text-emerald-500/70">{{ $i + 1 }}.</span>
                                        <span>{{ $item['text'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                {{-- Layout: with TOC = 4 cols, without = 3 cols --}}
                @if($hasToc)
                    <div class="grid gap-8 lg:grid-cols-4 lg:items-start lg:gap-10">
                        {{-- Desktop TOC --}}
                        <aside class="hidden lg:block">
                            <nav class="sticky top-28 rounded-2xl border border-white/10 bg-zinc-900/50 px-4 py-5" aria-label="{{ __('blog.toc') }}">
                                <p class="mb-3 text-xs font-black uppercase tracking-widest text-emerald-400">{{ __('blog.toc') }}</p>
                                <ol class="list-none space-y-0.5 p-0">
                                    @foreach($toc as $i => $item)
                                        <li>
                                            <a href="#{{ $item['id'] }}" class="flex gap-2 rounded-lg border-l-2 border-transparent py-2 pl-2 pr-1 text-xs leading-snug text-zinc-400 transition hover:border-emerald-400/50 hover:bg-emerald-400/5 hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-3.5' : '' }}">
                                                <span class="w-4 shrink-0 font-mono text-emerald-500/70">{{ $i + 1 }}.</span>
                                                <span class="break-words">{{ $item['text'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        </aside>
                        {{-- Content --}}
                        <div class="min-w-0 space-y-8 lg:col-span-2">
                            @foreach($blocks as $block)
                                @if(\Illuminate\Support\Facades\View::exists('blog.blocks.'.$block->type))
                                    @include('blog.blocks.'.$block->type, ['block' => $block, 'headingIds' => $headingIds ?? []])
                                @endif
                            @endforeach
                            @include('blog._article_footer', compact('post', 'canonicalUrl'))
                        </div>
                        {{-- Sidebar --}}
                        <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                            @include('marketplace.blog.partials.subscribe', ['compact' => true])
                            @include('blog._sidebar_models_cta')
                            @include('blog._share_sidebar', compact('canonicalUrl', 'post'))
                        </aside>
                    </div>
                @else
                    <div class="grid gap-8 lg:grid-cols-3 lg:items-start lg:gap-10">
                        {{-- Content --}}
                        <div class="min-w-0 space-y-8 lg:col-span-2">
                            @foreach($blocks as $block)
                                @if(\Illuminate\Support\Facades\View::exists('blog.blocks.'.$block->type))
                                    @include('blog.blocks.'.$block->type, ['block' => $block, 'headingIds' => $headingIds ?? []])
                                @endif
                            @endforeach
                            @include('blog._article_footer', compact('post', 'canonicalUrl'))
                        </div>
                        {{-- Sidebar --}}
                        <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                            @include('marketplace.blog.partials.subscribe', ['compact' => true])
                            @include('blog._sidebar_models_cta')
                            @include('blog._share_sidebar', compact('canonicalUrl', 'post'))
                        </aside>
                    </div>
                @endif
            @endif
        </div>

        {{-- Mobile subscribe --}}
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 lg:hidden">
            @include('marketplace.blog.partials.subscribe')
        </div>

        {{-- Related posts --}}
        @if($related->isNotEmpty())
            <div class="mx-auto mt-16 max-w-7xl border-t border-white/10 px-4 pt-12 sm:px-6 lg:px-8">
                <div class="mb-8 flex items-end justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-emerald-400">{{ __('blog.related_heading_eyebrow') }}</p>
                        <h2 class="mt-1 text-2xl font-black text-white">{{ __('blog.related_heading_title') }}</h2>
                    </div>
                    <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-zinc-400 transition hover:text-emerald-300">{{ __('blog.view_all') }} →</a>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($related as $relatedPost)
                        @include('marketplace.blog.partials.card', ['post' => $relatedPost])
                    @endforeach
                </div>
            </div>
        @endif
    </article>
</x-layouts.marketplace>
