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

    <article class="relative pb-24">

        {{-- ═══════════════════ HERO ═══════════════════ --}}
        <div class="relative">

            {{-- Cover image with overlay --}}
            @if($post->cover_url)
                <div class="relative aspect-[21/9] max-h-[560px] min-h-[260px] w-full overflow-hidden bg-zinc-950 sm:aspect-[21/8] lg:aspect-[21/7]">
                    <img
                        src="{{ $post->cover_url }}"
                        alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}"
                        width="1600" height="640"
                        loading="eager" fetchpriority="high"
                        class="h-full w-full object-cover"
                    >
                    {{-- gradient overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/60 to-zinc-950/20"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/30 to-transparent"></div>
                </div>

                {{-- Article header — overlaid on bottom of image --}}
                <div class="absolute inset-x-0 bottom-0">
                    <div class="mx-auto max-w-[88rem] px-4 pb-8 sm:px-6 sm:pb-10 lg:px-8 xl:px-10">
                        {{-- Breadcrumb --}}
                        <nav class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-400" aria-label="Breadcrumb">
                            <a href="{{ route('home') }}" class="transition hover:text-white">{{ __('blog.breadcrumb_home') }}</a>
                            <span class="text-zinc-600">›</span>
                            <a href="{{ route('blog.index') }}" class="transition hover:text-white">{{ __('blog.breadcrumb_blog') }}</a>
                            @if($primaryCategory)
                                <span class="text-zinc-600">›</span>
                                <a href="{{ route('blog.category', $primaryCategory) }}" class="max-w-[12rem] truncate transition hover:text-white sm:max-w-none">{{ $primaryCategory->localized('name') }}</a>
                            @endif
                        </nav>

                        {{-- Categories --}}
                        @if($post->categories->isNotEmpty())
                            <div class="mb-4 flex flex-wrap gap-2">
                                @foreach($post->categories as $cat)
                                    <a href="{{ route('blog.category', $cat) }}" class="inline-flex items-center rounded-full border border-emerald-400/40 bg-emerald-400/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-emerald-200 backdrop-blur-sm transition hover:bg-emerald-400/25">{{ $cat->localized('name') }}</a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Title --}}
                        <h1 class="max-w-[54rem] text-[1.75rem] font-black leading-[1.12] tracking-tight text-white drop-shadow-lg sm:text-[2.25rem] sm:leading-[1.1] lg:text-[2.75rem] lg:leading-[1.07]">{{ $post->localized_title }}</h1>
                    </div>
                </div>
            @else
                {{-- No cover: plain header --}}
                <div class="border-b border-white/10 bg-zinc-950 py-12 sm:py-16">
                    <div class="mx-auto max-w-[88rem] px-4 sm:px-6 lg:px-8 xl:px-10">
                        <nav class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500" aria-label="Breadcrumb">
                            <a href="{{ route('home') }}" class="transition hover:text-emerald-300">{{ __('blog.breadcrumb_home') }}</a>
                            <span class="text-zinc-700">›</span>
                            <a href="{{ route('blog.index') }}" class="transition hover:text-emerald-300">{{ __('blog.breadcrumb_blog') }}</a>
                            @if($primaryCategory)
                                <span class="text-zinc-700">›</span>
                                <a href="{{ route('blog.category', $primaryCategory) }}" class="transition hover:text-emerald-300">{{ $primaryCategory->localized('name') }}</a>
                            @endif
                        </nav>

                        @if($post->categories->isNotEmpty())
                            <div class="mb-4 flex flex-wrap gap-2">
                                @foreach($post->categories as $cat)
                                    <a href="{{ route('blog.category', $cat) }}" class="inline-flex items-center rounded-full border border-emerald-400/35 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-emerald-300 transition hover:bg-emerald-400/20">{{ $cat->localized('name') }}</a>
                                @endforeach
                            </div>
                        @endif

                        <h1 class="max-w-[54rem] text-[1.75rem] font-black leading-[1.12] tracking-tight text-white sm:text-[2.25rem] sm:leading-[1.1] lg:text-[2.75rem] lg:leading-[1.07]">{{ $post->localized_title }}</h1>
                    </div>
                </div>
            @endif
        </div>

        {{-- ═══════════════════ META BAR ═══════════════════ --}}
        <div class="border-b border-white/[0.07] bg-zinc-950/95 backdrop-blur-sm">
            <div class="mx-auto max-w-[88rem] px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-4 py-4">

                    {{-- Author + stats --}}
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        <div class="flex items-center gap-3">
                            @if($authorAvatar)
                                <img src="{{ $authorAvatar }}" alt="{{ $authorName }}" width="36" height="36" class="h-9 w-9 rounded-full object-cover ring-2 ring-emerald-400/30">
                            @else
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-400/15 text-sm font-black text-emerald-300 ring-2 ring-emerald-400/25">{{ $authorInitial }}</span>
                            @endif
                            <span class="font-semibold text-white text-sm">{{ $authorName }}</span>
                        </div>

                        <span class="hidden w-px self-stretch bg-white/10 sm:block"></span>

                        <time class="text-sm text-zinc-400" datetime="{{ optional($post->published_at)->toAtomString() }}">
                            {{ optional($post->published_at)->translatedFormat('d M Y') }}
                        </time>

                        <span class="flex items-center gap-1.5 text-sm text-zinc-400">
                            <svg class="h-4 w-4 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            {{ number_format($post->views) }}
                        </span>

                        <span class="flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-300">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ __('blog.reading_time', ['count' => $readMinutes]) }}
                        </span>
                    </div>

                    {{-- Share --}}
                    <div class="flex items-center gap-2">
                        <span class="hidden text-[10px] font-bold uppercase tracking-widest text-zinc-600 sm:block">{{ __('blog.share') }}</span>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonicalUrl) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener"
                           class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-400 transition hover:border-zinc-400/30 hover:bg-white/10 hover:text-white"
                           title="X (Twitter)">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63ZM17.2 19.77h1.833L6.886 4.126H4.92L17.2 19.77Z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}" target="_blank" rel="noopener"
                           class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-400 transition hover:border-blue-400/30 hover:bg-blue-400/10 hover:text-blue-300"
                           title="Facebook">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                        </a>
                        <button type="button"
                            x-data="{copied:false}"
                            @click="navigator.clipboard.writeText('{{ $canonicalUrl }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                            class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-400 transition hover:border-emerald-400/30 hover:bg-emerald-400/10 hover:text-emerald-300"
                            :title="copied ? '✓ Copied' : '{{ __('blog.copy_link') }}'">
                            <svg x-show="!copied" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            <svg x-show="copied" class="h-3.5 w-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════ EXCERPT ═══════════════════ --}}
        @if($post->localized_excerpt)
            <div class="mx-auto max-w-[88rem] px-4 pt-8 sm:px-6 sm:pt-10 lg:px-8 xl:px-10">
                <p class="max-w-[54rem] text-lg leading-[1.8] text-zinc-300 sm:text-xl sm:leading-[1.75]">{{ $post->localized_excerpt }}</p>
            </div>
        @endif

        {{-- ═══════════════════ CONTENT ═══════════════════ --}}
        <div class="mx-auto mt-10 max-w-[88rem] px-4 sm:px-6 sm:mt-12 lg:px-8 xl:px-10">

            @if(! ($hasActiveBlocks ?? false))
                {{-- No blocks: admin notice --}}
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_min(20rem,100%)] lg:items-start lg:gap-10">
                    <div class="rounded-2xl border border-amber-400/25 bg-amber-400/[0.07] p-8">
                        <p class="text-lg font-black text-amber-50">{{ __('blog.blocks_empty_title') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-amber-100/80">{{ __('blog.blocks_empty_hint') }}</p>
                        @auth
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.blog.edit', $post) }}" class="mt-5 inline-flex items-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.blocks_empty_admin_cta') }}</a>
                            @endif
                        @endauth
                    </div>
                    <aside class="space-y-6 lg:sticky lg:top-28">
                        @include('marketplace.blog.partials.subscribe', ['compact' => true])
                        @include('blog._sidebar_models_cta')
                    </aside>
                </div>
            @else

                {{-- Mobile TOC --}}
                @if($hasToc)
                    <nav class="mb-8 rounded-2xl border border-white/[0.07] bg-zinc-900/60 px-5 py-5 lg:hidden" aria-label="{{ __('blog.toc') }}">
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-400/80">{{ __('blog.toc') }}</p>
                        <ol class="mt-3 flex list-none flex-col gap-0.5 p-0">
                            @foreach($toc as $i => $item)
                                <li>
                                    <a href="#{{ $item['id'] }}" class="flex gap-2.5 rounded-xl py-2 px-1 text-sm text-zinc-400 transition hover:bg-emerald-400/[0.06] hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-3' : '' }}">
                                        <span class="w-5 shrink-0 font-mono text-xs text-emerald-500/70 pt-0.5">{{ $i + 1 }}.</span>
                                        <span class="min-w-0">{{ $item['text'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                {{-- Three-column at desktop: toc | content | sidebar --}}
                <div @class([
                    'grid gap-8 lg:items-start lg:gap-10 xl:gap-12',
                    'lg:grid-cols-[11rem_minmax(0,1fr)_min(17rem,100%)] xl:grid-cols-[13rem_minmax(0,1fr)_min(18.5rem,100%)]' => $hasToc,
                    'lg:grid-cols-[minmax(0,1fr)_min(17rem,100%)] xl:grid-cols-[minmax(0,1fr)_min(18.5rem,100%)]' => ! $hasToc,
                ])>

                    {{-- Desktop TOC sidebar (left) --}}
                    @if($hasToc)
                        <aside class="hidden lg:block">
                            <nav class="sticky top-28 rounded-2xl border border-white/[0.07] bg-zinc-900/50 px-3.5 py-5" aria-label="{{ __('blog.toc') }}">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-400/80">{{ __('blog.toc') }}</p>
                                <ol class="mt-3.5 flex list-none flex-col gap-0.5 p-0">
                                    @foreach($toc as $i => $item)
                                        <li>
                                            <a href="#{{ $item['id'] }}" class="flex gap-2 rounded-lg border-l-2 border-transparent py-2 pl-2 pr-1 text-[13px] leading-snug text-zinc-400 transition hover:border-emerald-400/50 hover:bg-emerald-400/[0.06] hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-3.5' : '' }}">
                                                <span class="w-4 shrink-0 pt-0.5 font-mono text-[11px] text-emerald-500/70">{{ $i + 1 }}.</span>
                                                <span class="min-w-0 break-words">{{ $item['text'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        </aside>
                    @endif

                    {{-- Article body --}}
                    <div class="min-w-0 space-y-8">
                        @foreach($blocks as $block)
                            @if(\Illuminate\Support\Facades\View::exists('blog.blocks.'.$block->type))
                                @include('blog.blocks.'.$block->type, ['block' => $block, 'headingIds' => $headingIds ?? []])
                            @endif
                        @endforeach

                        {{-- Tags --}}
                        @if($post->tags->isNotEmpty())
                            <div class="border-t border-white/[0.07] pt-6">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-600">{{ __('blog.tags_label') }}</span>
                                    @foreach($post->tags as $tag)
                                        <a href="{{ route('blog.tag', $tag) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-0.5 text-xs font-medium text-zinc-400 transition hover:border-emerald-400/30 hover:text-emerald-300">#{{ $tag->localized() }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Mobile share --}}
                        <div class="border-t border-white/[0.07] pt-6 lg:hidden">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-zinc-600">{{ __('blog.share') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonicalUrl) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-white/10 hover:text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63ZM17.2 19.77h1.833L6.886 4.126H4.92L17.2 19.77Z"/></svg>
                                    X (Twitter)
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-blue-400/10 hover:text-blue-300">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                                    Facebook
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Right sidebar --}}
                    <aside class="min-w-0 space-y-5 lg:sticky lg:top-28">
                        @include('marketplace.blog.partials.subscribe', ['compact' => true])
                        @include('blog._sidebar_models_cta')

                        {{-- Desktop share (in sidebar) --}}
                        <div class="hidden rounded-2xl border border-white/[0.07] bg-zinc-900/40 p-4 lg:block">
                            <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-zinc-600">{{ __('blog.share') }}</p>
                            <div class="flex flex-col gap-2">
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode($canonicalUrl) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2.5 rounded-xl border border-white/[0.07] bg-white/[0.03] px-3.5 py-2.5 text-sm text-zinc-300 transition hover:border-zinc-400/25 hover:bg-white/[0.07] hover:text-white">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.736l7.73-8.835L1.254 2.25H8.08l4.259 5.63 5.905-5.63ZM17.2 19.77h1.833L6.886 4.126H4.92L17.2 19.77Z"/></svg>
                                    X (Twitter)
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonicalUrl) }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2.5 rounded-xl border border-white/[0.07] bg-white/[0.03] px-3.5 py-2.5 text-sm text-zinc-300 transition hover:border-blue-400/20 hover:bg-blue-400/[0.07] hover:text-blue-200">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                                    Facebook
                                </a>
                                <button type="button"
                                    x-data="{copied:false}"
                                    @click="navigator.clipboard.writeText('{{ $canonicalUrl }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                                    class="flex w-full items-center gap-2.5 rounded-xl border border-white/[0.07] bg-white/[0.03] px-3.5 py-2.5 text-sm text-zinc-300 transition hover:border-emerald-400/25 hover:bg-emerald-400/[0.07] hover:text-emerald-200">
                                    <svg x-show="!copied" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                    <svg x-show="copied" class="h-4 w-4 shrink-0 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span x-text="copied ? '✓ {{ __('blog.copied') }}' : '{{ __('blog.copy_link') }}'">{{ __('blog.copy_link') }}</span>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            @endif
        </div>

        {{-- Mobile subscribe --}}
        <div class="mx-auto mt-12 max-w-[88rem] px-4 sm:px-6 lg:px-8 xl:px-10 lg:hidden">
            @include('marketplace.blog.partials.subscribe')
        </div>

        {{-- ═══════════════════ RELATED POSTS ═══════════════════ --}}
        @if($related->isNotEmpty())
            <div class="mx-auto mt-20 max-w-[88rem] border-t border-white/[0.07] px-4 pt-16 sm:px-6 lg:px-8 xl:px-10">
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-emerald-400">{{ __('blog.related_heading_eyebrow') }}</p>
                        <h2 class="mt-1.5 text-2xl font-black text-white sm:text-3xl">{{ __('blog.related_heading_title') }}</h2>
                    </div>
                    <a href="{{ route('blog.index') }}" class="shrink-0 text-sm font-semibold text-zinc-400 transition hover:text-emerald-300">{{ __('blog.view_all') }} →</a>
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
