@php
    $settings = app(\App\Services\SiteSettings::class);
    $seo = \Illuminate\Support\Facades\Schema::hasTable('seo_pages')
        ? \App\Models\SeoPage::query()
            ->where('route_name', request()->route()?->getName())
            ->where('locale', app()->getLocale())
            ->first()
        : null;
    $siteName = $settings->string('site.name', '3Dify');
    $logoPath = $settings->string('brand.logo_path');
    $faviconPath = $settings->string('brand.favicon_path');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $finalTitle = $seoTitle ?? $seo?->title ?? $siteName.' - '.__('маркетплейс 3D-моделей');
        $finalDescription = $seoDescription ?? $seo?->description ?? __('Купуйте, продавайте та завантажуйте якісні 3D-моделі для друку.');
        $finalImage = $seoImage ?? ($settings->string('brand.og_image_path') ? Storage::disk('public')->url($settings->string('brand.og_image_path')) : null);
        $canonical = $seo?->canonical_url ?? url()->current();
        $ogType = $ogType ?? 'website';
    @endphp
    <title>{{ $finalTitle }}</title>
    <meta name="description" content="{{ $finalDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $finalTitle }}">
    <meta property="og:description" content="{{ $finalDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if($finalImage)<meta property="og:image" content="{{ $finalImage }}">@endif
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

    {{-- Twitter --}}
    <meta name="twitter:card" content="{{ $finalImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $finalTitle }}">
    <meta name="twitter:description" content="{{ $finalDescription }}">
    @if($finalImage)<meta name="twitter:image" content="{{ $finalImage }}">@endif

    {{-- Hreflang --}}
    <link rel="alternate" hreflang="uk" href="{{ url()->current() }}?lang=uk">
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}?lang=en">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    @if($faviconPath)<link rel="icon" href="{{ Storage::disk('public')->url($faviconPath) }}">@endif

    <x-site.google-head />

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    <x-site.google-body />
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(16,185,129,.14),transparent_34%),radial-gradient(circle_at_top_right,rgba(14,165,233,.10),transparent_30%),linear-gradient(180deg,#071411_0%,#09090b_40%,#030712_100%)]"></div>

    <x-site.header :site-name="$siteName" :logo-path="$logoPath" />
    <x-site.announcement-banner />

    @if (session('status'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex gap-3 rounded-2xl border border-emerald-400/25 bg-emerald-400/[0.07] px-4 py-3.5 text-sm text-emerald-50 shadow-xl shadow-black/15 backdrop-blur-sm">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-emerald-300/30 bg-emerald-400/[0.12] text-emerald-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11"/></svg>
                </span>
                <p class="min-w-0 flex-1 pt-0.5 leading-relaxed text-emerald-50/95">{{ session('status') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div role="alert" class="flex gap-3 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5 shadow-xl shadow-black/25 backdrop-blur-sm">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-amber-300/25 bg-amber-400/[0.08] text-amber-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <p class="min-w-0 flex-1 pt-0.5 text-sm leading-relaxed text-zinc-200">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <main>{{ $slot }}</main>

    <x-site.footer :site-name="$siteName" />

    <x-site.cookie-banner />
</body>
</html>
