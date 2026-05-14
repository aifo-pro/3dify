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
        'publisher' => [
            '@type' => 'Organization',
            'name' => '3Dify',
            'url' => $siteUrl,
        ],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
    ];
    $primaryCategory = $post->categories->first();
    $authorName = $post->author?->displayName() ?: '3Dify';
    $authorInitial = mb_strtoupper(mb_substr($authorName, 0, 1));
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
    @endpush

    <article class="blog-show-v2 relative pb-20 pt-8 sm:pt-12">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-emerald-500/12 to-transparent sm:h-40"></div>

        <div class="relative mx-auto max-w-[min(100%,88rem)] px-4 sm:px-6 lg:px-8 xl:px-10">
            @if(session('status'))
                <div class="mb-8 rounded-3xl border border-emerald-300/25 bg-emerald-300/[0.10] px-5 py-4 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
            @endif

            {{-- Hero (новий вигляд) --}}
            <div class="relative overflow-hidden rounded-3xl border border-emerald-400/25 bg-zinc-950/90 p-8 shadow-2xl shadow-emerald-950/30 ring-1 ring-white/10 sm:p-10 lg:p-12">
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-400/15 blur-3xl"></div>
                <div class="relative">
                    <nav class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500 sm:text-sm" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}" class="transition hover:text-emerald-200">{{ __('blog.breadcrumb_home') }}</a>
                        <span class="text-zinc-600" aria-hidden="true">›</span>
                        <a href="{{ route('blog.index') }}" class="transition hover:text-emerald-200">{{ __('blog.breadcrumb_blog') }}</a>
                        @if($primaryCategory)
                            <span class="text-zinc-600" aria-hidden="true">›</span>
                            <a href="{{ route('blog.category', $primaryCategory) }}" class="max-w-[12rem] truncate transition hover:text-emerald-200 sm:max-w-md">{{ $primaryCategory->localized('name') }}</a>
                        @endif
                    </nav>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @forelse($post->categories as $category)
                            <span class="inline-flex items-center rounded-full border border-emerald-400/35 bg-emerald-400/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-emerald-100">{{ $category->localized('name') }}</span>
                        @empty
                            <span class="inline-flex items-center rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-zinc-500">Blog</span>
                        @endforelse
                    </div>

                    <h1 class="mt-6 max-w-[52rem] text-[1.85rem] font-black leading-[1.14] tracking-tight text-white sm:text-[2.35rem] sm:leading-[1.12] lg:text-[2.75rem] lg:leading-[1.08]">{{ $post->localized_title }}</h1>
                    @if($post->localized_excerpt)
                        <p class="mt-6 max-w-[48rem] text-lg leading-[1.75] text-zinc-300 sm:text-xl sm:leading-[1.72]">{{ $post->localized_excerpt }}</p>
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4 border-t border-white/10 pt-8 text-sm text-zinc-400">
                        <div class="flex items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-400/35 bg-emerald-400/12 text-sm font-black text-emerald-100" aria-hidden="true">{{ $authorInitial }}</span>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">{{ __('blog.author_label') }}</p>
                                <p class="font-semibold text-white">{{ $authorName }}</p>
                            </div>
                        </div>
                        <time class="font-medium text-zinc-300" datetime="{{ optional($post->published_at)->toAtomString() }}">{{ optional($post->published_at)->translatedFormat('d M Y') }}</time>
                        <span class="font-medium text-zinc-300">{{ number_format($post->views) }} {{ __('blog.views') }}</span>
                        <span class="font-medium text-emerald-200/90">{{ __('blog.reading_time', ['count' => $readMinutes]) }}</span>
                    </div>
                </div>
            </div>

            @if($post->cover_url)
                <div class="relative mx-auto mt-10 max-w-[min(76rem,100%)]">
                    <div class="overflow-hidden rounded-3xl border border-white/10 bg-zinc-950 shadow-2xl shadow-black/40 ring-1 ring-emerald-400/10">
                        <div class="relative aspect-[21/9] min-h-[200px] sm:aspect-[2/1]">
                            <img src="{{ $post->cover_url }}" alt="{{ $post->localized('cover_alt') ?: $post->localized_title }}" width="1600" height="800" loading="eager" fetchpriority="high" class="absolute inset-0 h-full w-full object-cover">
                        </div>
                    </div>
                </div>
            @endif

            @if(! ($hasActiveBlocks ?? false))
                <div class="mx-auto mt-12 grid max-w-none gap-10 lg:grid-cols-[minmax(0,1fr)_min(20rem,100%)] lg:items-start lg:gap-12">
                    <div class="min-w-0 space-y-8">
                        @if($post->localized_excerpt)
                            <section class="rounded-3xl border border-white/10 bg-white/[0.03] p-8 sm:p-10 shadow-xl shadow-black/20" aria-labelledby="article-lead-label">
                                <p id="article-lead-label" class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400">{{ __('blog.blocks_empty_lead') }}</p>
                                <p class="mt-4 text-lg leading-relaxed text-zinc-200 sm:text-xl">{{ $post->localized_excerpt }}</p>
                            </section>
                        @endif

                        <section class="rounded-3xl border border-amber-400/30 bg-gradient-to-br from-amber-400/[0.12] via-zinc-950 to-zinc-950 p-8 sm:p-10 shadow-2xl shadow-black/30 ring-1 ring-white/10">
                            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <p class="text-xl font-black tracking-tight text-amber-50 sm:text-2xl">{{ __('blog.blocks_empty_title') }}</p>
                                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-amber-100/85 sm:text-base">{{ __('blog.blocks_empty_hint') }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                                    <a href="{{ route('blog.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/[0.06] px-5 py-2.5 text-sm font-bold text-white transition hover:border-emerald-400/40 hover:bg-emerald-400/10">{{ __('blog.blocks_empty_back_blog') }}</a>
                                    @auth
                                        @if(auth()->user()->isAdmin())
                                            <a href="{{ route('admin.blog.edit', $post) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.blocks_empty_admin_cta') }}</a>
                                        @endif
                                    @endauth
                                </div>
                            </div>

                            <div class="mt-8 rounded-2xl border border-white/10 bg-black/40 p-5 font-mono text-xs leading-relaxed text-zinc-300 sm:text-sm">
                                <p class="font-sans text-[10px] font-bold uppercase tracking-wider text-zinc-500">{{ __('blog.blocks_empty_cli_title') }}</p>
                                <p class="mt-2 font-sans text-sm text-zinc-400">{{ __('blog.blocks_empty_cli_hint') }}</p>
                                <p class="mt-3 text-emerald-200/90">php artisan migrate --force</p>
                                <p class="mt-1 text-emerald-200/90">php artisan blog:install-petg-demo</p>
                            </div>
                        </section>
                    </div>

                    <aside class="hidden min-w-0 space-y-6 lg:block lg:sticky lg:top-28">
                        @include('marketplace.blog.partials.subscribe', ['compact' => true])
                        <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/[0.06] p-6 text-center shadow-lg">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-200">{{ __('blog.sidebar_models_title') }}</p>
                            <a href="{{ route('products.index') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-400 py-3 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.sidebar_models_button') }}</a>
                        </div>
                    </aside>
                </div>

                <div class="mx-auto mt-10 max-w-none lg:hidden">
                    @include('marketplace.blog.partials.subscribe')
                </div>
            @else
                @if($hasToc)
                    <nav class="mx-auto mt-10 w-full rounded-2xl border border-white/[0.08] bg-gradient-to-b from-zinc-900/80 to-zinc-950/70 px-5 py-5 shadow-lg shadow-black/25 ring-1 ring-white/[0.04] lg:hidden" aria-label="{{ __('blog.toc') }}">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400/90">{{ __('blog.toc') }}</p>
                        <ol class="mt-3.5 flex list-none flex-col gap-0.5 p-0 text-[15px] leading-snug text-zinc-400">
                            @foreach($toc as $i => $item)
                                <li>
                                    <a href="#{{ $item['id'] }}" class="flex gap-2 rounded-xl py-2 transition hover:bg-emerald-400/[0.06] hover:text-emerald-100 {{ $item['level'] === 3 ? 'pl-3' : '' }}">
                                        <span class="w-6 shrink-0 font-mono text-xs text-emerald-500/85">{{ $i + 1 }}.</span>
                                        <span class="min-w-0 break-words">{{ $item['text'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <div @class([
                    'mx-auto mt-12 grid max-w-none gap-8 lg:items-start lg:gap-10 xl:gap-12',
                    'lg:grid-cols-[11.5rem_minmax(0,1fr)_min(16.5rem,100%)] xl:grid-cols-[12.5rem_minmax(0,1fr)_min(18rem,100%)]' => $hasToc,
                    'lg:grid-cols-[minmax(0,1fr)_min(16.5rem,100%)] xl:grid-cols-[minmax(0,1fr)_min(18rem,100%)]' => ! $hasToc,
                ])>
                    @if($hasToc)
                        <aside class="hidden lg:block">
                            <nav class="sticky top-28 rounded-2xl border border-white/[0.08] bg-gradient-to-b from-zinc-900/90 to-zinc-950/80 px-3.5 py-5 shadow-lg shadow-black/20 ring-1 ring-white/[0.04]" aria-label="{{ __('blog.toc') }}">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400/90">{{ __('blog.toc') }}</p>
                                <ol class="mt-3.5 flex list-none flex-col gap-0.5 p-0 text-[13px] leading-[1.45] text-zinc-400">
                                    @foreach($toc as $i => $item)
                                        <li>
                                            <a href="#{{ $item['id'] }}" class="flex gap-2 rounded-lg border-l-2 border-transparent py-2 pl-2 pr-1 transition hover:border-emerald-400/50 hover:bg-emerald-400/[0.07] hover:text-emerald-50 {{ $item['level'] === 3 ? 'pl-3' : '' }}">
                                                <span class="w-5 shrink-0 pt-0.5 font-mono text-[11px] text-emerald-500/80">{{ $i + 1 }}.</span>
                                                <span class="min-w-0 break-words">{{ $item['text'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        </aside>
                    @endif

                    <div class="min-w-0 space-y-10">
                        <div class="space-y-10 rounded-[1.75rem] border border-white/[0.07] bg-gradient-to-br from-zinc-900/50 via-zinc-950/40 to-zinc-950/80 p-7 shadow-[0_1px_0_0_rgba(255,255,255,0.04)_inset] sm:space-y-12 sm:p-9 lg:space-y-14 lg:p-11 xl:rounded-[2rem] xl:p-14 ring-1 ring-white/[0.04]">
                            @foreach($blocks as $block)
                                @if(\Illuminate\Support\Facades\View::exists('blog.blocks.'.$block->type))
                                    @include('blog.blocks.'.$block->type, ['block' => $block, 'headingIds' => $headingIds ?? []])
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <aside class="min-w-0 space-y-6 lg:sticky lg:top-28">
                        @include('marketplace.blog.partials.subscribe', ['compact' => true])
                        <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/[0.06] p-6 text-center shadow-lg">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-200">{{ __('blog.sidebar_models_title') }}</p>
                            <a href="{{ route('products.index') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-400 py-3 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('blog.sidebar_models_button') }}</a>
                        </div>
                    </aside>
                </div>
            @endif

            @if($hasActiveBlocks ?? false)
                <div class="mt-12 lg:hidden">
                    @include('marketplace.blog.partials.subscribe')
                </div>
            @endif
        </div>

        @if($related->isNotEmpty())
            <div class="mx-auto mt-20 max-w-[min(100%,88rem)] border-t border-white/10 px-4 pt-16 sm:px-6 lg:px-8 xl:px-10">
                <x-ui.section-heading :eyebrow="__('blog.related_heading_eyebrow')" :title="__('blog.related_heading_title')" />
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach($related as $relatedPost)
                        @include('marketplace.blog.partials.card', ['post' => $relatedPost])
                    @endforeach
                </div>
            </div>
        @endif
    </article>
</x-layouts.marketplace>
