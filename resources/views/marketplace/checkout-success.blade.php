<x-layouts.marketplace>
    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded border border-emerald-400/30 bg-emerald-400/10 p-8">
            <h1 class="text-2xl font-bold text-white">Покупку завершено</h1>
            <p class="mt-3 text-emerald-100">Файли замовлення {{ $order->number }} доступні в особистому кабінеті та на сторінці моделі.</p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex rounded bg-emerald-400 px-5 py-3 font-semibold text-zinc-950">Перейти до кабінету</a>
        </div>
    </section>
</x-layouts.marketplace>
