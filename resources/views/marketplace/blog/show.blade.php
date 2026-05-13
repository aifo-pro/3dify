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

    <article class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/25 bg-emerald-300/[0.10] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
        @endif
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-zinc-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-200">{{ __('blog.breadcrumb_home') }}</a><span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-emerald-200">{{ __('blog.breadcrumb_blog') }}</a><span>/</span>
            <span class="text-zinc-300">{{ $post->localized_title }}</span>
        </nav>

        <header class="max-w-4xl">
            <div class="flex flex-wrap gap-2">
                @foreach($post->categories as $category)
                    <a href="{{ route('blog.category', $category) }}"><x-ui.badge>{{ $category->localized('name') }}</x-ui.badge></a>
                @endforeach
            </div>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-white sm:text-6xl">{{ $post->localized_title }}</h1>
            <p class="mt-5 text-lg leading-8 text-zinc-400">{{ $post->localized_excerpt }}</p>
            <div class="mt-5 flex flex-wrap gap-4 text-sm text-zinc-500">
                <time datetime="{{ optional($post->published_at)->toAtomString() }}">{{ optional($post->published_at)->translatedFormat('d M Y') }}</time>
                <span>{{ $post->author?->displayName() ?: '3Dify' }}</span>
                <span>{{ number_format($post->views) }} {{ __('blog.views') }}</span>
            </div>
        </header>

        @if($post->cover_url)
            <img src="{{ $post->cover_url }}" alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}" width="1200" height="630" loading="eager" fetchpriority="high" class="mt-8 aspect-[1200/630] w-full rounded-3xl border border-white/10 bg-zinc-950 object-cover shadow-2xl shadow-black/30">
        @endif

        @if($toc)
            <nav class="mt-8 rounded-3xl border border-white/10 bg-white/[0.04] p-5 lg:hidden" aria-label="{{ __('blog.toc') }}">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-300">{{ __('blog.toc') }}</p>
                <div class="mt-3 grid gap-2">
                    @foreach($toc as $item)
                        <a href="#{{ $item['id'] }}" class="text-sm text-zinc-400 hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-4' : '' }}">{{ $item['text'] }}</a>
                    @endforeach
                </div>
            </nav>
        @endif

        <div class="mt-10 grid gap-8 lg:grid-cols-[240px_1fr]">
            <aside class="hidden lg:block">
                @if($toc)
                    <nav class="sticky top-28 rounded-3xl border border-white/10 bg-white/[0.04] p-5" aria-label="{{ __('blog.toc') }}">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-300">{{ __('blog.toc') }}</p>
                        <div class="mt-4 grid gap-2">
                            @foreach($toc as $item)
                                <a href="#{{ $item['id'] }}" class="text-sm text-zinc-400 hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-4' : '' }}">{{ $item['text'] }}</a>
                            @endforeach
                        </div>
                    </nav>
                @endif
            </aside>
            <div>
                <section class="blog-content rounded-3xl border border-white/10 bg-white/[0.04] p-6 text-zinc-200 sm:p-10">
                    {!! $contentHtml !!}
                </section>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('blog.tag', $tag) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs font-bold text-zinc-300 hover:border-emerald-300/30 hover:text-emerald-100">#{{ $tag->localized() }}</a>
                    @endforeach
                </div>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url) }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-white/10 bg-white/[0.05] px-4 py-2 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('blog.share_facebook') }}</a>
                    <a href="https://t.me/share/url?url={{ urlencode($post->url) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-white/10 bg-white/[0.05] px-4 py-2 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('blog.share_telegram') }}</a>
                    <a href="https://x.com/intent/tweet?url={{ urlencode($post->url) }}&text={{ urlencode($post->localized_title) }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-white/10 bg-white/[0.05] px-4 py-2 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('blog.share_x') }}</a>
                </div>
                <div class="mt-10">@include('marketplace.blog.partials.subscribe')</div>
            </div>
        </div>

        @if($related->isNotEmpty())
            <section class="mt-14">
                <x-section-heading :eyebrow="__('blog.related_heading_eyebrow')" :title="__('blog.related_heading_title')" />
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach($related as $relatedPost)
                        @include('marketplace.blog.partials.card', ['post' => $relatedPost])
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</x-layouts.marketplace>
