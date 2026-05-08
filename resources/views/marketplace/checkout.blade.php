<x-layouts.marketplace>
    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded border border-white/10 bg-zinc-900 p-8">
            <h1 class="text-2xl font-bold text-white">Оплата через aifo.pro</h1>
            <p class="mt-3 text-zinc-400">Платіж створено: {{ $payment->provider_payment_id }}. Для production підключіть ключі aifo.pro в адмінці та обробку webhook.</p>
            <dl class="mt-6 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-400">Замовлення</dt><dd>{{ $order->number }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-400">Сума</dt><dd>{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</dd></div>
            </dl>
            @if(data_get($payment->payload, 'checkout_url'))
                <a href="{{ data_get($payment->payload, 'checkout_url') }}" class="mt-6 inline-flex rounded bg-emerald-400 px-5 py-3 font-semibold text-zinc-950">Перейти до оплати</a>
            @else
                <form method="POST" action="{{ route('checkout.demo-confirm', $order) }}" class="mt-6">
                    @csrf
                    <button class="rounded bg-emerald-400 px-5 py-3 font-semibold text-zinc-950">Demo: підтвердити оплату</button>
                </form>
            @endif
        </div>
    </section>
</x-layouts.marketplace>
