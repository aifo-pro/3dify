<x-layouts.marketplace
    :seo-title="__('Автори 3Dify — дизайнери 3D-моделей для друку') . ' · 3Dify'"
    :seo-description="__('Каталог авторів 3D-моделей: дизайнери STL, OBJ, GLB та 3MF. Підписуйтесь на оновлення та відкривайте колекції моделей для друку.')"
    :seo-canonical="route('authors.index')"
>
    @push('head')
        {!! \App\Support\Seo::jsonLd(\App\Support\Seo::breadcrumb([
            ['name' => __('Головна'), 'url' => route('home')],
            ['name' => __('Автори'), 'url' => route('authors.index')],
        ])) !!}
        {!! \App\Support\Seo::jsonLd(\App\Support\Seo::collectionPage(
            __('Автори 3Dify'),
            route('authors.index'),
            __('Каталог авторів 3D-моделей для друку.'),
            $authors->take(20)->map(fn ($a) => ['name' => $a->displayName(), 'url' => $a->profileUrl()])->all()
        )) !!}
    @endpush

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Visible breadcrumbs (matches BreadcrumbList schema) --}}
        <nav aria-label="Breadcrumb" class="mb-5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500">
            <a href="{{ route('home') }}" class="transition hover:text-emerald-300">{{ __('Головна') }}</a>
            <span class="text-zinc-700">›</span>
            <span class="text-zinc-300">{{ __('Автори') }}</span>
        </nav>

        <div class="relative overflow-hidden rounded-[32px] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30 sm:p-8 lg:p-10">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-400/15 blur-3xl"></div>
            <x-ui.badge>{{ __('Автори') }}</x-ui.badge>
            <div class="relative mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-end">
                <div>
                    <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Автори 3Dify') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('Знайдіть дизайнерів, які публікують якісні 3D-моделі, підписуйтеся на оновлення та відкривайте їхні колекції.') }}</p>
                </div>

                <form method="GET" action="{{ route('authors.index') }}" style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;padding:.75rem;border-radius:1.5rem;border:1px solid rgba(255,255,255,.10);background-color:rgba(9,9,11,.55);">
                    <label style="position:relative;display:block;flex:1 1 220px;min-width:220px;">
                        <span style="position:absolute;top:0;bottom:0;left:.75rem;display:flex;align-items:center;color:#71717a;pointer-events:none;">
                            <svg style="height:1rem;width:1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Пошук автора за ім\'ям або @username…') }}" style="height:2.75rem;width:100%;border-radius:1rem;border:1px solid rgba(255,255,255,.10);background-color:rgba(255,255,255,.06);padding:0 1rem 0 2.5rem;font-size:.875rem;color:#fff;" class="placeholder:text-zinc-500 focus:border-emerald-300 focus:outline-none focus:ring-1 focus:ring-emerald-300/40">
                    </label>
                    <select name="sort" style="height:2.75rem;width:240px;flex:0 0 240px;-webkit-appearance:none;-moz-appearance:none;appearance:none;border-radius:1rem;border:1px solid rgba(255,255,255,.10);background-color:rgba(255,255,255,.06);padding:0 2.75rem 0 1rem;font-size:.875rem;font-weight:500;color:#fff;background-image:url(&quot;data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 8 10 12 14 8'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right .875rem center;background-size:1.1rem 1.1rem;">
                        <option value="popular" @selected($filters['sort'] === 'popular')>{{ __('Популярні') }}</option>
                        <option value="new" @selected($filters['sort'] === 'new')>{{ __('Нові') }}</option>
                        <option value="models" @selected($filters['sort'] === 'models')>{{ __('Найбільше моделей') }}</option>
                        <option value="downloads" @selected($filters['sort'] === 'downloads')>{{ __('Найбільше завантажень') }}</option>
                    </select>
                    <button style="height:2.75rem;flex:0 0 auto;border-radius:1rem;background-color:#34d399;padding:0 1.25rem;font-size:.875rem;font-weight:900;color:#09090b;" class="transition hover:bg-emerald-300">{{ __('Знайти') }}</button>
                </form>
            </div>
        </div>

        <div class="mt-8">
            @if($authors->isEmpty())
                <x-ui.empty-state :title="__('Авторів ще немає')" :description="__('Коли користувачі опублікують моделі, вони зʼявляться тут.')" :href="route('author.products.create')" :action="__('Стати автором')" />
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($authors as $author)
                        @php
                            $modelsCount = (int) ($author->published_products_count ?? 0);
                            $downloadsCount = (int) ($author->downloads_sum ?? 0);
                            $followersCount = (int) ($author->followers_count ?? 0);
                            $bio = $author->localizedBio();
                            $isSelf = auth()->id() === $author->id;
                            $isFollowing = $author->isFollowedBy(auth()->user());
                        @endphp

                        <article class="group overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20 transition hover:-translate-y-1 hover:border-emerald-300/30">
                            <div class="relative h-28 overflow-hidden">
                                @if($author->coverUrl())
                                    <img src="{{ $author->coverUrl() }}" alt="" class="h-full w-full object-cover opacity-85 transition group-hover:scale-105">
                                @else
                                    <div class="h-full bg-[radial-gradient(circle_at_20%_20%,rgba(52,211,153,.28),transparent_55%),radial-gradient(circle_at_80%_40%,rgba(56,189,248,.18),transparent_60%),linear-gradient(135deg,#0c1f1a,#09090b)]"></div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent"></div>
                            </div>

                            <div class="-mt-10 p-5">
                                <div class="relative flex items-end gap-3">
                                    @if($author->avatarUrl())
                                        <img src="{{ $author->avatarUrl() }}" alt="{{ $author->displayName() }}" class="h-20 w-20 rounded-3xl border-4 border-zinc-950 object-cover shadow-xl shadow-black/30">
                                    @else
                                        <span class="grid h-20 w-20 place-items-center rounded-3xl border-4 border-zinc-950 bg-emerald-400 text-2xl font-black text-zinc-950 shadow-xl shadow-emerald-500/20">{{ mb_strtoupper(mb_substr($author->displayName(), 0, 1)) }}</span>
                                    @endif
                                    <div class="min-w-0 pb-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <a href="{{ $author->profileUrl() }}" class="block truncate text-lg font-black text-white hover:text-emerald-200">{{ $author->displayName() }}</a>
                                            <x-ui.verified-badge :user="$author" size="xs" :show-label="false" />
                                        </div>
                                        <p class="truncate text-xs text-zinc-500">{{ '@'.($author->username ?: 'author-'.$author->id) }}</p>
                                    </div>
                                </div>

                                <p class="mt-4 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-zinc-400">{{ $bio ?: __('Автор поки не додав опис.') }}</p>

                                <div class="mt-5 grid grid-cols-3 gap-2">
                                    <div class="rounded-2xl border border-white/10 bg-zinc-950/45 p-3 text-center">
                                        <p class="text-lg font-black text-white">{{ number_format($modelsCount) }}</p>
                                        <p class="text-[10px] uppercase tracking-wider text-zinc-500">{{ __('Моделі') }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-zinc-950/45 p-3 text-center">
                                        <p class="text-lg font-black text-white">{{ number_format($downloadsCount) }}</p>
                                        <p class="text-[10px] uppercase tracking-wider text-zinc-500">{{ __('Завантаження') }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-zinc-950/45 p-3 text-center">
                                        <p class="text-lg font-black text-white">{{ number_format($followersCount) }}</p>
                                        <p class="text-[10px] uppercase tracking-wider text-zinc-500">{{ __('Підписники') }}</p>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                                    <a href="{{ $author->profileUrl() }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-4 text-xs font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('Переглянути профіль') }}</a>
                                    <x-ui.follow-button :author="$author" :is-following="$isFollowing" :is-self="$isSelf" size="sm" class="justify-center" />
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">{{ $authors->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.marketplace>
