{{-- TinyMCE: use loadTinyMce prop — @push from slot runs after <head> @stack, so CDN never loaded. --}}
@props([
    'title' => null,
    'description' => null,
    'breadcrumbs' => [],
    'active' => 'dashboard',
    'loadTinyMce' => false,
])

@php
    $settings = app(\App\Services\SiteSettings::class);
    $siteName = $settings->string('site.name', '3Dify');
    $faviconPath = $settings->string('brand.favicon_path');

    $pendingBadge = \Illuminate\Support\Facades\Schema::hasTable('products')
        ? \App\Models\Product::where('status', 'pending')->count()
        : 0;
    $failedBadge = \Illuminate\Support\Facades\Schema::hasTable('failed_jobs')
        ? \Illuminate\Support\Facades\DB::table('failed_jobs')->count()
        : 0;
    $refundsBadge = \Illuminate\Support\Facades\Schema::hasTable('refund_requests')
        ? \App\Models\RefundRequest::where('status', 'pending')->count()
        : 0;
    $reportsBadge = \Illuminate\Support\Facades\Schema::hasTable('product_reports')
        ? \App\Models\ProductReport::where('status', 'pending')->count()
        : 0;
    $reviewsBadge = \Illuminate\Support\Facades\Schema::hasTable('product_reviews')
        ? \App\Models\ProductReview::where('status', 'pending')->count()
        : 0;
    $commentsBadge = \Illuminate\Support\Facades\Schema::hasTable('product_comments')
        ? \App\Models\ProductComment::where('status', 'pending')->count()
        : 0;
    $makesBadge = \Illuminate\Support\Facades\Schema::hasTable('product_makes')
        ? \App\Models\ProductMake::where('status', 'pending')->count()
        : 0;
    $moderationBadge = $pendingBadge + $reportsBadge + $reviewsBadge + $commentsBadge + $makesBadge;

    $navSections = [
        [
            'label' => __('Огляд'),
            'items' => [
                ['key' => 'dashboard', 'label' => __('Dashboard'), 'href' => route('admin.index'), 'icon' => 'grid'],
                ['key' => 'analytics', 'label' => __('Аналітика'), 'href' => route('admin.analytics'), 'icon' => 'chart'],
            ],
        ],
        [
            'label' => __('Модерація'),
            'items' => [
                ['key' => 'moderation', 'label' => __('Центр модерації'), 'href' => route('admin.moderation.hub'), 'icon' => 'shield', 'badge' => $moderationBadge ?: null, 'badge_tone' => 'rose'],
            ],
        ],
        [
            'label' => __('Каталог'),
            'items' => [
                ['key' => 'products', 'label' => __('Моделі'), 'href' => route('admin.products'), 'icon' => 'box', 'badge' => $pendingBadge ?: null, 'badge_tone' => 'amber'],
                ['key' => 'categories', 'label' => __('Категорії'), 'href' => route('admin.categories'), 'icon' => 'folder'],
                ['key' => 'tags', 'label' => __('Теги'), 'href' => route('admin.tags'), 'icon' => 'tag'],
                ['key' => 'licenses', 'label' => __('Ліцензії'), 'href' => route('admin.licenses'), 'icon' => 'shield'],
            ],
        ],
        [
            'label' => __('Комерція'),
            'items' => [
                ['key' => 'orders', 'label' => __('Замовлення'), 'href' => route('admin.orders'), 'icon' => 'bag'],
                ['key' => 'payments', 'label' => __('Платежі'), 'href' => route('admin.payments'), 'icon' => 'card'],
                ['key' => 'payouts', 'label' => __('Виплати'), 'href' => route('admin.payouts'), 'icon' => 'card'],
                ['key' => 'promo-codes', 'label' => __('Промокоди'), 'href' => route('admin.promo-codes'), 'icon' => 'tag'],
                ['key' => 'finance', 'label' => __('Тіпи'), 'href' => route('admin.tips'), 'icon' => 'card'],
                ['key' => 'refunds', 'label' => __('Повернення'), 'href' => route('admin.refunds'), 'icon' => 'shield', 'badge' => $refundsBadge ?: null, 'badge_tone' => 'rose'],
            ],
        ],
        [
            'label' => __('Спільнота'),
            'items' => [
                ['key' => 'users', 'label' => __('Користувачі'), 'href' => route('admin.users'), 'icon' => 'users'],
                ['key' => 'blog', 'label' => __('Блог'), 'href' => route('admin.blog.index'), 'icon' => 'layers', 'admin_only' => true],
                ['key' => 'announcements', 'label' => __('Оголошення'), 'href' => route('admin.announcements'), 'icon' => 'bell'],
                ['key' => 'newsletter', 'label' => __('Newsletter'), 'href' => route('admin.newsletter'), 'icon' => 'send'],
            ],
        ],
        [
            'label' => __('Система'),
            'items' => [
                ['key' => 'system', 'label' => __('Система'), 'href' => route('admin.system'), 'icon' => 'settings', 'badge' => $failedBadge ?: null, 'badge_tone' => 'amber'],
                ['key' => 'api-tokens', 'label' => __('API-токени'), 'href' => route('admin.api-tokens'), 'icon' => 'key'],
                ['key' => 'content', 'label' => __('SEO та контент'), 'href' => route('admin.content'), 'icon' => 'settings'],
                ['key' => 'audit', 'label' => __('Журнал дій'), 'href' => route('admin.audit'), 'icon' => 'grid'],
            ],
        ],
    ];

    $icon = static function (string $name): string {
        return match ($name) {
            'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
            'box' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            'layers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
            'folder' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
            'tag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
            'card' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
            'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
            'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
            'send' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
            'key' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-[18px] w-[18px]"><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-3.5 3.5L21 9"/></svg>',
            default => '',
        };
    };

    $envName = app()->environment();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('Admin') }} · {{ $siteName }}</title>
    <meta name="robots" content="noindex,nofollow">
    @if($faviconPath)<link rel="icon" href="{{ Storage::disk('public')->url($faviconPath) }}">@endif
    @if($loadTinyMce)
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7.4.0/tinymce.min.js"></script>
    @endif
    @stack('head-scripts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{ sidebar: false, collapsed: $persist(false).as('admin_sidebar_collapsed') }"
    class="min-h-screen bg-zinc-950 text-zinc-100 antialiased"
>
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,.10),transparent_36%),radial-gradient(circle_at_top_right,rgba(14,165,233,.06),transparent_30%),linear-gradient(180deg,#06100c_0%,#09090b_44%,#030712_100%)]"></div>

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-white/10 bg-zinc-950/90 backdrop-blur-xl transition-all duration-200 lg:sticky lg:top-0 lg:h-screen"
            :class="[
                sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                collapsed ? 'lg:w-[72px]' : 'lg:w-64',
                'w-72'
            ]"
            x-cloak
        >
            {{-- Brand --}}
            <div class="flex h-[68px] shrink-0 items-center gap-3 border-b border-white/10 px-4">
                <a href="{{ route('admin.index') }}" class="flex min-w-0 items-center gap-2.5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-400 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/25">3D</span>
                    <span class="min-w-0 leading-tight" :class="collapsed && 'lg:hidden'">
                        <span class="block truncate text-sm font-black text-white">{{ $siteName }}</span>
                        <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-300">Admin</span>
                    </span>
                </a>
                <button
                    @click="collapsed = ! collapsed"
                    type="button"
                    class="ml-auto hidden h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-400 transition hover:bg-white/10 hover:text-white lg:grid"
                    :title="collapsed ? '{{ __('Розгорнути') }}' : '{{ __('Згорнути') }}'"
                >
                    <svg class="h-4 w-4" :class="collapsed && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button
                    @click="sidebar = false"
                    type="button"
                    class="ml-auto grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/10 lg:hidden"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 [scrollbar-width:thin]">
                @foreach($navSections as $section)
                    <div class="mb-5">
                        <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-500" :class="collapsed && 'lg:hidden'">{{ $section['label'] }}</p>
                        <ul class="grid gap-1">
                            @foreach($section['items'] as $item)
                                @continue(! empty($item['admin_only']) && ! auth()->user()?->isAdmin())
                                @php $isActive = $active === $item['key']; @endphp
                                <li>
                                    <a
                                        href="{{ $item['href'] }}"
                                        class="group relative flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition
                                            {{ $isActive
                                                ? 'bg-emerald-300/15 text-emerald-100 shadow-inner shadow-emerald-500/10'
                                                : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white' }}"
                                        :title="collapsed ? '{{ $item['label'] }}' : null"
                                    >
                                        <span class="grid h-5 w-5 shrink-0 place-items-center {{ $isActive ? 'text-emerald-200' : 'text-zinc-500 group-hover:text-zinc-200' }}">
                                            {!! $icon($item['icon']) !!}
                                        </span>
                                        <span class="truncate" :class="collapsed && 'lg:hidden'">{{ $item['label'] }}</span>
                                        @if(! empty($item['badge']))
                                            <span
                                                class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-bold
                                                    {{ ($item['badge_tone'] ?? 'emerald') === 'amber' ? 'bg-amber-300/20 text-amber-100' : 'bg-emerald-300/20 text-emerald-100' }}"
                                                :class="collapsed && 'lg:hidden'"
                                            >{{ $item['badge'] }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            {{-- Footer / health --}}
            <div class="border-t border-white/10 px-3 py-3" :class="collapsed && 'lg:hidden'">
                <a href="{{ route('home') }}" class="flex h-9 items-center gap-2 rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-medium text-zinc-300 transition hover:bg-white/10 hover:text-white">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    {{ __('Перейти на сайт') }}
                </a>
                @if($failedBadge > 0)
                    <a href="#" class="mt-2 flex h-9 items-center gap-2 rounded-lg border border-red-400/30 bg-red-400/[0.08] px-3 text-xs font-semibold text-red-200 transition hover:bg-red-400/15">
                        <span class="grid h-1.5 w-1.5 place-items-center rounded-full bg-red-400 ring-2 ring-red-400/30"></span>
                        {{ __('Failed jobs') }}: {{ $failedBadge }}
                    </a>
                @endif
            </div>
        </aside>

        {{-- Backdrop on mobile --}}
        <div
            x-cloak
            x-show="sidebar"
            @click="sidebar = false"
            class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm lg:hidden"
        ></div>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-[68px] items-center gap-3 border-b border-white/10 bg-zinc-950/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                <button
                    @click="sidebar = true"
                    type="button"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-200 transition hover:bg-white/10 lg:hidden"
                    aria-label="Open menu"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>

                {{-- Breadcrumbs --}}
                <nav class="hidden min-w-0 items-center gap-2 text-sm md:flex">
                    <a href="{{ route('admin.index') }}" class="text-zinc-500 transition hover:text-zinc-300">{{ __('Admin') }}</a>
                    @foreach($breadcrumbs as $crumb)
                        <span class="text-zinc-700">/</span>
                        @if(! empty($crumb['href']) && ! $loop->last)
                            <a href="{{ $crumb['href'] }}" class="truncate text-zinc-500 transition hover:text-zinc-300">{{ $crumb['label'] }}</a>
                        @else
                            <span class="truncate font-semibold text-white">{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    {{-- Quick search (visual; future endpoint) --}}
                    <div class="relative hidden md:block">
                        <input
                            type="search"
                            placeholder="{{ __('Швидкий пошук…') }}"
                            class="h-10 w-56 rounded-full border border-white/10 bg-white/[0.04] pl-10 pr-12 text-sm text-white placeholder:text-zinc-500 transition focus:w-72 focus:border-emerald-300 focus:bg-zinc-900/80 focus:ring-1 focus:ring-emerald-300/40"
                        >
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </span>
                        <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded border border-white/10 bg-white/[0.06] px-1.5 py-0.5 text-[10px] font-bold text-zinc-400 lg:inline">⌘K</kbd>
                    </div>

                    <span class="hidden h-10 items-center rounded-full border border-white/10 bg-white/[0.04] px-3 text-[11px] font-bold uppercase tracking-wider text-zinc-300 sm:inline-flex">
                        <span class="mr-2 grid h-1.5 w-1.5 place-items-center rounded-full {{ $envName === 'production' ? 'bg-emerald-400 ring-2 ring-emerald-400/30' : 'bg-amber-400 ring-2 ring-amber-400/30' }}"></span>
                        {{ $envName }}
                    </span>

                    @auth
                        <div x-data="{ menuOpen: false }" class="relative">
                            <button @click="menuOpen = ! menuOpen" type="button" class="flex h-10 items-center gap-2 rounded-full border border-white/10 bg-white/[0.05] py-1 pl-1 pr-3 text-sm font-semibold text-white transition hover:bg-white/[0.1]">
                                <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-300 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="hidden max-w-[120px] truncate xl:inline">{{ auth()->user()->name }}</span>
                                <svg class="h-3.5 w-3.5 text-zinc-400 transition" :class="menuOpen && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.24 4.38a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                            </button>
                            <div
                                x-cloak
                                x-show="menuOpen"
                                @click.outside="menuOpen = false"
                                @keydown.escape.window="menuOpen = false"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute right-0 top-full z-[9999] mt-3 w-60 origin-top-right overflow-hidden rounded-2xl border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/50 backdrop-blur-xl"
                            >
                                <div class="flex items-center gap-3 border-b border-white/10 px-4 py-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-300 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2.5 text-sm text-zinc-200 hover:bg-white/[0.06] hover:text-white">{{ __('Профіль') }}</a>
                                    <a href="{{ route('home') }}" class="flex items-center px-4 py-2.5 text-sm text-zinc-200 hover:bg-white/[0.06] hover:text-white">{{ __('Перейти на сайт') }}</a>
                                </div>
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-white/10">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center px-4 py-3 text-left text-sm font-semibold text-red-300 transition hover:bg-red-400/10 hover:text-red-200">{{ __('Вийти') }}</button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            {{-- Page header --}}
            @if($title)
                <div class="border-b border-white/5 px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto flex w-full max-w-[1400px] flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0">
                            <h1 class="truncate text-2xl font-black tracking-tight text-white sm:text-3xl">{{ $title }}</h1>
                            @if($description)
                                <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-400">{{ $description }}</p>
                            @endif
                        </div>
                        @isset($actions)
                            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                </div>
            @endif

            @if (session('status'))
                <div class="px-4 pt-4 sm:px-6 lg:px-8">
                    <div class="mx-auto flex w-full max-w-[1400px] items-center gap-3 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100 shadow-lg shadow-emerald-500/10">
                        <svg class="h-4 w-4 shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-[1400px]">{{ $slot }}</div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
