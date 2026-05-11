@php
    use Illuminate\Support\Facades\Storage;

    $item = $order->items->first();
    $product = $item?->product;
    $checkoutUrl = data_get($payment->payload, 'checkout_url');
    $formatMoney = fn ($amount, $currency = 'UAH') => number_format((float) $amount, 2, '.', ' ') . ' ' . ($currency ?: 'UAH');

    $coverUrl = null;
    $coverPath = $product?->cover_path ?: collect($product?->gallery ?? [])->first();
    if (is_string($coverPath) && trim($coverPath) !== '') {
        try {
            $coverUrl = Storage::disk('public')->exists($coverPath) ? Storage::disk('public')->url($coverPath) : null;
        } catch (Throwable) {
            $coverUrl = null;
        }
    }
@endphp

<x-layouts.marketplace :seo-title="__('Оплата замовлення') . ' · 3Dify'">
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <span class="inline-flex rounded-full border border-emerald-300/20 bg-emerald-400/10 px-4 py-2 text-xs font-black uppercase tracking-[0.22em] text-emerald-200">
                {{ __('Безпечна оплата') }}
            </span>
            <h1 class="mt-5 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Оплата замовлення') }}</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-400">
                {{ __('Перевірте модель і суму. Після оплати доступ до файлів відкриється автоматично.') }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <div class="rounded-[2rem] border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/25 sm:p-6">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-zinc-500">{{ __('Замовлення') }}</p>
                        <p class="mt-1 font-mono text-sm font-bold text-zinc-200">{{ $order->number }}</p>
                    </div>
                    <span class="rounded-full border border-amber-200/25 bg-amber-300/10 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-200">
                        {{ __('Очікує оплати') }}
                    </span>
                </div>

                @if($product)
                    <article class="mt-5 grid gap-5 sm:grid-cols-[180px_1fr]">
                        <a href="{{ route('products.show', $product) }}" class="group overflow-hidden rounded-3xl border border-white/10 bg-zinc-900">
                            @if($coverUrl)
                                <img src="{{ $coverUrl }}" alt="{{ $product->localized('title') }}" class="h-48 w-full object-cover transition duration-500 group-hover:scale-105 sm:h-full">
                            @else
                                <div class="flex h-48 w-full items-center justify-center bg-gradient-to-br from-emerald-400/20 via-sky-400/10 to-zinc-900 text-2xl font-black text-emerald-100 sm:h-full">3D</div>
                            @endif
                        </a>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-[11px] font-bold text-zinc-300">{{ __('Цифрова 3D-модель') }}</span>
                                @if($item?->license_type)
                                    <span class="rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold text-emerald-200">{{ __(ucfirst($item->license_type)) }}</span>
                                @endif
                            </div>
                            <h2 class="mt-3 text-2xl font-black text-white">{{ $product->localized('title') }}</h2>
                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-zinc-400">{{ $product->localized('short_description') ?: __('Файли моделі будуть доступні після успішної оплати.') }}</p>
                            <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <dt class="text-zinc-500">{{ __('Автор') }}</dt>
                                    <dd class="mt-1 font-bold text-white">{{ $item?->author?->name ?? $product->author?->name ?? '3Dify' }}</dd>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                                    <dt class="text-zinc-500">{{ __('Доступ') }}</dt>
                                    <dd class="mt-1 font-bold text-white">{{ __('Одразу після оплати') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </article>
                @endif
            </div>

            <aside class="h-max rounded-[2rem] border border-white/10 bg-zinc-950/75 p-5 shadow-2xl shadow-black/30 sm:p-6 lg:sticky lg:top-28">
                <h2 class="text-xl font-black text-white">{{ __('До сплати') }}</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-500">{{ __('Модель') }}</dt>
                        <dd class="font-bold text-zinc-200">{{ $formatMoney($item?->price ?? $order->subtotal, $order->currency) }}</dd>
                    </div>
                    <div class="border-t border-white/10 pt-4">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-zinc-300">{{ __('Разом') }}</dt>
                            <dd class="text-2xl font-black text-white">{{ $formatMoney($order->total, $order->currency) }}</dd>
                        </div>
                    </div>
                </dl>

                @if($checkoutUrl)
                    <a href="{{ $checkoutUrl }}" class="mt-6 inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-6 text-base font-black text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                        {{ __('Перейти до оплати') }}
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </a>
                    <p class="mt-4 text-center text-xs leading-5 text-zinc-500">{{ __('Ви перейдете на захищену сторінку платіжного сервісу aifo.pro.') }}</p>
                @else
                    <div class="mt-6 rounded-2xl border border-amber-200/20 bg-amber-300/10 p-4 text-sm leading-6 text-amber-100">
                        {{ __('Платіжне посилання ще не створено. Спробуйте оновити сторінку або зверніться до підтримки.') }}
                    </div>
                    <form method="POST" action="{{ route('checkout.demo-confirm', $order) }}" class="mt-4">
                        @csrf
                        <button class="inline-flex h-12 w-full items-center justify-center rounded-2xl border border-white/10 bg-white/[0.06] px-5 text-sm font-bold text-white transition hover:bg-white/[0.1]">
                            {{ __('Підтвердити тестову оплату') }}
                        </button>
                    </form>
                @endif

                <div class="mt-6 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm font-bold text-white">{{ __('Що буде далі?') }}</p>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-zinc-400">
                        <li>{{ __('1. Ви завершуєте оплату на aifo.pro.') }}</li>
                        <li>{{ __('2. 3Dify підтверджує замовлення.') }}</li>
                        <li>{{ __('3. Файли зʼявляються на сторінці успішної покупки та в кабінеті.') }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.marketplace>
