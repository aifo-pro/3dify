@php
    use Illuminate\Support\Facades\Storage;

    $formatMoney = fn ($amount, $currency = 'UAH') => number_format((float) $amount, 2, '.', ' ') . ' ' . ($currency ?: 'UAH');
    $imageUrl = function ($product): ?string {
        $path = $product?->cover_path ?: collect($product?->gallery ?? [])->first();
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        try {
            return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null;
        } catch (Throwable) {
            return null;
        }
    };
@endphp

<x-layouts.marketplace :seo-title="__('Покупку завершено') . ' · 3Dify'">
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-[2rem] border border-emerald-400/25 bg-emerald-400/[0.08] shadow-2xl shadow-emerald-950/30">
            <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1.25fr_.75fr] lg:p-10">
                <div>
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-300/30 bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/25">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </div>
                    <p class="mt-6 text-xs font-black uppercase tracking-[0.28em] text-emerald-200">{{ __('Оплата успішна') }}</p>
                    <h1 class="mt-3 max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Дякуємо за покупку') }}</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-300">
                        {{ __('Замовлення :number оплачено. Нижче доступні придбані моделі та файли для завантаження.', ['number' => $order->number]) }}
                    </p>
                </div>

                <div class="rounded-3xl border border-white/10 bg-zinc-950/55 p-5">
                    <dl class="space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-500">{{ __('Замовлення') }}</dt>
                            <dd class="text-right font-bold text-white">{{ $order->number }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-500">{{ __('Сума') }}</dt>
                            <dd class="font-black text-emerald-200">{{ $formatMoney($order->total, $order->currency) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-500">{{ __('Статус') }}</dt>
                            <dd class="rounded-full border border-emerald-300/25 bg-emerald-400/10 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-200">{{ __('Оплачено') }}</dd>
                        </div>
                    </dl>
                    <a href="{{ route('dashboard') }}" class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-2xl border border-white/10 bg-white/[0.06] px-5 text-sm font-bold text-white transition hover:bg-white/[0.1]">
                        {{ __('Перейти до кабінету') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-5">
            @foreach($order->items as $item)
                @php
                    $product = $item->product;
                    $cover = $imageUrl($product);
                @endphp
                @if($product)
                    <article class="grid gap-5 rounded-[1.75rem] border border-white/10 bg-zinc-950/70 p-4 shadow-2xl shadow-black/25 sm:grid-cols-[180px_1fr] sm:p-5">
                        <a href="{{ route('products.show', $product) }}" class="group overflow-hidden rounded-3xl border border-white/10 bg-zinc-900">
                            @if($cover)
                                <img src="{{ $cover }}" alt="{{ $product->localized('title') }}" class="h-48 w-full object-cover transition duration-500 group-hover:scale-105 sm:h-full">
                            @else
                                <div class="flex h-48 w-full items-center justify-center bg-gradient-to-br from-emerald-400/20 via-sky-400/10 to-zinc-900 text-2xl font-black text-emerald-100 sm:h-full">3D</div>
                            @endif
                        </a>

                        <div class="flex min-w-0 flex-col justify-between gap-5">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-[11px] font-black uppercase tracking-[0.18em] text-zinc-300">{{ __('Придбано') }}</span>
                                    <span class="rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold text-emerald-200">{{ $formatMoney($item->price, $item->currency) }}</span>
                                </div>
                                <h2 class="mt-3 text-2xl font-black text-white">{{ $product->localized('title') }}</h2>
                                <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-400">{{ $product->localized('short_description') ?: __('Файли моделі готові до завантаження.') }}</p>
                                <p class="mt-3 text-sm text-zinc-500">{{ __('Автор') }}: <span class="font-bold text-zinc-200">{{ $item->author?->name ?? $product->author?->name ?? '3Dify' }}</span></p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    data-download-trigger
                                    data-download-url="{{ route('products.download-options', $product) }}"
                                    data-download-title="{{ $product->localized('title') }}"
                                    class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <path d="M7 10l5 5 5-5" />
                                        <path d="M12 15V3" />
                                    </svg>
                                    {{ __('Скачати файли') }}
                                </button>
                                <a href="{{ route('products.show', $product) }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.06] px-6 text-sm font-bold text-white transition hover:bg-white/[0.1]">
                                    {{ __('Відкрити сторінку моделі') }}
                                </a>
                            </div>
                        </div>
                    </article>
                @endif
            @endforeach
        </div>
    </section>

    <x-ui.download-modal />
</x-layouts.marketplace>
