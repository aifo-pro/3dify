<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Email') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Підтвердження пошти') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('У production-режимі тут можна повторно надіслати лист підтвердження. Для локального сервера обовʼязкову перевірку пошти вимкнено.') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-2xl border border-emerald-300/25 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-100">
            {{ __('Нове посилання підтвердження надіслано на email.') }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">{{ __('Надіслати ще раз') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">{{ __('Вийти') }}</button>
        </form>
    </div>
</x-guest-layout>
