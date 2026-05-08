@php
    /** @var \App\Services\AdminStats $stats */
    $period = $stats->period();

    $usersMetric = $stats->metric('users');
    $productsMetric = $stats->metric('products');
    $publishedMetric = $stats->metric('products', 'published_at', fn ($q) => $q->whereNotNull('published_at')->where('status', 'published'));
    $ordersMetric = $stats->metric('orders');
    $paidOrdersMetric = $stats->metric('orders', 'paid_at', fn ($q) => $q->whereNotNull('paid_at'));
    $paymentsMetric = $stats->metric('payments', 'created_at', fn ($q) => $q->where('status', 'paid'));

    $revenueByCurrency = $stats->revenueByCurrency();
    $aovByCurrency = $stats->aovByCurrency();

    $signupsDaily = $stats->dailyCounts('users');
    $productsDaily = $stats->dailyCounts('products');
    $ordersDaily = $stats->dailyCounts('orders', 'paid_at', fn ($q) => $q->whereNotNull('paid_at'));

    $labels = [];
    $start = $stats->rangeStart();
    for ($i = 0; $i < $stats->days(); $i++) {
        $labels[] = $start->addDays($i)->format('d.m');
    }

    $moderationQueue = $stats->moderationQueue(5);
    $activity = $stats->activity(8);
    $topAuthors = $stats->topAuthors(5);
    $topCategories = $stats->topCategories(5);

    $failedJobs = $stats->failedJobsCount();
    $queuedJobs = $stats->queuedJobsCount();
    $publicBytes = $stats->storageBytes('public');
    $privateBytes = $stats->storageBytes('private');

    $iconUsers = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
    $iconBox = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
    $iconClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    $iconBag = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';
    $iconCard = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>';

    $iconForActivity = [
        'user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'box' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/></svg>',
        'bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    ];
@endphp

<x-layouts.admin
    :title="__('Dashboard')"
    :description="__('Огляд активності маркетплейсу за обраний період.')"
    active="dashboard"
>
    <x-slot:actions>
        <x-admin.period-tabs :current="$period" :options="[
            '7d' => __('Тиждень'),
            '30d' => __('30 днів'),
            '90d' => __('90 днів'),
        ]" />
        <a href="{{ route('admin.products') }}" class="inline-flex h-9 items-center gap-2 rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ __('До модерації') }}
            @if($pendingCount > 0)
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-zinc-950/30 px-1.5 text-[10px] font-bold">{{ $pendingCount }}</span>
            @endif
        </a>
    </x-slot:actions>

    {{-- KPI grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-admin.stat-card
            color="emerald"
            :label="__('Користувачі')"
            :value="number_format($usersMetric['total'])"
            :delta="$usersMetric['delta']"
            :trend="$usersMetric['trend']"
            :sparkline="$usersMetric['sparkline']"
            :icon="$iconUsers"
            :href="route('admin.users')"
            :helper="'+'.$usersMetric['value'].' '.__('за період')"
        />
        <x-admin.stat-card
            color="sky"
            :label="__('Моделі')"
            :value="number_format($productsMetric['total'])"
            :delta="$productsMetric['delta']"
            :trend="$productsMetric['trend']"
            :sparkline="$productsMetric['sparkline']"
            :icon="$iconBox"
            :href="route('admin.products')"
            :helper="$publishedMetric['total'].' '.__('опубліковано')"
        />
        <x-admin.stat-card
            color="amber"
            :label="__('Модерація')"
            :value="number_format($pendingCount)"
            :delta="null"
            trend="flat"
            :sparkline="$productsMetric['sparkline']"
            :icon="$iconClock"
            :href="route('admin.products')"
            :helper="__('очікують перевірки')"
        />
        <x-admin.stat-card
            color="violet"
            :label="__('Замовлення')"
            :value="number_format($ordersMetric['total'])"
            :delta="$paidOrdersMetric['delta']"
            :trend="$paidOrdersMetric['trend']"
            :sparkline="$paidOrdersMetric['sparkline']"
            :icon="$iconBag"
            :href="route('admin.orders')"
            :helper="$paidOrdersMetric['value'].' '.__('оплачено за період')"
        />
        <x-admin.stat-card
            color="rose"
            :label="__('Платежі')"
            :value="number_format($paymentsMetric['total'])"
            :delta="$paymentsMetric['delta']"
            :trend="$paymentsMetric['trend']"
            :sparkline="$paymentsMetric['sparkline']"
            :icon="$iconCard"
            :href="route('admin.payments')"
            :helper="$paymentsMetric['value'].' '.__('успішних транзакцій')"
        />
    </div>

    {{-- Revenue + Chart --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-[1.5fr_1fr]">
        <x-admin.section :title="__('Активність за період')" :description="__('Реєстрації, нові моделі та оплати по днях.')">
            @if(array_sum($signupsDaily) === 0 && array_sum($productsDaily) === 0 && array_sum($ordersDaily) === 0)
                <div class="grid h-[220px] place-items-center rounded-2xl border border-dashed border-white/10 bg-zinc-950/40 text-center text-sm text-zinc-500">
                    <div>
                        <p>{{ __('За цей період даних немає.') }}</p>
                        <p class="mt-1 text-xs">{{ __('Спробуйте інший період або запустіть seed.') }}</p>
                    </div>
                </div>
            @else
                <x-admin.chart
                    :height="220"
                    :labels="$labels"
                    :series="[
                        ['label' => __('Користувачі'), 'color' => 'emerald', 'data' => $signupsDaily],
                        ['label' => __('Моделі'), 'color' => 'sky', 'data' => $productsDaily],
                        ['label' => __('Оплати'), 'color' => 'violet', 'data' => $ordersDaily],
                    ]"
                />
            @endif
        </x-admin.section>

        <x-admin.section :title="__('Виручка')" :description="__('Сума оплачених платежів за період.')">
            @if(empty($revenueByCurrency))
                <div class="grid h-full min-h-[180px] place-items-center text-center text-sm text-zinc-500">
                    <div>
                        <p class="text-3xl font-black text-white">—</p>
                        <p class="mt-1 text-xs">{{ __('Поки немає оплачених транзакцій') }}</p>
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($revenueByCurrency as $currency => $total)
                        @php $aov = $aovByCurrency[$currency] ?? null; @endphp
                        <div class="rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                            <div class="flex items-baseline justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ $currency }}</p>
                                    <p class="mt-1 text-2xl font-black text-white">{{ number_format($total, 2, '.', ' ') }}</p>
                                </div>
                                @if($aov !== null)
                                    <div class="text-right">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('AOV') }}</p>
                                        <p class="mt-0.5 text-sm font-bold text-emerald-200">{{ number_format($aov, 2, '.', ' ') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
            @endforeach
        </div>
            @endif
        </x-admin.section>
    </div>

    {{-- Moderation queue + System health --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-[1.6fr_1fr]">
        <x-admin.section
            :title="__('Черга модерації')"
            :description="__('Pending моделі — підтвердьте або відхиліть прямо звідси.')"
            :href="route('admin.products')"
            :action="__('Усі моделі')"
            :padded="false"
        >
            @forelse($moderationQueue as $product)
                <div class="flex flex-col gap-4 border-b border-white/5 px-5 py-4 last:border-b-0 sm:flex-row sm:items-center">
                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-zinc-950/70 text-xs font-bold text-emerald-100">
                        @if($product->cover_path)
                            <img src="{{ Storage::disk('public')->url($product->cover_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            3D
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ $product->localized('title') ?: __('Без назви') }}</p>
                        <p class="mt-0.5 truncate text-xs text-zinc-500">
                            {{ $product->author?->name ?? '—' }} · {{ $product->display_price ?? '—' }} · {{ $product->created_at?->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="inline-flex h-8 items-center rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-semibold text-zinc-200 hover:bg-white/10">
                            {{ __('Переглянути') }}
                        </a>
                        <form method="POST" action="{{ route('admin.products.moderate', $product) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="published">
                            <button class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-emerald-300/30 bg-emerald-300/10 px-3 text-xs font-semibold text-emerald-100 transition hover:bg-emerald-300/15">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ __('Approve') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.moderate', $product) }}" onsubmit="return confirm('{{ __('Відхилити модель?') }}')">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="rejected">
                            <button class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-rose-300/30 bg-rose-300/10 px-3 text-xs font-semibold text-rose-100 transition hover:bg-rose-300/15">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                {{ __('Reject') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="grid place-items-center px-5 py-12 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-full bg-emerald-300/10 text-emerald-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-white">{{ __('Черга порожня') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Усі моделі перевірено.') }}</p>
                </div>
            @endforelse
        </x-admin.section>

        <x-admin.section :title="__('System health')" :description="__('Статус інфраструктури та сховища.')">
            <ul class="grid gap-3">
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="grid h-8 w-8 place-items-center rounded-lg {{ $failedJobs > 0 ? 'bg-rose-300/15 text-rose-100' : 'bg-emerald-300/15 text-emerald-100' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M21 12a9 9 0 1 1-6.2-8.55"/><polyline points="21 4 21 10 15 10"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ __('Failed jobs') }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ __('Невдалі задачі черги') }}</p>
                        </div>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $failedJobs > 0 ? 'bg-rose-300/15 text-rose-100' : 'bg-emerald-300/15 text-emerald-100' }}">{{ $failedJobs }}</span>
                </li>
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-300/15 text-sky-100">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ __('Queue jobs') }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ __('У черзі на виконання') }}</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $queuedJobs }}</span>
                </li>
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-violet-300/15 text-violet-100">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ __('Storage') }}</p>
                            <p class="truncate text-xs text-zinc-500">public + private</p>
                        </div>
                    </div>
                    <span class="text-right text-xs font-bold text-zinc-200">
                        {{ \App\Services\AdminStats::formatBytes($publicBytes + $privateBytes) }}
                    </span>
                </li>
                <li class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-300/15 text-amber-100">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ __('Стек') }}</p>
                            <p class="truncate text-xs text-zinc-500">PHP {{ PHP_VERSION }} · Laravel {{ app()->version() }}</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-emerald-300/15 px-2.5 py-0.5 text-xs font-bold text-emerald-100">OK</span>
                </li>
            </ul>
        </x-admin.section>
    </div>

    {{-- Activity timeline + Top lists --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-[1.4fr_1fr]">
        <x-admin.section :title="__('Активність')" :description="__('Останні дії на платформі.')" :padded="false">
            @forelse($activity as $event)
                @php $tint = $event['tint'] ?? 'emerald'; @endphp
                <div class="flex items-start gap-3 border-b border-white/5 px-5 py-3 last:border-b-0">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-{{ $tint }}-300/15 text-{{ $tint }}-100">
                        {!! $iconForActivity[$event['icon']] ?? $iconForActivity['user'] !!}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white">{{ $event['title'] }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ $event['description'] }}</p>
                    </div>
                    <span class="shrink-0 text-[11px] text-zinc-500">{{ $event['at']->diffForHumans() }}</span>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-zinc-500">{{ __('Подій поки немає.') }}</div>
            @endforelse
        </x-admin.section>

        <div class="grid gap-5">
            <x-admin.section :title="__('Топ авторів')" :description="__('За кількістю опублікованих моделей.')" :padded="false">
                @forelse($topAuthors as $author)
                    <div class="flex items-center gap-3 border-b border-white/5 px-5 py-3 last:border-b-0">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-300/15 text-xs font-bold text-emerald-100">
                            {{ mb_strtoupper(mb_substr($author->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ $author->name }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ $author->email }}</p>
                        </div>
                        <span class="rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $author->published_count }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-zinc-500">{{ __('Поки немає авторів.') }}</div>
                @endforelse
            </x-admin.section>

            <x-admin.section :title="__('Топ категорій')" :description="__('Найзаповненіші розділи каталогу.')" :padded="false">
                @forelse($topCategories as $category)
                    <div class="flex items-center gap-3 border-b border-white/5 px-5 py-3 last:border-b-0">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-sky-300/15 text-xs font-bold text-sky-100">
                            {{ mb_strtoupper(mb_substr($category->localized('name') ?: '?', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ $category->localized('name') ?: $category->slug }}</p>
                            <p class="truncate text-xs text-zinc-500">/{{ $category->slug }}</p>
                        </div>
                        <span class="rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $category->products_count }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-zinc-500">{{ __('Категорій поки немає.') }}</div>
                @endforelse
            </x-admin.section>
        </div>
    </div>
</x-layouts.admin>
