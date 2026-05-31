@props(['siteName' => '3Dify', 'logoPath' => null])

@php
    $navItems = [
        ['label' => __('Каталог'),      'href' => route('products.index'), 'active' => request()->routeIs('products.index') && ! request()->boolean('free')],
        ['label' => __('Категорії'),    'href' => route('products.index').'#categories', 'active' => request()->filled('category')],
        ['label' => __('Автори'),       'href' => route('authors.index'), 'active' => request()->routeIs('authors.*')],
        ['label' => __('Блог'),         'href' => route('blog.index'), 'active' => request()->routeIs('blog.*')],
        ['label' => __('Безкоштовні'), 'href' => route('products.index', ['free' => 1]), 'active' => request()->boolean('free')],
    ];
@endphp

<header
    x-data="{ open: false, userOpen: false, moreOpen: false }"
    class="sticky top-0 z-50 border-b border-white/10 bg-zinc-950/85 backdrop-blur-xl supports-[backdrop-filter]:bg-zinc-950/60"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 whitespace-nowrap">
            @if($logoPath)
                <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ $siteName }}" class="h-8 w-8 rounded-xl object-cover shadow-lg shadow-emerald-500/20">
            @else
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-400 text-[11px] font-black text-zinc-950 shadow-lg shadow-emerald-500/25">3D</span>
            @endif
            <span class="hidden text-base font-black tracking-tight text-white sm:inline">{{ $siteName }}</span>
        </a>

        {{-- Left nav --}}
        <nav class="hidden items-center gap-1 lg:flex">
            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}" class="relative inline-flex h-9 items-center whitespace-nowrap rounded-full px-3.5 text-[13px] font-semibold transition {{ $item['active'] ? 'text-emerald-100' : 'text-zinc-400 hover:text-white' }}">
                    @if($item['active'])<span class="absolute inset-0 rounded-full bg-emerald-300/[0.10] ring-1 ring-emerald-300/30"></span>@endif
                    <span class="relative">{{ $item['label'] }}</span>
                </a>
            @endforeach

            {{-- "Ще" dropdown --}}
            <div class="relative" x-data="{ moreOpen: false }">
                <button @click="moreOpen = !moreOpen" class="relative inline-flex h-9 items-center gap-1 whitespace-nowrap rounded-full px-3.5 text-[13px] font-semibold text-zinc-400 transition hover:text-white">
                    {{ __('Ще') }}
                    <svg class="h-3.5 w-3.5 transition" :class="moreOpen && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.24 4.38a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="moreOpen" @click.outside="moreOpen = false" x-cloak
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute left-0 top-full z-[9999] mt-2 w-52 origin-top-left overflow-hidden rounded-2xl border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/50 backdrop-blur-xl">
                    <div class="py-1.5">
                        <a href="{{ route('makes.gallery') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                            <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            {{ __('Галерея друків') }}
                        </a>
                        <a href="{{ route('challenges.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                            <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                            {{ __('Челенджі') }}
                        </a>
                        <a href="{{ route('leaderboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                            <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                            {{ __('Рейтинг авторів') }}
                        </a>
                        <a href="{{ route('search') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                            <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            {{ __('Глобальний пошук') }}
                        </a>
                        <a href="{{ route('compare') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                            <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            {{ __('Порівняти моделі') }}
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Right cluster --}}
        <div class="ml-auto hidden items-center gap-1.5 md:flex">
            {{-- Global search --}}
            <a href="{{ route('search') }}" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 text-zinc-400 transition hover:bg-white/10 hover:text-white" aria-label="{{ __('Пошук') }}" title="{{ __('Глобальний пошук') }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </a>

            {{-- Lang switcher --}}
            <a href="{{ route('locale.switch', app()->getLocale() === 'uk' ? 'en' : 'uk') }}" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 text-[10px] font-bold text-zinc-300 transition hover:bg-white/10 hover:text-white">
                {{ app()->getLocale() === 'uk' ? 'EN' : 'UK' }}
            </a>

            {{-- Sell CTA --}}
            <a href="{{ auth()->check() ? route('author.products.create') : route('register') }}" class="hidden h-9 items-center whitespace-nowrap rounded-full bg-emerald-400 px-4 text-[13px] font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300 lg:inline-flex">
                {{ __('Продати') }}
            </a>

            @guest
                <a href="{{ route('login') }}" class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-[13px] font-semibold text-zinc-200 transition hover:bg-white/10 hover:text-white">{{ __('Увійти') }}</a>
                <a href="{{ route('register') }}" class="inline-flex h-9 items-center whitespace-nowrap rounded-full border border-white/15 bg-white/[0.06] px-3 text-[13px] font-semibold text-white transition hover:bg-white/10">{{ __('Реєстрація') }}</a>
            @else
                @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                <a href="{{ route('wishlist.index') }}" title="{{ __('Обране') }}" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 text-zinc-400 transition hover:bg-white/10 hover:text-rose-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </a>
                <a href="{{ route('notifications.index') }}" title="{{ __('Сповіщення') }}" class="relative grid h-9 w-9 place-items-center rounded-full border border-white/10 text-zinc-400 transition hover:bg-white/10 hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    @if($unreadCount > 0)
                        <span class="absolute -right-0.5 -top-0.5 grid min-w-[18px] place-items-center rounded-full bg-emerald-400 px-1 text-[9px] font-black text-zinc-950">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </a>

                {{-- User dropdown --}}
                <div class="relative">
                    <button @click="userOpen = !userOpen" :aria-expanded="userOpen" type="button"
                            class="flex h-9 items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.05] py-0.5 pl-0.5 pr-2.5 text-sm font-semibold text-white transition hover:bg-white/[0.1]">
                        @if(auth()->user()->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" class="h-7 w-7 rounded-full object-cover">
                        @else
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-300 text-[11px] font-black text-zinc-950">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        @endif
                        <svg class="h-3 w-3 text-zinc-400 transition" :class="userOpen && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.24 4.38a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-cloak x-show="userOpen" @click.outside="userOpen = false" @keydown.escape.window="userOpen = false"
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute right-0 top-full z-[9999] mt-3 w-72 origin-top-right overflow-hidden rounded-2xl border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/50 backdrop-blur-xl">

                        <div class="flex items-center gap-3 border-b border-white/10 px-4 py-3">
                            @if(auth()->user()->avatarUrl())
                                <img src="{{ auth()->user()->avatarUrl() }}" class="h-9 w-9 rounded-full object-cover shrink-0">
                            @else
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-300 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->displayName() }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                            </div>
                        </div>

                        {{-- Library & purchases --}}
                        <div class="border-b border-white/[0.06] py-1.5">
                            <p class="px-4 pb-1 pt-2 text-[10px] font-black uppercase tracking-widest text-zinc-600">Мої файли</p>
                            <a href="{{ route('library') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                {{ __('Бібліотека завантажень') }}
                            </a>
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                                {{ __('Покупки') }}
                            </a>
                            <a href="{{ route('wishlist.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                {{ __('Обране') }}
                            </a>
                            <a href="{{ route('balance.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                                {{ __('Баланс') }}
                            </a>
                        </div>

                        {{-- Author section --}}
                        <div class="border-b border-white/[0.06] py-1.5">
                            <p class="px-4 pb-1 pt-2 text-[10px] font-black uppercase tracking-widest text-zinc-600">Автору</p>
                            <a href="{{ route('author.products.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                {{ __('Мої моделі') }}
                            </a>
                            <a href="{{ route('author.analytics') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                {{ __('Аналітика') }}
                            </a>
                            <a href="{{ route('author.payouts') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                {{ __('Виплати') }}
                            </a>
                            <a href="{{ route('referral') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                                {{ __('Реферальна програма') }}
                            </a>
                        </div>

                        {{-- Settings --}}
                        <div class="border-b border-white/[0.06] py-1.5">
                            <p class="px-4 pb-1 pt-2 text-[10px] font-black uppercase tracking-widest text-zinc-600">Налаштування</p>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/></svg>
                                {{ __('Профіль') }}
                            </a>
                            <a href="{{ route('notifications.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                                {{ __('Сповіщення') }}
                                @if($unreadCount > 0)<span class="ml-auto rounded-full bg-emerald-400 px-1.5 py-0.5 text-[10px] font-black text-zinc-950">{{ $unreadCount }}</span>@endif
                            </a>
                            <a href="{{ route('printers.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                {{ __('Мої принтери') }}
                            </a>
                            <a href="{{ route('saved-searches.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                {{ __('Збережені пошуки') }}
                            </a>
                            <a href="{{ route('refunds.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-zinc-200 transition hover:bg-white/[0.06] hover:text-white">
                                <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                {{ __('Повернення') }}
                            </a>
                            @if(auth()->user()->canModerate())
                                <a href="{{ route('admin.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-emerald-300 transition hover:bg-emerald-400/[0.08] hover:text-emerald-200">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    {{ __('Адмінпанель') }}
                                </a>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-3 text-left text-sm font-semibold text-red-300 transition hover:bg-red-400/10 hover:text-red-200">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                {{ __('Вийти') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>

        {{-- Mobile burger --}}
        <button @click="open = !open" type="button"
                class="ml-auto grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-200 transition hover:bg-white/10 md:hidden"
                :aria-expanded="open" aria-label="Menu">
            <svg x-show="!open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            <svg x-cloak x-show="open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    {{-- Mobile drawer --}}
    <div x-cloak x-show="open"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         class="border-t border-white/10 bg-zinc-950/95 px-4 pb-5 pt-4 backdrop-blur-xl md:hidden">

        <form method="GET" action="{{ route('search') }}" class="relative mb-4">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('Пошук моделей, авторів…') }}"
                   class="h-11 w-full rounded-2xl border border-white/10 bg-white/[0.06] pl-10 pr-4 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </form>

        <nav class="grid gap-1">
            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}" class="rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-emerald-300/15 text-emerald-100' : 'text-zinc-300 hover:bg-white/[0.06] hover:text-white' }}">{{ $item['label'] }}</a>
            @endforeach

            <div class="my-2 border-t border-white/[0.06] pt-2">
                <p class="mb-1 px-4 text-[10px] font-black uppercase tracking-widest text-zinc-600">Спільнота</p>
                <a href="{{ route('makes.gallery') }}" class="rounded-xl px-4 py-2.5 text-sm text-zinc-300 transition hover:bg-white/[0.06] hover:text-white flex items-center gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    {{ __('Галерея друків') }}
                </a>
                <a href="{{ route('challenges.index') }}" class="rounded-xl px-4 py-2.5 text-sm text-zinc-300 transition hover:bg-white/[0.06] hover:text-white flex items-center gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                    {{ __('Челенджі') }}
                </a>
                <a href="{{ route('leaderboard') }}" class="rounded-xl px-4 py-2.5 text-sm text-zinc-300 transition hover:bg-white/[0.06] hover:text-white flex items-center gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                    {{ __('Рейтинг авторів') }}
                </a>
                <a href="{{ route('compare') }}" class="rounded-xl px-4 py-2.5 text-sm text-zinc-300 transition hover:bg-white/[0.06] hover:text-white flex items-center gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    {{ __('Порівняти') }}
                </a>
            </div>

            <a href="{{ auth()->check() ? route('author.products.create') : route('register') }}" class="mt-2 inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-bold text-zinc-950 transition hover:bg-emerald-300">{{ __('Продати модель') }}</a>

            @auth
                <div class="my-2 border-t border-white/[0.06] pt-2">
                    <p class="mb-1 px-4 text-[10px] font-black uppercase tracking-widest text-zinc-600">Мій акаунт</p>
                    <a href="{{ route('library') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-white/[0.06] hover:text-white">{{ __('Бібліотека') }}</a>
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-white/[0.06] hover:text-white">{{ __('Покупки') }}</a>
                    <a href="{{ route('author.products.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-white/[0.06] hover:text-white">{{ __('Мої моделі') }}</a>
                    <a href="{{ route('referral') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-white/[0.06] hover:text-white">{{ __('Реферальна програма') }}</a>
                    <a href="{{ route('balance.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-white/[0.06] hover:text-white">{{ __('Баланс') }}</a>
                    @if(auth()->user()->canModerate())
                        <a href="{{ route('admin.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-emerald-300 transition hover:bg-emerald-400/10 hover:text-emerald-200">{{ __('Адмінпанель') }}</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="w-full rounded-xl border border-red-400/30 bg-red-500/[0.06] px-4 py-2.5 text-left text-sm font-semibold text-red-200">{{ __('Вийти') }}</button>
                </form>
            @else
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-white/[0.06] px-4 py-2.5 text-sm font-semibold text-white">{{ __('Увійти') }}</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-bold text-zinc-950">{{ __('Реєстрація') }}</a>
                </div>
            @endauth
        </nav>
    </div>
</header>
