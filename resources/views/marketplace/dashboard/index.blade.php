@php
    $user = auth()->user();
@endphp

<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- ============================================================== --}}
        {{-- HERO                                                            --}}
        {{-- ============================================================== --}}
        <header class="mb-8 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <x-ui.badge>{{ __('Кабінет') }}</x-ui.badge>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('Привіт') }}, {{ $user->name }}!</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-zinc-400">{{ __('Покупки, продажі, моделі та підписки — все в одному місці.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('products.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-white/10 bg-white/[0.05] px-4 text-sm font-bold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.10]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    {{ __('Каталог') }}
                </a>
                <a href="{{ route('author.products.create') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Опублікувати модель') }}
                </a>
            </div>
        </header>

        {{-- ============================================================== --}}
        {{-- KPI STRIP                                                       --}}
        {{-- ============================================================== --}}
        <div class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="#purchases" class="group rounded-2xl border border-white/10 bg-gradient-to-br from-emerald-500/[0.10] to-transparent p-5 transition hover:border-emerald-300/40">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Куплено') }}</p>
                    <svg class="h-4 w-4 text-emerald-300/60 group-hover:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['purchases_count'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-400">{{ number_format($stats['purchases_total'], 2) }} EUR {{ __('загалом') }}</p>
            </a>

            <a href="{{ route('author.products.index') }}" class="group rounded-2xl border border-white/10 bg-gradient-to-br from-sky-500/[0.10] to-transparent p-5 transition hover:border-sky-300/40">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-300">{{ __('Моїх моделей') }}</p>
                    <svg class="h-4 w-4 text-sky-300/60 group-hover:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['models_count'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-400">{{ $stats['models_published'] }} {{ __('опубліковано') }}</p>
            </a>

            <a href="{{ route('author.analytics') }}" class="group rounded-2xl border border-white/10 bg-gradient-to-br from-amber-500/[0.10] to-transparent p-5 transition hover:border-amber-300/40">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-300">{{ __('Продажі') }}</p>
                    <svg class="h-4 w-4 text-amber-300/60 group-hover:text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['sales_count'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-400">{{ number_format($stats['sales_total'], 2) }} EUR</p>
            </a>

            <a href="{{ route('wishlist.index') }}" class="group rounded-2xl border border-white/10 bg-gradient-to-br from-rose-500/[0.10] to-transparent p-5 transition hover:border-rose-300/40">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-300">{{ __('Спільнота') }}</p>
                    <svg class="h-4 w-4 text-rose-300/60 group-hover:text-rose-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['followers_count'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-400">{{ $stats['following_count'] }} {{ __('підписок') }} · {{ $stats['wishlist_count'] }} ❤</p>
            </a>
        </div>

        {{-- ============================================================== --}}
        {{-- MAIN GRID                                                       --}}
        {{-- ============================================================== --}}
        <div class="grid gap-6 xl:grid-cols-12 xl:items-start">

            {{-- ====================== PURCHASES =========================== --}}
            <article id="purchases" class="min-w-0 rounded-3xl border border-white/10 bg-white/[0.03] p-1 xl:col-span-7">
                <div class="min-w-0 rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-5 sm:p-6">
                    <header class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ __('Останні покупки') }}</h2>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ __('Скачуйте файли або відкривайте у слайсері.') }}</p>
                        </div>
                        @if($orders->isNotEmpty())
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] font-bold text-zinc-300">{{ $orders->count() }}</span>
                        @endif
                    </header>

                    @if($orders->isEmpty())
                        <x-ui.empty-state :title="__('Покупок ще немає')" :description="__('Перейдіть у каталог і знайдіть першу модель для друку.')" :href="route('products.index')" :action="__('В каталог')" />
                    @else
                        <ul class="space-y-3">
                            @foreach($orders->take(8) as $order)
                                <li class="rounded-2xl border border-white/10 bg-zinc-950/40 p-4 transition hover:border-white/20">
                                    {{-- Order header --}}
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-white/5 pb-3">
                                        <div class="flex items-center gap-2">
                                            <x-ui.status :status="$order->status" size="xs" />
                                            <code class="font-mono text-[11px] text-zinc-500">{{ $order->number }}</code>
                                        </div>
                                        <strong class="text-sm font-black text-white">{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</strong>
                                    </div>

                                    {{-- Items --}}
                                    @if($order->items->isNotEmpty())
                                        <div class="space-y-2">
                                            @foreach($order->items as $item)
                                                @php $itemProduct = $item->product; @endphp
                                                @if($itemProduct)
                                                    @php
                                                        $coverUrl = $itemProduct->cover_path && \Storage::disk('public')->exists($itemProduct->cover_path)
                                                            ? \Storage::disk('public')->url($itemProduct->cover_path)
                                                            : null;
                                                    @endphp
                                                    <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-2.5">
                                                        {{-- Cover --}}
                                                        <a href="{{ route('products.show', $itemProduct) }}" class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-white/10 bg-zinc-950">
                                                            @if($coverUrl)
                                                                <img src="{{ $coverUrl }}" alt="" class="h-full w-full object-cover">
                                                            @else
                                                                <span class="text-[10px] font-bold text-emerald-200">3D</span>
                                                            @endif
                                                        </a>

                                                        {{-- Title + price --}}
                                                        <div class="min-w-0 flex-1">
                                                            <a href="{{ route('products.show', $itemProduct) }}" class="block truncate text-sm font-semibold text-white hover:text-emerald-200">{{ $itemProduct->localized('title') }}</a>
                                                            <p class="mt-0.5 text-[11px] text-zinc-500">{{ number_format((float) $item->price, 2) }} {{ $item->currency }} · {{ $itemProduct->author->name }}</p>
                                                        </div>

                                                        {{-- Action --}}
                                                        @if($order->status === 'paid')
                                                            <button
                                                                type="button"
                                                                data-download-trigger
                                                                data-download-url="{{ route('products.download-options', $itemProduct) }}"
                                                                data-download-title="{{ $itemProduct->localized('title') }}"
                                                                title="{{ __('Скачати / друкувати') }}"
                                                                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-emerald-400 px-3 text-xs font-bold text-zinc-950 shadow shadow-emerald-500/25 transition hover:bg-emerald-300"
                                                            >
                                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                                <span class="hidden sm:inline">{{ __('Скачати') }}</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Order footer (refund link) --}}
                                    @if($order->status === 'paid' && ($order->updated_at ?? $order->created_at)?->diffInDays(now()) <= 14)
                                        <div class="mt-3 flex items-center justify-end border-t border-white/5 pt-2">
                                            <a href="{{ route('refunds.index') }}" class="text-[10px] text-zinc-500 hover:text-rose-200">{{ __('Запросити повернення') }}</a>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>

            {{-- ============== RIGHT COLUMN: MY MODELS + SALES ============= --}}
            <div class="grid min-w-0 gap-5 xl:col-span-5">
                {{-- My models --}}
                <article class="min-w-0 rounded-3xl border border-white/10 bg-white/[0.03] p-1">
                    <div class="min-w-0 rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-5 sm:p-6">
                        <header class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-white">{{ __('Мої моделі') }}</h2>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ __('Останні чернетки та публікації.') }}</p>
                            </div>
                            <a href="{{ route('author.products.index') }}" class="text-xs font-bold text-emerald-200 hover:text-emerald-100">{{ __('Усі →') }}</a>
                        </header>

                        @if($products->isEmpty())
                            <x-ui.empty-state :title="__('Опублікуйте першу модель')" :description="__('Wizard допоможе пройти усі кроки.')" :href="route('author.products.create')" :action="__('Створити')" />
                        @else
                            <ul class="space-y-2">
                                @foreach($products as $p)
                                    <li>
                                        <a href="{{ route('author.products.edit', $p) }}" class="flex min-w-0 items-center gap-3 rounded-xl border border-white/10 bg-zinc-950/40 p-3 transition hover:border-emerald-300/40 hover:bg-zinc-950/60">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-white/10 bg-zinc-900 text-[10px] font-bold text-emerald-200">3D</span>
                                            <div class="min-w-0 flex-1 overflow-hidden">
                                                <p class="truncate text-sm font-semibold text-white">{{ $p->localized('title') }}</p>
                                                <p class="text-[11px] text-zinc-500">{{ $p->display_price }} · {{ $p->views_count ?? 0 }} {{ __('переглядів') }}</p>
                                            </div>
                                            <x-ui.status :status="$p->status" size="xs" :icon="false" class="hidden shrink-0 sm:inline-flex" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </article>

                {{-- Recent sales --}}
                <article class="min-w-0 rounded-3xl border border-white/10 bg-white/[0.03] p-1">
                    <div class="min-w-0 rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-5 sm:p-6">
                        <header class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-white">{{ __('Останні продажі') }}</h2>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ __('Що купили з ваших моделей.') }}</p>
                            </div>
                            <a href="{{ route('author.payouts') }}" class="text-xs font-bold text-emerald-200 hover:text-emerald-100">{{ __('Виплати →') }}</a>
                        </header>

                        @if($sales->isEmpty())
                            <x-ui.empty-state :title="__('Поки тиша')" :description="__('Якісне фото, точний опис і безкоштовний тестовий профіль допоможуть зробити перший продаж.')" />
                        @else
                            <ul class="space-y-2">
                                @foreach($sales as $sale)
                                    <li class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-3 rounded-xl border border-white/10 bg-zinc-950/40 p-3 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center">
                                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-emerald-300/30 bg-emerald-300/[0.08] text-emerald-200">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        </div>
                                        <div class="min-w-0 overflow-hidden">
                                            @if($sale->product)
                                                <a href="{{ route('products.show', $sale->product) }}" class="block truncate text-sm font-semibold text-white hover:text-emerald-200">{{ $sale->product->localized('title') }}</a>
                                            @else
                                                <p class="text-sm italic text-zinc-500">{{ __('модель видалено') }}</p>
                                            @endif
                                            <p class="text-[11px] text-zinc-500">{{ $sale->created_at?->translatedFormat('d M, H:i') }}</p>
                                        </div>
                                        <strong class="col-span-2 justify-self-end text-sm font-black text-emerald-200 sm:col-span-1">+{{ number_format((float) $sale->price, 2) }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </article>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- SUBSCRIPTIONS                                                    --}}
        {{-- ============================================================== --}}
        <article class="mt-6 rounded-3xl border border-white/10 bg-white/[0.03] p-1">
            <div class="rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-5 sm:p-6">
                <header class="mb-5 flex flex-wrap items-end justify-between gap-3 border-b border-white/5 pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">{{ __('Мої підписки') }}</h2>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ __('Автори, на оновлення яких ви підписані.') }}</p>
                    </div>
                    <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px] font-bold text-zinc-300">{{ $following->count() }}</span>
                </header>

                @if($following->isEmpty())
                    <div class="grid place-items-center rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-10 text-center">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-300/[0.10] text-emerald-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m22 11-3-3-3 3"/><path d="M19 8v6"/></svg>
                        </span>
                        <h3 class="mt-3 text-base font-bold text-white">{{ __('Поки немає підписок') }}</h3>
                        <p class="mt-1 max-w-md text-xs leading-5 text-zinc-400">{{ __('Підпишіться на авторів, щоб не пропустити нові моделі.') }}</p>
                        <a href="{{ route('products.index') }}" class="mt-3 inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Знайти авторів') }}</a>
                    </div>
                @else
                    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($following as $author)
                            <li class="flex items-center gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4 transition hover:border-emerald-300/30">
                                <a href="{{ $author->profileUrl() }}" class="shrink-0">
                                    @if($author->avatarUrl())
                                        <img src="{{ $author->avatarUrl() }}" alt="" class="h-12 w-12 rounded-2xl border border-white/10 object-cover">
                                    @else
                                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-emerald-300 to-emerald-500 text-base font-black text-zinc-950">{{ mb_strtoupper(mb_substr($author->name, 0, 1)) }}</span>
                                    @endif
                                </a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $author->profileUrl() }}" class="flex items-center gap-1.5 truncate text-sm font-bold text-white hover:text-emerald-200">
                                        <span class="truncate">{{ $author->name }}</span>
                                        <x-ui.verified-badge :user="$author" size="xs" :show-label="false" />
                                    </a>
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-500">
                                        {{ $author->products_count }} {{ __('моделей') }}
                                        ·
                                        {{ $author->followers()->count() }} {{ __('підписників') }}
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('authors.unfollow', ['user' => $author->username ?: $author->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="{{ __('Відписатися') }}" class="grid h-8 w-8 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-rose-300/40 hover:bg-rose-300/[0.10] hover:text-rose-100">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </article>

        {{-- ============================================================== --}}
        {{-- QUICK LINKS                                                     --}}
        {{-- ============================================================== --}}
        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['href' => route('wishlist.index'), 'icon' => 'heart', 'title' => __('Список бажань'), 'desc' => __('Збережені моделі.')],
                ['href' => route('saved-searches.index'), 'icon' => 'search', 'title' => __('Збережені пошуки'), 'desc' => __('Сповіщення про нові моделі.')],
                ['href' => route('printers.index'), 'icon' => 'printer', 'title' => __('Мої принтери'), 'desc' => __('Перевірка сумісності.')],
                ['href' => route('two-factor.show'), 'icon' => 'shield', 'title' => __('Безпека (2FA)'), 'desc' => __('Двофакторний захист.')],
            ] as $link)
                <a href="{{ $link['href'] }}" class="group flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 transition hover:-translate-y-0.5 hover:border-emerald-300/30 hover:bg-white/[0.06]">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-300/[0.10] text-emerald-200 transition group-hover:bg-emerald-300/[0.16]">
                        @switch($link['icon'])
                            @case('heart')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>@break
                            @case('search')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>@break
                            @case('printer')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>@break
                            @case('shield')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>@break
                        @endswitch
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-white">{{ $link['title'] }}</p>
                        <p class="truncate text-[11px] text-zinc-500">{{ $link['desc'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Download / slicer modal (single instance, shared by all download triggers above) --}}
    <x-ui.download-modal />
</x-layouts.marketplace>
