<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Hero --}}
        <header class="mb-8">
            <x-ui.badge>{{ __('Каталог 3Dify') }}</x-ui.badge>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('3D-моделі для друку, прототипів і декору') }}</h1>
            <p class="mt-3 max-w-3xl text-zinc-400">{{ __('Фільтруйте за категорією, ціною, ліцензією, форматом файлів. Зберігайте улюблені моделі та читайте відгуки покупців.') }}</p>
        </header>

        {{-- Top toolbar (search + sort) --}}
        <form method="GET" id="catalog-filters" class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.04] p-2 shadow-xl shadow-black/20">
            <div class="relative flex-1 min-w-[200px]">
                <input name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Пошук моделей, форматів або тем') }}" class="h-11 w-full rounded-xl border border-white/10 bg-zinc-950/60 pl-10 pr-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <select name="sort" onchange="this.form.submit()" class="h-11 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                <option value="latest" @selected($filters['sort'] === 'latest' || $filters['sort'] === '')>{{ __('Нові спочатку') }}</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>{{ __('Старіші спочатку') }}</option>
                <option value="popular" @selected($filters['sort'] === 'popular')>{{ __('Популярні') }}</option>
                <option value="downloads" @selected($filters['sort'] === 'downloads')>{{ __('Найбільше завантажень') }}</option>
                <option value="price_asc" @selected($filters['sort'] === 'price_asc')>{{ __('Дешевші') }}</option>
                <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('Дорожчі') }}</option>
            </select>
            <button class="h-11 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 transition hover:bg-emerald-300">{{ __('Шукати') }}</button>
            {{-- Re-emit hidden filter values so they survive search submit --}}
            @foreach((array) $filters['license'] as $val)<input type="hidden" name="license[]" value="{{ $val }}">@endforeach
            @foreach((array) $filters['format'] as $val)<input type="hidden" name="format[]" value="{{ $val }}">@endforeach
            @if($filters['category'])<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
            @if($filters['tag'])<input type="hidden" name="tag" value="{{ $filters['tag'] }}">@endif
            @if($filters['free'])<input type="hidden" name="free" value="1">@endif
            @if($filters['min_price'] !== null)<input type="hidden" name="min_price" value="{{ $filters['min_price'] }}">@endif
            @if($filters['max_price'] !== null)<input type="hidden" name="max_price" value="{{ $filters['max_price'] }}">@endif
        </form>

        {{-- Active filter chips --}}
        @php
            $hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['tag'] !== '' || $filters['free'] || $filters['license'] || $filters['format'] || $filters['min_price'] !== null || $filters['max_price'] !== null || ($filters['sort'] !== '' && $filters['sort'] !== 'latest');
        @endphp
        @if($hasFilters)
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ __('Активні фільтри') }}:</span>
                @if($filters['q'])
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs text-zinc-200">
                        {{ __('Запит') }}: <strong>{{ $filters['q'] }}</strong>
                        <a href="{{ route('products.index', array_merge(request()->except('q', 'page'))) }}" class="text-zinc-500 hover:text-rose-300">×</a>
                    </span>
                @endif
                @if($filters['category'])
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-3 py-1 text-xs text-emerald-100">
                        {{ optional($categories->firstWhere('slug', $filters['category']))->localized('name') ?? $filters['category'] }}
                        <a href="{{ route('products.index', array_merge(request()->except('category', 'page'))) }}" class="text-emerald-200/60 hover:text-rose-300">×</a>
                    </span>
                @endif
                @if($filters['free'])
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-3 py-1 text-xs text-emerald-100">
                        {{ __('Безкоштовні') }}
                        <a href="{{ route('products.index', array_merge(request()->except('free', 'page'))) }}" class="text-emerald-200/60 hover:text-rose-300">×</a>
                    </span>
                @endif
                @foreach((array) $filters['license'] as $slug)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs text-zinc-200">
                        {{ __('Ліцензія') }}: {{ $slug }}
                    </span>
                @endforeach
                @foreach((array) $filters['format'] as $ext)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs text-zinc-200 uppercase">
                        {{ $ext }}
                    </span>
                @endforeach
                @if($filters['min_price'] !== null || $filters['max_price'] !== null)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-xs text-zinc-200">
                        {{ __('Ціна') }}:
                        {{ $filters['min_price'] !== null ? number_format($filters['min_price'], 0).' грн' : '—' }}
                        … {{ $filters['max_price'] !== null ? number_format($filters['max_price'], 0).' грн' : '—' }}
                    </span>
                @endif
                <a href="{{ route('products.index') }}" class="ml-auto inline-flex h-8 items-center gap-1 rounded-full border border-rose-300/30 bg-rose-300/[0.08] px-3 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.14]">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    {{ __('Скинути все') }}
                </a>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)]">
            {{-- Sidebar facets --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <form method="GET" class="grid gap-4">
                    @if($filters['q'])<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
                    @if($filters['sort'] && $filters['sort'] !== 'latest')<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif

                    {{-- Free toggle --}}
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <label class="flex cursor-pointer items-center justify-between gap-3">
                            <span class="text-sm font-bold text-white">{{ __('Тільки безкоштовні') }}</span>
                            <input type="checkbox" name="free" value="1" @checked($filters['free']) onchange="this.form.submit()" class="h-5 w-9 cursor-pointer appearance-none rounded-full bg-white/10 transition checked:bg-emerald-400">
                        </label>
                    </div>

                    {{-- Category --}}
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Категорія') }}</p>
                        <select name="category" onchange="this.form.submit()" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                            <option value="">{{ __('Усі') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->slug }}" @selected($filters['category'] === $cat->slug)>{{ $cat->localized('name') }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- License --}}
                    @if($licenses->isNotEmpty())
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Ліцензія') }}</p>
                            <div class="grid gap-1.5 max-h-56 overflow-y-auto pr-1">
                                @foreach($licenses as $lic)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1 text-xs text-zinc-300 hover:bg-white/[0.05]">
                                        <input type="checkbox" name="license[]" value="{{ $lic->slug }}" @checked(in_array($lic->slug, (array) $filters['license'], true)) onchange="this.form.submit()" class="h-3.5 w-3.5 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300/40">
                                        <span class="truncate">{{ $lic->localized('name') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Format --}}
                    @if($availableFormats->isNotEmpty())
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Формат файлу') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($availableFormats as $fmt)
                                    @php $active = in_array($fmt->extension, (array) $filters['format'], true); @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="format[]" value="{{ $fmt->extension }}" @checked($active) onchange="this.form.submit()" class="peer hidden">
                                        <span class="inline-flex h-7 items-center gap-1.5 rounded-full border px-2.5 text-[11px] font-bold uppercase tracking-wider transition peer-checked:border-emerald-300/40 peer-checked:bg-emerald-300/[0.10] peer-checked:text-emerald-100 {{ $active ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-300 hover:border-white/20' }}">
                                            {{ $fmt->extension }}
                                            <span class="rounded-full bg-white/10 px-1 text-[9px] text-zinc-400">{{ $fmt->c }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Price range --}}
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Ціна, грн') }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="relative">
                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-emerald-300/70">₴</span>
                                <input type="number" name="min_price" min="0" step="0.5" value="{{ $filters['min_price'] }}" placeholder="{{ __('від') }}" class="h-9 w-full rounded-xl border border-white/10 bg-zinc-950/60 pl-6 pr-2 font-mono text-xs tabular-nums text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/30">
                            </div>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-emerald-300/70">₴</span>
                                <input type="number" name="max_price" min="0" step="0.5" value="{{ $filters['max_price'] }}" placeholder="{{ __('до') }}" class="h-9 w-full rounded-xl border border-white/10 bg-zinc-950/60 pl-6 pr-2 font-mono text-xs tabular-nums text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/30">
                            </div>
                        </div>
                        <button type="submit" class="mt-2 inline-flex h-8 w-full items-center justify-center rounded-xl bg-emerald-400 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Застосувати') }}</button>
                    </div>

                @if($hasFilters)
                    <a href="{{ route('products.index') }}" class="inline-flex h-9 items-center justify-center rounded-xl border border-rose-300/30 bg-rose-300/[0.06] text-xs font-bold text-rose-200 hover:bg-rose-300/[0.10]">
                        {{ __('Скинути всі фільтри') }}
                    </a>
                @endif
                </form>

                @auth
                    @if($hasFilters)
                        <form method="POST" action="{{ route('saved-searches.store') }}" class="mt-4 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.06] p-4">
                            @csrf
                            <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-emerald-300">{{ __('Зберегти цей пошук') }}</p>
                            <input type="text" name="name" required maxlength="100" placeholder="{{ __('Наприклад: Free PETG STL') }}" class="h-9 w-full rounded-lg border border-white/10 bg-zinc-950/60 px-2.5 text-xs text-white placeholder:text-zinc-500 focus:border-emerald-300">
                            @foreach((array) $filters['license'] as $val)<input type="hidden" name="filters[license][]" value="{{ $val }}">@endforeach
                            @foreach((array) $filters['format'] as $val)<input type="hidden" name="filters[format][]" value="{{ $val }}">@endforeach
                            @if($filters['q'])<input type="hidden" name="filters[q]" value="{{ $filters['q'] }}">@endif
                            @if($filters['category'])<input type="hidden" name="filters[category]" value="{{ $filters['category'] }}">@endif
                            @if($filters['tag'])<input type="hidden" name="filters[tag]" value="{{ $filters['tag'] }}">@endif
                            @if($filters['free'])<input type="hidden" name="filters[free]" value="1">@endif
                            @if($filters['min_price'] !== null)<input type="hidden" name="filters[min_price]" value="{{ $filters['min_price'] }}">@endif
                            @if($filters['max_price'] !== null)<input type="hidden" name="filters[max_price]" value="{{ $filters['max_price'] }}">@endif
                            @if($filters['sort'] && $filters['sort'] !== 'latest')<input type="hidden" name="filters[sort]" value="{{ $filters['sort'] }}">@endif
                            <label class="mt-2 flex items-center gap-2 text-[11px] text-zinc-300">
                                <input type="checkbox" name="notify_email" value="1" class="h-3.5 w-3.5 rounded border-white/20 bg-zinc-950 text-emerald-400">
                                {{ __('Email про нові моделі') }}
                            </label>
                            <button class="mt-2 inline-flex h-9 w-full items-center justify-center rounded-lg bg-emerald-400 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Зберегти') }}</button>
                        </form>
                    @endif
                @endauth
            </aside>

            {{-- Results --}}
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-zinc-400">{{ __('Знайдено') }}: <strong class="text-white">{{ $products->total() }}</strong></p>
                </div>
                @if($products->isNotEmpty())
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach($products as $product)
                            <x-ui.model-card :product="$product" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @else
                    <x-ui.empty-state :title="__('Нічого не знайдено')" :description="__('Спробуйте змінити запит, прибрати частину фільтрів або перейти до всього каталогу.')" :href="route('products.index')" :action="__('Очистити фільтри')" />
                @endif
            </div>
        </div>
    </section>
</x-layouts.marketplace>
