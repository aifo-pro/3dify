@php
    // Placeholder data used when DB is empty.
    $placeholderModels = [
        ['title' => __('Miniature Dragon'), 'subtitle' => __('Деталізована 32mm міньятюра для tabletop та dnd.'), 'price' => '180 грн', 'free' => false, 'tone' => 'emerald', 'icon' => 'dragon'],
        ['title' => __('Phone Stand'), 'subtitle' => __('Підставка для смартфона під будь-яким кутом.'), 'price' => null, 'free' => true, 'tone' => 'rose', 'icon' => 'phone'],
        ['title' => __('Desk Organizer'), 'subtitle' => __('Модульний органайзер для робочого стола.'), 'price' => '80 грн', 'free' => false, 'tone' => 'violet', 'icon' => 'organizer'],
        ['title' => __('Wall Hook'), 'subtitle' => __('Декоративний гачок із сучасним дизайном.'), 'price' => '60 грн', 'free' => false, 'tone' => 'sky', 'icon' => 'hook'],
    ];

    $placeholderFree = [
        ['title' => __('Phone Stand'), 'subtitle' => __('Безкоштовна підставка для смартфона.'), 'price' => null, 'free' => true, 'tone' => 'rose', 'icon' => 'phone'],
        ['title' => __('Wall Hook'), 'subtitle' => __('Простий настінний гачок під ключі.'), 'price' => null, 'free' => true, 'tone' => 'sky', 'icon' => 'hook'],
        ['title' => __('Cable Clip'), 'subtitle' => __('Затискач для кабелів на робочому місці.'), 'price' => null, 'free' => true, 'tone' => 'lime', 'icon' => 'cube'],
        ['title' => __('Earbuds Holder'), 'subtitle' => __('Тримач для навушників із кабельним каналом.'), 'price' => null, 'free' => true, 'tone' => 'amber', 'icon' => 'organizer'],
    ];

    // Static category tiles, used if there are < 6 in DB.
    $placeholderCategories = [
        ['name' => __('Мініатюри'), 'desc' => __('Tabletop, DnD, фентезі та sci-fi'), 'icon' => 'sword', 'count' => '120+', 'tone' => 'emerald'],
        ['name' => __('Інструменти'), 'desc' => __('Шаблони, гачки, тримачі, jig-и'), 'icon' => 'wrench', 'count' => '80+', 'tone' => 'sky'],
        ['name' => __('Декор'), 'desc' => __('Вази, рамки, фігурки, скульптури'), 'icon' => 'sparkle', 'count' => '64+', 'tone' => 'violet'],
        ['name' => __('Іграшки'), 'desc' => __('Дитячі іграшки, головоломки, ігри'), 'icon' => 'toy', 'count' => '45+', 'tone' => 'rose'],
        ['name' => __('Запчастини'), 'desc' => __('Заміна частин для побутової техніки'), 'icon' => 'gear', 'count' => '38+', 'tone' => 'amber'],
        ['name' => __('Архітектура'), 'desc' => __('Будівлі, ландшафт, інтерʼєр'), 'icon' => 'building', 'count' => '24+', 'tone' => 'lime'],
    ];

    $catIcon = function (string $name): string {
        return match ($name) {
            'sword' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><polyline points="14.5 17.5 3 6 3 3 6 3 17.5 14.5"/><line x1="13" y1="19" x2="19" y2="13"/><line x1="16" y1="16" x2="20" y2="20"/><line x1="19" y1="21" x2="21" y2="19"/></svg>',
            'wrench' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
            'sparkle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M12 3 9.5 9.5 3 12l6.5 2.5L12 21l2.5-6.5L21 12l-6.5-2.5z"/></svg>',
            'toy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><circle cx="12" cy="12" r="10"/><path d="M14.31 8 20.05 17.94"/><path d="M9.69 8h11.46"/><path d="M7.38 12 13.12 2.06"/><path d="M9.69 16 3.95 6.06"/><path d="M14.31 16H2.85"/><path d="M16.62 12 10.88 21.94"/></svg>',
            'gear' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'building' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="9" y1="6" x2="9" y2="6"/><line x1="9" y1="10" x2="9" y2="10"/><line x1="9" y1="14" x2="9" y2="14"/><line x1="15" y1="6" x2="15" y2="6"/><line x1="15" y1="10" x2="15" y2="10"/><line x1="15" y1="14" x2="15" y2="14"/><path d="M9 22v-4h6v4"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/></svg>',
        };
    };

    $catTone = [
        'emerald' => 'from-emerald-300/20 via-emerald-300/5 to-transparent',
        'sky' => 'from-sky-300/20 via-sky-300/5 to-transparent',
        'violet' => 'from-violet-300/20 via-violet-300/5 to-transparent',
        'rose' => 'from-rose-300/20 via-rose-300/5 to-transparent',
        'amber' => 'from-amber-300/20 via-amber-300/5 to-transparent',
        'lime' => 'from-lime-300/20 via-lime-300/5 to-transparent',
    ];

    $catText = [
        'emerald' => 'text-emerald-200',
        'sky' => 'text-sky-200',
        'violet' => 'text-violet-200',
        'rose' => 'text-rose-200',
        'amber' => 'text-amber-200',
        'lime' => 'text-lime-200',
    ];

    $accentTones = [
        'emerald' => 'border-emerald-300/30 bg-emerald-300/[0.08] text-emerald-200',
        'sky' => 'border-sky-300/30 bg-sky-300/[0.08] text-sky-200',
        'violet' => 'border-violet-300/30 bg-violet-300/[0.08] text-violet-200',
        'rose' => 'border-rose-300/30 bg-rose-300/[0.08] text-rose-200',
        'amber' => 'border-amber-300/30 bg-amber-300/[0.08] text-amber-200',
        'lime' => 'border-lime-300/30 bg-lime-300/[0.08] text-lime-200',
    ];

    $accentDelta = [
        'emerald' => 'text-emerald-300',
        'sky' => 'text-sky-300',
        'violet' => 'text-violet-300',
        'amber' => 'text-amber-300',
    ];

    $popularToShow = $popularProducts->isNotEmpty() ? $popularProducts : $featuredProducts;
    $hasFeaturedReal = $popularToShow->isNotEmpty() || $latestProducts->isNotEmpty();
    $hasFreeReal = $freeProducts->isNotEmpty();
    $hasCategoriesReal = $categories->isNotEmpty();
