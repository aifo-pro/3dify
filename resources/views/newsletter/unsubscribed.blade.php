<x-layouts.marketplace :title="__('Ви відписалися')">
    <div class="mx-auto max-w-md px-4 py-20 text-center">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-300/15 text-emerald-200">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h1 class="mt-6 text-2xl font-black text-white">{{ __('Ви успішно відписалися') }}</h1>
        <p class="mt-2 text-sm text-zinc-400">{{ __('Адреса :email більше не отримуватиме листи від нас.', ['email' => $email]) }}</p>
        <a href="{{ url('/') }}" class="mt-6 inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('На головну') }}</a>
    </div>
</x-layouts.marketplace>
