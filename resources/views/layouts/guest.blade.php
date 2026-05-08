<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', '3Dify') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <main class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
            <section class="relative hidden overflow-hidden border-r border-white/10 bg-zinc-950 lg:block">
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(16,185,129,.24),transparent_42%),linear-gradient(225deg,rgba(14,165,233,.16),transparent_38%)]"></div>
                <div class="relative flex min-h-screen flex-col justify-between p-12">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 text-2xl font-black text-white">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/25">3D</span>
                        3Dify
                    </a>
                    <div class="max-w-xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-300">{{ __('Marketplace для мейкерів') }}</p>
                        <h1 class="mt-5 text-5xl font-black leading-tight tracking-tight text-white">{{ __('Купуйте, продавайте й друкуйте 3D-моделі без зайвого шуму') }}</h1>
                        <p class="mt-5 text-lg leading-8 text-zinc-300">{{ __('Авторський кабінет, захищені файли, каталог, 3D preview та локальний MVP-режим без обовʼязкового email-підтвердження.') }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-3xl border border-white/10 bg-white/[0.06] p-5"><strong class="block text-2xl text-white">STL</strong><span class="text-xs text-zinc-400">{{ __('source-файли') }}</span></div>
                        <div class="rounded-3xl border border-white/10 bg-white/[0.06] p-5"><strong class="block text-2xl text-white">GLB</strong><span class="text-xs text-zinc-400">{{ __('preview') }}</span></div>
                        <div class="rounded-3xl border border-white/10 bg-white/[0.06] p-5"><strong class="block text-2xl text-white">AIFO</strong><span class="text-xs text-zinc-400">{{ __('оплати') }}</span></div>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mx-auto mb-8 flex w-fit items-center gap-3 text-2xl font-black text-white lg:hidden">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-400 text-zinc-950">3D</span>
                        3Dify
                    </a>
                    <x-site.auth-card>
                        {{ $slot }}
                    </x-site.auth-card>
                    <p class="mt-6 text-center text-xs text-zinc-500">© {{ date('Y') }} 3Dify. {{ __('Локальний режим розробки.') }}</p>
                </div>
            </section>
        </main>
    </body>
</html>