@endphp

<x-layouts.marketplace>
    {{-- =================================================================== --}}
    {{-- HERO                                                                  --}}
    {{-- =================================================================== --}}
    <section class="relative overflow-hidden">
        {{-- Background ambient --}}
        <div class="pointer-events-none absolute inset-0 -z-10 [mask-image:linear-gradient(to_bottom,#000,transparent_80%)]">
            <div class="absolute -left-32 top-12 h-96 w-96 rounded-full bg-emerald-500/15 blur-[120px]"></div>
            <div class="absolute right-0 top-32 h-80 w-80 rounded-full bg-sky-500/10 blur-[120px]"></div>
        </div>

        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:px-8 lg:py-20">
            {{-- Left: copy + CTA --}}
            <div class="flex flex-col">
                <div class="inline-flex items-center gap-2 self-start rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-200 backdrop-blur">
                    <span class="grid h-1.5 w-1.5 place-items-center rounded-full bg-emerald-400 ring-2 ring-emerald-400/30"></span>
                    {{ __('3Dify Marketplace') }}
                </div>

                <h1 class="mt-6 text-4xl font-black leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-[56px]">
                    {{ __('Преміальні') }}
                    <span class="whitespace-nowrap bg-gradient-to-r from-emerald-300 via-emerald-200 to-sky-300 bg-clip-text text-transparent">{{ __('3D-моделі') }}</span>
                    {{ __('для якісного друку') }}
                </h1>

                <p class="mt-5 max-w-xl text-base leading-7 text-zinc-300 sm:text-lg sm:leading-8">{{ __('Знаходьте STL, OBJ, GLB та 3MF файли, перевіряйте модель у браузері, купуйте безпечно та відкривайте завантаження одразу після оплати.') }}</p>

                {{-- Search --}}
                <form method="GET" action="{{ route('products.index') }}" class="mt-8 flex max-w-xl items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.05] p-1.5 shadow-xl shadow-black/30 backdrop-blur">
                    <span class="grid h-11 w-11 shrink-0 place-items-center text-zinc-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input
                        name="q"
                        placeholder="{{ __('Що друкуємо? dragon, phone stand, organizer...') }}"
                        class="h-11 min-w-0 flex-1 border-0 bg-transparent px-1 text-sm text-white placeholder:text-zinc-500 focus:outline-none focus:ring-0"
                    >
                    <button type="submit" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                        {{ __('Знайти') }}
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </form>

                {{-- Quick search chips --}}
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                    <span>{{ __('Популярне') }}:</span>
                    @foreach(['Dragon', 'Phone Stand', 'Vase', 'Cosplay', 'Cookie Cutter'] as $tag)
                        <a href="{{ route('products.index', ['q' => $tag]) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/[0.08] hover:text-emerald-100">{{ $tag }}</a>
                    @endforeach
                </div>

                {{-- CTAs --}}
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-ui.button :href="route('products.index')">
                        {{ __('Дивитися каталог') }}
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </x-ui.button>
                    <x-ui.button :href="auth()->check() ? route('author.products.create') : route('register')" variant="secondary">
                        {{ __('Стати автором') }}
                    </x-ui.button>
                </div>

                {{-- Trust strip --}}
                <div class="mt-10 flex flex-wrap items-center gap-x-7 gap-y-3 border-t border-white/5 pt-6">
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-300/10 text-emerald-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                        </span>
                        <div class="text-xs leading-5 text-zinc-300">
                            <p class="font-semibold">{{ number_format($stats['products']) }} {{ trans_choice('моделей|моделі|моделей', $stats['products']) }}</p>
                            <p class="text-zinc-500">{{ __('у каталозі') }}</p>
            </div>
                            </div>
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-sky-300/10 text-sky-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <div class="text-xs leading-5 text-zinc-300">
                            <p class="font-semibold">{{ $stats['authors'] }} {{ trans_choice('авторів|автор|авторів', $stats['authors']) }}</p>
                            <p class="text-zinc-500">{{ __('публікують моделі') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-300/10 text-emerald-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <div class="text-xs leading-5 text-zinc-300">
                            <p class="font-semibold">{{ __('Безпечні платежі') }}</p>
                            <p class="text-zinc-500">aifo.pro</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: marketplace preview mockup --}}
            <div class="relative">
                <x-ui.hero-preview />
            </div>
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- POPULAR CATEGORIES                                                    --}}
    {{-- =================================================================== --}}
    <section id="categories" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="__('Категорії')"
            :title="__('Популярні категорії')"
            :description="__('Швидко знайдіть моделі під ваш сценарій друку — від мініатюр до інженерних деталей.')"
            :href="route('products.index')"
            :action="__('Усі категорії')"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @if($hasCategoriesReal)
                @foreach($categories->take(6) as $category)
                    @php
                        $tone = ['emerald','sky','violet','rose','amber','lime'][$loop->index % 6];
                        $iconNames = ['sword','wrench','sparkle','toy','gear','building'];
                        $iconKey = $iconNames[$loop->index % 6];
                    @endphp
                    <a href="{{ route('categories.show', $category) }}" class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/15 transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:bg-white/[0.07]">
                        <div class="absolute inset-0 -z-10 bg-gradient-to-br {{ $catTone[$tone] }} opacity-60"></div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border border-white/10 bg-zinc-950/50 backdrop-blur {{ $catText[$tone] }}">
                                {!! $catIcon($iconKey) !!}
                            </div>
                            <span class="rounded-full border border-white/10 bg-zinc-950/60 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-300 backdrop-blur">{{ $category->products_count ?? '' }}{{ __('моделей') }}</span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-white group-hover:text-emerald-100">{{ $category->localized('name') }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-400">{{ $category->localized('description') ?: __('Добірка якісних моделей для 3D-друку.') }}</p>
                        <div class="mt-5 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-300 transition group-hover:gap-3">
                            {{ __('Перейти') }}
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </div>
                    </a>
                @endforeach
            @else
                @foreach($placeholderCategories as $cat)
                    <a href="{{ route('products.index') }}" class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/15 transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:bg-white/[0.07]">
                        <div class="absolute inset-0 -z-10 bg-gradient-to-br {{ $catTone[$cat['tone']] }} opacity-60"></div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border border-white/10 bg-zinc-950/50 backdrop-blur {{ $catText[$cat['tone']] }}">
                                {!! $catIcon($cat['icon']) !!}
                            </div>
                            <span class="rounded-full border border-white/10 bg-zinc-950/60 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-300 backdrop-blur">{{ $cat['count'] }}{{ __('моделей') }}</span>
                        </div>
                        <h3 class="mt-5 text-xl font-bold text-white group-hover:text-emerald-100">{{ $cat['name'] }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-400">{{ $cat['desc'] }}</p>
                        <div class="mt-5 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-300 transition group-hover:gap-3">
                            {{ __('Перейти') }}
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </div>
                    </a>
                @endforeach
            @endif
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- FEATURED MODELS                                                        --}}
    {{-- =================================================================== --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="__('Featured')"
            :title="__('Моделі, які виділяються')"
            :description="__('Добірка позицій з найбільшою активністю переглядів і завантажень.')"
            :href="route('products.index', ['sort' => 'popular'])"
            :action="__('Усі популярні')"
        />

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @if($popularToShow->isNotEmpty())
                @foreach($popularToShow->take(4) as $product)
                <x-ui.model-card :product="$product" />
                @endforeach
            @else
                @foreach($placeholderModels as $m)
                    <x-ui.placeholder-card
                        :title="$m['title']"
                        :subtitle="$m['subtitle']"
                        :price="$m['price']"
                        :free="$m['free']"
                        :tone="$m['tone']"
                        :icon="$m['icon']"
                    />
                @endforeach
            @endif
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- LATEST MODELS                                                          --}}
    {{-- =================================================================== --}}
    @if($latestProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <x-ui.section-heading
                :eyebrow="__('Свіже')"
                :title="__('Нові моделі')"
                :description="__('Свіжі завантаження авторів — готові до перевірки, покупки та друку.')"
                :href="route('products.index')"
                :action="__('Дивитися каталог')"
            />
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($latestProducts->take(4) as $product)
                    <x-ui.model-card :product="$product" />
                @endforeach
        </div>
    </section>
    @endif

    {{-- =================================================================== --}}
    {{-- HOW IT WORKS                                                           --}}
    {{-- =================================================================== --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="__('Як це працює')"
            :title="__('Від моделі до друку — три прості кроки')"
            :description="__('Дизайнери публікують, ми зберігаємо файли в безпеці, ви купуєте та одразу отримуєте доступ до завантаження.')"
        />

        <div class="relative grid gap-5 md:grid-cols-3">
            {{-- Connector line --}}
            <div class="pointer-events-none absolute inset-x-12 top-12 hidden h-px bg-gradient-to-r from-transparent via-emerald-300/30 to-transparent md:block"></div>

            @foreach([
                ['n' => '01', 'title' => __('Завантажте модель'), 'desc' => __('Автор додає STL/OBJ/GLB файли, опис, ціну та превʼю — все в одному акуратному кабінеті.'), 'icon' => 'upload', 'tone' => 'emerald'],
                ['n' => '02', 'title' => __('Перегляньте 3D preview'), 'desc' => __('Перед покупкою клієнт може покрутити модель у браузері — без сторонніх програм.'), 'icon' => 'eye', 'tone' => 'sky'],
                ['n' => '03', 'title' => __('Купуйте та друкуйте'), 'desc' => __('Оплата через aifo.pro, доступ до файлів відкривається миттєво — і ви друкуєте.'), 'icon' => 'print', 'tone' => 'violet'],
            ] as $step)
                <div class="relative rounded-3xl border border-white/10 bg-white/[0.04] p-7 shadow-xl shadow-black/20 backdrop-blur transition hover:-translate-y-1 hover:border-white/20">
                    <div class="flex items-start justify-between">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border {{ $accentTones[$step['tone']] }}">
                            @switch($step['icon'])
                                @case('upload')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    @break
                                @case('eye')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7"/><circle cx="12" cy="12" r="3"/></svg>
                                    @break
                                @case('print')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                    @break
                            @endswitch
                        </span>
                        <span class="font-mono text-xs font-bold tracking-wider text-zinc-600">{{ $step['n'] }}</span>
                    </div>
                    <h3 class="mt-6 text-lg font-bold text-white">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-400">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- FREE MODELS                                                            --}}
    {{-- =================================================================== --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[2rem] border border-emerald-300/20 bg-gradient-to-br from-emerald-300/[0.10] via-emerald-300/[0.04] to-transparent p-6 shadow-2xl shadow-emerald-950/30 sm:p-10">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-emerald-400/20 blur-[100px]"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-sky-400/15 blur-[100px]"></div>

            <x-ui.section-heading
                :eyebrow="__('Free')"
                :title="__('Спробуйте безкоштовно')"
                :description="__('Дайте сервісу шанс — оберіть free-модель, завантажте її та оцініть якість файлів і авторів.')"
                :href="route('products.index', ['free' => 1])"
                :action="__('Усі безкоштовні')"
            />

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @if($hasFreeReal)
                    @foreach($freeProducts->take(4) as $product)
                        <x-ui.model-card :product="$product" />
                    @endforeach
                @else
                    @foreach($placeholderFree as $m)
                        <x-ui.placeholder-card
                            :title="$m['title']"
                            :subtitle="$m['subtitle']"
                            :price="$m['price']"
                            :free="$m['free']"
                            :tone="$m['tone']"
                            :icon="$m['icon']"
                        />
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- WHY SELL ON 3DIFY                                                      --}}
    {{-- =================================================================== --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <x-ui.section-heading
            :eyebrow="__('Чому 3Dify')"
            :title="__('Все, що потрібно автору 3D-моделей')"
            :description="__('Зосередьтеся на дизайні моделей — рутину з оплатами, доставкою файлів та модерацією візьмемо на себе.')"
        />

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['title' => __('Захищені файли'), 'desc' => __('Source-файли не віддаються напряму — тільки по платних посиланнях зі сроком життя.'), 'icon' => 'lock', 'tone' => 'emerald'],
                ['title' => __('Готова SEO-вітрина'), 'desc' => __('Кожна модель отримує локалізовані мета-теги, схему та чисті URL для пошуку.'), 'icon' => 'search', 'tone' => 'sky'],
                ['title' => __('Кабінет автора'), 'desc' => __('Публікація, редагування, модерація, статистика переглядів і продажів.'), 'icon' => 'user', 'tone' => 'violet'],
                ['title' => __('Платежі та виплати'), 'desc' => __('Інтеграція з aifo.pro: безпечний checkout та прозора комісія платформи.'), 'icon' => 'card', 'tone' => 'amber'],
            ] as $f)
                <div class="group rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:bg-white/[0.07]">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl border {{ $accentTones[$f['tone']] }}">
                        @switch($f['icon'])
                            @case('lock')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                @break
                            @case('search')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                @break
                            @case('user')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                @break
                            @case('card')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                @break
                        @endswitch
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-white">{{ $f['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-400">{{ $f['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- TRUST / STATISTICS — real numbers only                                --}}
    {{-- =================================================================== --}}
    @php
        $statTiles = [
            [
                'n' => number_format($stats['products']),
                'label' => trans_choice('моделей у каталозі|модель у каталозі|моделей у каталозі', $stats['products']),
                'sub' => __('опубліковано на 3Dify'),
                'tone' => 'emerald',
                'icon' => 'cube',
            ],
            [
                'n' => number_format($stats['authors']),
                'label' => trans_choice('активних авторів|активний автор|активних авторів', $stats['authors']),
                'sub' => __('хоча б одна опублікована модель'),
                'tone' => 'sky',
                'icon' => 'users',
            ],
            [
                'n' => number_format($stats['categories']),
                'label' => trans_choice('категорій|категорія|категорій', $stats['categories']),
                'sub' => __('активних у каталозі'),
                'tone' => 'violet',
                'icon' => 'folder',
            ],
            [
                'n' => number_format($stats['paid_orders']),
                'label' => trans_choice('успішних покупок|успішна покупка|успішних покупок', $stats['paid_orders']),
                'sub' => __('оплачених замовлень'),
                'tone' => 'amber',
                'icon' => 'card',
            ],
        ];

        $hasAnyStats = ($stats['products'] + $stats['authors'] + $stats['categories'] + $stats['paid_orders']) > 0;
    @endphp

    @if($hasAnyStats)
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-4 rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30 sm:grid-cols-2 sm:p-8 lg:grid-cols-4">
                @foreach($statTiles as $stat)
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border {{ $accentTones[$stat['tone']] }}">
                            @switch($stat['icon'])
                                @case('cube')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                                    @break
                                @case('users')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    @break
                                @case('folder')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    @break
                                @case('card')
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                    @break
                            @endswitch
                        </span>
                        <div class="min-w-0">
                            <p class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ $stat['n'] }}</p>
                            <p class="mt-0.5 text-sm font-semibold text-zinc-200">{{ $stat['label'] }}</p>
                            <p class="text-xs text-zinc-500">{{ $stat['sub'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- =================================================================== --}}
    {{-- AUTHOR CTA                                                             --}}
    {{-- =================================================================== --}}
    <section id="authors" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-zinc-900 via-zinc-950 to-black p-6 shadow-2xl shadow-black/40 sm:p-12">
            <div class="pointer-events-none absolute -left-32 -top-24 h-72 w-72 rounded-full bg-emerald-500/20 blur-[120px]"></div>
            <div class="pointer-events-none absolute -right-32 -bottom-24 h-80 w-80 rounded-full bg-sky-500/15 blur-[120px]"></div>

            <div class="relative grid gap-10 md:grid-cols-[1.2fr_1fr] md:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-200">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 14.39 8.26 21 9 16 13.74 17.18 20.52 12 17.27 6.82 20.52 8 13.74 3 9 9.61 8.26z"/></svg>
                        {{ __('Для авторів') }}
                    </div>
                    <h2 class="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl">{{ __('Продавайте моделі як цифровий продукт') }}</h2>
                    <p class="mt-4 max-w-xl text-base leading-7 text-zinc-300">{{ __('Завантажте файли, опис, ціну та превʼю — 3Dify сам подбає про каталог, безпечне зберігання та доступ після покупки.') }}</p>

                    <ul class="mt-7 grid gap-3 sm:grid-cols-2">
                        @foreach([
                            __('STL · OBJ · GLB · 3MF · ZIP'),
                            __('Модерація draft → published'),
                            __('Прозора комісія aifo.pro'),
                            __('Локалізовані картки UK / EN'),
                        ] as $point)
                            <li class="inline-flex items-center gap-2 text-sm text-zinc-200">
                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-400/20 text-emerald-200">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-ui.button :href="auth()->check() ? route('author.products.create') : route('register')">
                            {{ __('Опублікувати модель') }}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </x-ui.button>
                        <x-ui.button :href="route('products.index')" variant="secondary">{{ __('Подивитися приклади') }}</x-ui.button>
                    </div>
                </div>

                {{-- Mock dashboard preview --}}
                <div class="relative">
                    <div class="rounded-3xl border border-white/10 bg-zinc-950/60 p-5 shadow-2xl shadow-black/40 backdrop-blur">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('Кабінет автора') }}</p>
                            <span class="inline-flex items-center gap-1 rounded-full border border-amber-300/30 bg-amber-300/[0.10] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-200">
                                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 14.39 8.26 21 9 16 13.74 17.18 20.52 12 17.27 6.82 20.52 8 13.74 3 9 9.61 8.26z"/></svg>
                                {{ __('приклад') }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('У вашому кабінеті') }}</p>
                                <ul class="mt-3 grid gap-2 text-sm text-zinc-200">
                                    <li class="inline-flex items-center gap-2">
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/20 text-emerald-200"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                        {{ __('Публікація моделей у кілька кліків') }}
                                    </li>
                                    <li class="inline-flex items-center gap-2">
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/20 text-emerald-200"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                        {{ __('Статистика переглядів і завантажень') }}
                                    </li>
                                    <li class="inline-flex items-center gap-2">
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/20 text-emerald-200"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                        {{ __('Безпечні платежі та виплати через aifo.pro') }}
                                    </li>
                                    <li class="inline-flex items-center gap-2">
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-400/20 text-emerald-200"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                        {{ __('Модерація draft → published') }}
                                    </li>
                                </ul>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('Платформа сьогодні') }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-lg font-black text-white">{{ number_format($stats['products']) }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ trans_choice('моделей у каталозі|модель у каталозі|моделей у каталозі', $stats['products']) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-lg font-black text-white">{{ number_format($stats['authors']) }}</p>
                                        <p class="text-[10px] text-zinc-500">{{ trans_choice('авторів публікують|автор публікує|авторів публікують', $stats['authors']) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketplace>
