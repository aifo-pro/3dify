@php
    $money = fn ($amount, $cur = 'UAH') => number_format((float) $amount, 2, '.', ' ') . ' ' . ($cur ?: 'UAH');
    $tone = fn ($tx) => $tx->type === \App\Models\AccountBalanceTransaction::TYPE_CREDIT ? 'text-emerald-200' : 'text-amber-200';
@endphp

<x-layouts.marketplace :seo-title="__('Мій баланс') . ' · 3Dify'">
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30 sm:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <x-ui.badge>{{ __('Кабінет') }}</x-ui.badge>
                    <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Мій баланс') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">
                        {{ __('Тут видно всі повернення, списання при покупках і зарезервовані суми. Баланс можна використати під час наступної покупки моделі.') }}
                    </p>
                </div>
                <a href="{{ route('products.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
                    {{ __('Перейти в каталог') }}
                </a>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl border border-emerald-300/25 bg-emerald-400/[0.08] p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-emerald-200">{{ __('Доступно') }}</p>
                    <p class="mt-3 text-3xl font-black text-white">{{ $money($totals['available'], $currency) }}</p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-zinc-950/50 p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('Зараховано') }}</p>
                    <p class="mt-3 text-3xl font-black text-white">{{ $money($totals['credited'], $currency) }}</p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-zinc-950/50 p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('Використано') }}</p>
                    <p class="mt-3 text-3xl font-black text-white">{{ $money($totals['spent'], $currency) }}</p>
                </div>
                <div class="rounded-3xl border border-amber-300/20 bg-amber-300/[0.06] p-5">
                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-amber-200">{{ __('У резерві') }}</p>
                    <p class="mt-3 text-3xl font-black text-white">{{ $money($totals['reserved'], $currency) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-[2rem] border border-white/10 bg-zinc-950/70 p-4 shadow-2xl shadow-black/25 sm:p-5">
            <div class="flex items-center justify-between gap-4 px-2 py-2">
                <div>
                    <h2 class="text-xl font-black text-white">{{ __('Історія балансу') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Нові операції зверху.') }}</p>
                </div>
            </div>

            <div class="mt-3 grid gap-2">
                @forelse($transactions as $tx)
                    <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4 transition hover:border-emerald-300/25 hover:bg-white/[0.055]">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-white/10 bg-zinc-950/50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-zinc-300">{{ __($tx->type) }}</span>
                                    <span class="rounded-full border border-white/10 bg-zinc-950/50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-zinc-300">{{ __($tx->status) }}</span>
                                </div>
                                <p class="mt-2 text-sm font-bold text-white">{{ $tx->description ?: __('Операція балансу') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $tx->created_at?->translatedFormat('d M Y · H:i') }}
                                    @if($tx->order)
                                        · {{ $tx->order->number }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xl font-black {{ $tone($tx) }}">{{ $tx->type === 'credit' ? '+' : '-' }}{{ $money($tx->amount, $tx->currency) }}</p>
                                @if($tx->order)
                                    <a href="{{ route('checkout.success', $tx->order) }}" class="mt-1 inline-flex text-xs font-bold text-emerald-200 hover:text-emerald-100">{{ __('Відкрити замовлення') }}</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state :title="__('Операцій ще немає')" :description="__('Повернення або списання з балансу зʼявляться тут автоматично.')" />
                @endforelse
            </div>

            <div class="mt-6">{{ $transactions->links() }}</div>
        </div>
    </section>
</x-layouts.marketplace>
