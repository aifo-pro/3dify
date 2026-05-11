@php
    use Illuminate\Support\Facades\Storage;

    $formatMoney = fn ($amount, $currency = 'UAH') => number_format((float) $amount, 2, '.', ' ') . ' ' . ($currency ?: 'UAH');
    $firstProduct = $order->items->first()?->product;
    $coverPath = $firstProduct?->cover_path ?: collect($firstProduct?->gallery ?? [])->first();
    $coverUrl = null;
    if (is_string($coverPath) && trim($coverPath) !== '') {
        try {
            $coverUrl = Storage::disk('public')->exists($coverPath) ? Storage::disk('public')->url($coverPath) : null;
        } catch (Throwable) {
            $coverUrl = null;
        }
    }
@endphp

<x-layouts.marketplace :seo-title="__('Оплату не завершено') . ' · 3Dify'">
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-[2rem] border border-amber-300/25 bg-amber-300/[0.07] shadow-2xl shadow-black/25">
            <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1fr_320px] lg:p-10">
                <div>
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-amber-200/30 bg-amber-300/15 text-amber-200">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                        </svg>
                    </div>
                    <p class="mt-6 text-xs font-black uppercase tracking-[0.28em] text-amber-200">{{ __('Оплату не завершено') }}</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Платіж не пройшов') }}</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-300">
                        {{ __('Ми не отримали підтвердження оплати для замовлення :number. Ви можете повторити покупку або повернутися до моделі.', ['number' => $order->number]) }}
                    </p>

                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        @if($firstProduct)
                            <a href="{{ route('products.show', $firstProduct) }}" class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
                                {{ __('Спробувати ще раз') }}
                            </a>
                        @endif
                        <a href="{{ route('products.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.06] px-6 text-sm font-bold text-white transition hover:bg-white/[0.1]">
                            {{ __('Повернутися в каталог') }}
                        </a>
                    </div>
                </div>

                <aside class="rounded-3xl border border-white/10 bg-zinc-950/60 p-4">
                    @if($firstProduct)
                        <div class="overflow-hidden rounded-2xl border border-white/10 bg-zinc-900">
                            @if($coverUrl)
                                <img src="{{ $coverUrl }}" alt="{{ $firstProduct->localized('title') }}" class="h-40 w-full object-cover">
                            @else
                                <div class="flex h-40 w-full items-center justify-center bg-gradient-to-br from-amber-300/15 to-zinc-950 text-2xl font-black text-amber-100">3D</div>
                            @endif
                        </div>
                        <h2 class="mt-4 text-lg font-black text-white">{{ $firstProduct->localized('title') }}</h2>
                    @endif

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">{{ __('Сума') }}</dt>
                            <dd class="font-black text-white">{{ $formatMoney($order->total, $order->currency) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500">{{ __('Статус') }}</dt>
                            <dd class="rounded-full border border-amber-200/25 bg-amber-300/10 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-200">{{ __('Не оплачено') }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.marketplace>
