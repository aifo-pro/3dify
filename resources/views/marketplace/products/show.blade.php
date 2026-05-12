@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Schema;

    $access = auth()->check() ? app(\App\Services\MarketplaceAccess::class)->canDownload(auth()->user(), $product) : false;
    $accountBalance = 0.0;
    if (auth()->check() && Schema::hasTable('account_balance_transactions')) {
        try {
            $accountBalance = app(\App\Services\AccountBalanceService::class)->availableBalance(auth()->user(), $product->currency ?? 'UAH');
        } catch (\Throwable) {
            $accountBalance = 0.0;
        }
    }
    $isOwner = auth()->id() === $product->user_id;
    $canModerate = auth()->user()?->canModerate() || $isOwner;

    // Default print settings — recommended baseline shown when author has not
    // specified custom values. Real custom values can be wired later via JSON.
    $printSettings = [
        ['key' => 'material', 'label' => __('Матеріал'), 'value' => 'PLA / PETG', 'icon' => 'flask'],
        ['key' => 'layer', 'label' => __('Висота шару'), 'value' => '0.2 mm', 'icon' => 'layers'],
        ['key' => 'infill', 'label' => __('Заповнення'), 'value' => '15–20 %', 'icon' => 'grid'],
        ['key' => 'supports', 'label' => __('Підтримки'), 'value' => __('Не потрібні'), 'icon' => 'columns'],
        ['key' => 'nozzle', 'label' => __('Сопло'), 'value' => '0.4 mm', 'icon' => 'circle'],
        ['key' => 'time', 'label' => __('Час друку'), 'value' => '~3–5 ' . __('год'), 'icon' => 'clock'],
    ];

    $settingIcon = function (string $name): string {
        return match ($name) {
            'flask' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v6L4 21h16L15 9V3"/><path d="M9 3h6"/></svg>',
            'layers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
            'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
            'columns' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="21"/><line x1="12" y1="3" x2="12" y2="21"/><line x1="18" y1="3" x2="18" y2="21"/></svg>',
            'circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>',
            'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
            default => '',
        };
    };

    $hasPrintProfile = $product->print_profile_path || $product->print_profile_settings || $product->dim_x || $product->recommended_materials;

    $tabs = [
        ['key' => 'info', 'label' => __('Інформація')],
        ['key' => 'profile', 'label' => __('Профіль друку'), 'show' => $hasPrintProfile],
        ['key' => 'reviews', 'label' => __('Відгуки')],
        ['key' => 'makes', 'label' => __('Фото друку'), 'count' => $makes->where('status','approved')->count()],
        ['key' => 'comments', 'label' => __('Коментарі'), 'count' => $comments->count()],
        ['key' => 'similar', 'label' => __('Схожі моделі')],
    ];
    $tabs = array_values(array_filter($tabs, fn ($t) => ! isset($t['show']) || $t['show']));

    $reviews = $product->reviews()->where('status', 'published')->with('user')->latest()->get();
    $avgRating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : null;
    $reviewsCount = $reviews->count();
    $userReview = auth()->check() ? $reviews->firstWhere('user_id', auth()->id()) : null;
    $authorName = $product->author?->name ?? __('3Dify author');
    $publicUrl = function (?string $path): ?string {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        try {
            if (! Storage::disk('public')->exists($path)) {
                return null;
            }
            $url = Storage::disk('public')->url($path);
            if (! str_starts_with($url, 'http')) {
                $url = url($url);
            }
            return $url;
        } catch (\Throwable) {
            return null;
        }
    };
    $diskUrl = function (?string $disk, ?string $path): ?string {
        if (! is_string($disk) || ! is_string($path) || trim($path) === '') {
            return null;
        }

        try {
            $url = Storage::disk($disk)->url($path);
            if (! str_starts_with($url, 'http')) {
                $url = url($url);
            }
            return $url;
        } catch (\Throwable) {
            return null;
        }
    };

    $imagePreview = $product->previewFile && in_array($product->previewFile->extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)
        ? $product->previewFile
        : null;
    $modelPreview = $product->previewFile && ! $imagePreview ? $product->previewFile : null;
    $galleryImages = collect($product->gallery ?? [])
        ->filter(fn ($image) => is_string($image) && trim($image) !== '')
        ->values();
    $coverImage = $publicUrl($product->cover_path)
        ?: $publicUrl($galleryImages->first())
        ?: ($imagePreview ? $diskUrl($imagePreview->disk, $imagePreview->path) : null);
    $gallerySliderImages = collect([
            $coverImage,
            ...$galleryImages->map(fn ($image) => $publicUrl($image))->all(),
            $imagePreview ? $diskUrl($imagePreview->disk, $imagePreview->path) : null,
        ])
        ->filter(fn ($url) => is_string($url) && trim($url) !== '')
        ->unique()
        ->values()
        ->map(fn ($url) => [
            'url' => $url,
            'alt' => $product->localized('title'),
        ])
        ->all();
    $modelPreviewUrl = $modelPreview ? $diskUrl($modelPreview->disk, $modelPreview->path) : null;
    $galleryMediaItems = collect($gallerySliderImages)
        ->map(fn ($item) => [
            'type' => 'image',
            'url' => $item['url'],
            'alt' => $item['alt'],
        ])
        ->when($modelPreviewUrl, fn ($items) => $items->prepend([
            'type' => 'viewer',
            'url' => $modelPreviewUrl,
            'alt' => $product->localized('title'),
        ]))
        ->values()
        ->all();

    $productUrl = route('products.show', $product);
    $productDescription = $product->localized('short_description') ?: $product->localized('description') ?: __('3D-модель для друку на 3Dify.');
    $productImages = collect($gallerySliderImages)
        ->pluck('url')
        ->filter(fn ($url) => is_string($url) && trim($url) !== '')
        ->unique()
        ->values();
    $primaryImageUrl = $productImages->first() ?: $coverImage;
    $breadcrumbItems = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => __('Головна'), 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => __('Каталог'), 'item' => route('products.index')],
    ];
    if ($product->category) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $product->category->localized('name'),
            'item' => route('categories.show', $product->category),
        ];
    }
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => $product->localized('title'),
        'item' => $productUrl,
    ];
    $webPageSchema = [
        '@type' => 'WebPage',
        '@id' => $productUrl.'#webpage',
        'url' => $productUrl,
        'name' => $product->localized('title').' · 3Dify',
        'description' => $productDescription,
        'breadcrumb' => ['@id' => $productUrl.'#breadcrumb'],
    ];
    if ($primaryImageUrl) {
        $webPageSchema['primaryImageOfPage'] = ['@id' => $primaryImageUrl.'#image'];
    }
    $productSchema = [
        '@type' => 'Product',
        '@id' => $productUrl.'#product',
        'name' => $product->localized('title'),
        'description' => $productDescription,
        'sku' => 'P-'.$product->id,
        'url' => $productUrl,
        'brand' => ['@type' => 'Brand', 'name' => '3Dify'],
        'manufacturer' => ['@type' => 'Organization', 'name' => $authorName],
        'category' => $product->category?->localized('name'),
        'offers' => [
            '@type' => 'Offer',
            'price' => (float) $product->price,
            'priceCurrency' => $product->currency ?? 'UAH',
            'availability' => $product->status === 'published' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $productUrl,
        ],
    ];
    if ($productImages->isNotEmpty()) {
        $productSchema['image'] = $productImages->all();
    }
    if ($reviewsCount > 0) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (float) $avgRating,
            'reviewCount' => $reviewsCount,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
    $imageSchemas = $productImages
        ->map(fn ($url, $index) => [
            '@type' => 'ImageObject',
            '@id' => $url.'#image',
            'url' => $url,
            'contentUrl' => $url,
            'name' => $product->localized('title').' · '.__('фото :number', ['number' => $index + 1]),
            'caption' => $product->localized('title').' — '.__('3D-модель для друку'),
            'representativeOfPage' => $index === 0,
        ])
        ->all();
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => array_merge([
            $webPageSchema,
            $productSchema,
            [
                '@type' => 'BreadcrumbList',
                '@id' => $productUrl.'#breadcrumb',
                'itemListElement' => $breadcrumbItems,
            ],
        ], $imageSchemas),
    ];
@endphp

<x-layouts.marketplace
    :seo-title="$product->localized('title') . ' · 3Dify'"
    :seo-description="$product->localized('short_description') ?: __('3D-модель для друку на 3Dify.')"
    :seo-image="$coverImage"
    og-type="product"
>

@push('head')
    @if($coverImage)
        <link rel="preload" as="image" href="{{ $coverImage }}">
    @elseif($imagePreview)
        <link rel="preload" as="image" href="{{ Storage::disk($imagePreview->disk)->url($imagePreview->path) }}">
    @endif
    <script type="application/ld+json">
    {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

    {{-- =================================================================== --}}
    {{-- HERO                                                                  --}}
    {{-- =================================================================== --}}
    <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$product->is_free ? 'free' : 'paid'">{{ $product->is_free ? __('Безкоштовна модель') : __('Преміальна модель') }}</x-ui.badge>
                @if($product->category)
                    <a href="{{ route('categories.show', $product->category) }}">
                        <x-ui.badge>{{ $product->category->localized('name') }}</x-ui.badge>
                    </a>
                @endif
                @if($product->license)
                    <x-license-badge :license="$product->license" size="md" />
                @endif
                @if($product->commercial_license_enabled && ($product->commercialLicense ?? null) && $product->commercial_license_id !== $product->license_id)
                    <x-license-badge :license="$product->commercialLicense" size="md" />
                @endif
            </div>
            <h1 class="mt-5 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">{{ $product->localized('title') }}</h1>
            <p class="mt-4 max-w-3xl text-lg leading-8 text-zinc-400">{{ $product->localized('short_description') }}</p>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_390px]">
            <div class="grid gap-8">
                @if(count($galleryMediaItems) > 0)
                    <x-product-gallery :media-items="$galleryMediaItems" :model-preview-url="$modelPreviewUrl" :product-title="$product->localized('title')" />
                @elseif($modelPreview)
                    <div class="overflow-hidden rounded-3xl" style="aspect-ratio: 4/3; max-height: 620px; background: #05070a;">
                        <div data-model-viewer data-model-url="{{ Storage::disk($modelPreview->disk)->url($modelPreview->path) }}" class="h-full w-full"></div>
                    </div>
                @else
                    <div class="overflow-hidden rounded-3xl" style="aspect-ratio: 4/3; max-height: 620px; background: #05070a;">
                        <div data-model-viewer data-model-url="" class="h-full w-full"></div>
                    </div>
                @endif

                {{-- Quick stats --}}
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('Перегляди') }}</span>
                        <strong class="mt-2 block text-2xl font-black text-white">{{ number_format((int) $product->views_count) }}</strong>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('Завантаження') }}</span>
                        <strong class="mt-2 block text-2xl font-black text-white">{{ number_format((int) $product->downloads_count) }}</strong>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ __('Формати') }}</span>
                        <strong class="mt-2 block text-base font-bold text-white">
                            {{ $product->files->pluck('extension')->unique()->map(fn ($ext) => strtoupper($ext))->join(' · ') ?: '3D' }}
                        </strong>
                    </div>
                </div>
            </div>

            <aside class="self-start lg:sticky lg:top-28">
                <x-ui.card
                    class="p-6"
                    x-data="productPricing()"
                    x-init="Object.assign($data, {
                        licenseType: 'personal',
                        personalPrice: {{ (float) $product->personalPrice() }},
                        commercialPrice: {{ (float) $product->commercialPrice() }},
                        currency: @js($product->currency ?? 'UAH'),
                        accountBalance: {{ (float) $accountBalance }},
                        balanceAmount: {{ (float) min($accountBalance, max($product->personalPrice(), 0)) }},
                        locale: @js(app()->getLocale() === 'uk' ? 'uk-UA' : 'en-US'),
                        freeLabel: @js(__('Безкоштовно'))
                    })"
                    x-on:license-changed="licenseType = $event.detail.type"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-zinc-400">{{ __('Ціна') }}</p>
                            <strong class="mt-1 block text-3xl text-white" x-text="displayPrice">{{ $product->display_price }}</strong>
                        </div>
                        <x-ui.badge>{{ $product->category?->localized('name') ?? __('3D model') }}</x-ui.badge>
                    </div>

                    {{-- Pricing cards (personal vs commercial) --}}
                    @if($product->commercial_license_enabled && ! $product->is_free)
                        <div class="mt-5">
                            <x-license-pricing-cards :product="$product" />
                        </div>
                    @endif

                    <div class="mt-6 grid gap-3 rounded-3xl border border-white/10 bg-zinc-950/60 p-4 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-zinc-400">{{ __('Автор') }}</span><span class="text-right text-white">{{ $authorName }}</span></div>
                        @if($product->license)
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-zinc-400">{{ __('Ліцензія') }}</span>
                                <span x-show="licenseType === 'personal'">
                                    <x-license-badge :license="$product->license" size="sm" :tooltip="false" />
                                </span>
                                @if($product->commercial_license_enabled)
                                    <span x-show="licenseType === 'commercial'" x-cloak>
                                        <x-license-badge :license="$product->commercialLicense ?? $product->license" size="sm" :tooltip="false" />
                                    </span>
                                @endif
                            </div>
                        @endif
                        <div class="flex justify-between gap-4"><span class="text-zinc-400">{{ __('Статус') }}</span><span class="text-right text-emerald-200">{{ __('Опубліковано') }}</span></div>
                        @if($product->published_at)
                            <div class="flex justify-between gap-4"><span class="text-zinc-400">{{ __('Опубліковано') }}</span><span class="text-right text-zinc-300">{{ $product->published_at->format('d.m.Y') }}</span></div>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-2">
                        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.06] p-4">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-200">{{ __('Що входить') }}</p>
                            <ul class="mt-3 grid gap-2 text-sm text-zinc-300">
                                <li class="flex gap-2"><span class="text-emerald-300">✓</span><span>{{ __('Захищені файли моделі після оплати') }}</span></li>
                                <li class="flex gap-2"><span class="text-emerald-300">✓</span><span>{{ __('Доступ до download center і slicer-посилань') }}</span></li>
                                <li class="flex gap-2"><span class="text-emerald-300">✓</span><span>{{ __('Покупка привʼязується до вашого акаунта') }}</span></li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.035] p-4 text-xs leading-5 text-zinc-400">
                            <strong class="text-zinc-200">{{ __('Повернення') }}:</strong>
                            {{ __('якщо файл пошкоджений або не відповідає опису, заявку можна подати з кабінету. Після підтвердження кошти повертаються на баланс, а скачування цього замовлення блокується.') }}
                        </div>
                    </div>

                    @auth
                        @php $promoSession = session('promo.'.$product->id); @endphp
                        @if(! $product->is_free && ! ($access ?? false))
                            <form method="POST" action="{{ route('products.promo.apply', $product) }}" class="mt-6 grid grid-cols-[1fr_auto] gap-2" x-data="{ open: {{ $promoSession ? 'true' : 'false' }} }">
                                @csrf
                                <button type="button" @click="open = !open" class="col-span-2 -mb-1 inline-flex items-center gap-2 self-start text-xs font-bold uppercase tracking-[0.14em] text-emerald-300 hover:text-emerald-200">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    {{ __('Маю промокод') }}
                                </button>
                                <div x-show="open" x-cloak class="col-span-2 grid grid-cols-[1fr_auto] gap-2">
                                    <input type="text" name="code" value="{{ $promoSession['code'] ?? '' }}" placeholder="SUMMER25" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 font-mono text-sm uppercase text-white placeholder:text-zinc-500 focus:border-emerald-300">
                                    <button class="h-10 rounded-xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 text-xs font-bold text-emerald-100 hover:bg-emerald-300/[0.16]">{{ __('Застосувати') }}</button>
                                </div>
                                @error('promo')<p x-show="open" class="col-span-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                                @if($promoSession)
                                    <p x-show="open" class="col-span-2 text-xs text-emerald-200">{{ __('Знижка') }}: {{ $promoSession['code'] }} (−{{ number_format($promoSession['discount'], 2, '.', ' ') }} грн)</p>
                                @endif
                            </form>
                        @endif

                        <div class="mt-3 grid gap-3">
                            @if($access)
                                <button
                                    type="button"
                                    data-download-trigger
                                    data-download-url="{{ route('products.download-options', $product) }}"
                                    data-download-title="{{ $product->localized('title') }}"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                    {{ __('Скачати / друкувати') }}
                                </button>
                            @else
                                <form method="POST" action="{{ route('checkout.store', $product) }}" class="grid gap-3">
                                    @csrf
                                    <input type="hidden" name="license_type" :value="licenseType">
                                    @if($accountBalance > 0 && ! $product->is_free)
                                        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-400/[0.07] p-4">
                                            <label class="flex items-start gap-3">
                                                <input type="checkbox" name="use_balance" value="1" x-model="useBalance" @change="balanceAmount = maxBalanceAmount" class="mt-1 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-black text-white">{{ __('Використати баланс') }}</span>
                                                    <span class="mt-1 block text-xs leading-5 text-zinc-400">
                                                        {{ __('Доступно на балансі') }}:
                                                        <strong class="text-emerald-200">{{ number_format($accountBalance, 2, '.', ' ') }} {{ $product->currency ?? 'UAH' }}</strong>
                                                    </span>
                                                </span>
                                            </label>
                                            <div x-show="useBalance" x-cloak class="mt-3 grid gap-2">
                                                <label class="text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-200">{{ __('Списати з балансу') }}</label>
                                                <input
                                                    type="number"
                                                    name="balance_amount"
                                                    step="0.01"
                                                    min="0"
                                                    :max="maxBalanceAmount"
                                                    x-model.number="balanceAmount"
                                                    class="h-11 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm font-bold text-white focus:border-emerald-300"
                                                >
                                                <p class="text-xs text-zinc-400">
                                                    {{ __('До оплати через платіжний сервіс') }}:
                                                    <strong class="text-white" x-text="money(payableAmount)"></strong>
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                    <button class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
                                        @if($product->is_free)
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                                            <span>{{ __('Отримати файли') }}</span>
                                        @else
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                            <span x-text="licenseType === 'commercial' ? @js(__('Купити Commercial')) : @js(__('Купити Personal'))">{{ __('Купити модель') }}</span>
                                        @endif
                                    </button>
                                </form>
                            @endif
                            <div class="flex items-center gap-2">
                                <x-ui.wishlist-button :product="$product" variant="icon" size="lg" class="[&_button]:h-11 [&_button]:w-11" />
                                <x-share-button
                                    :url="route('products.show', $product)"
                                    :title="$product->localized('title')"
                                    :description="$product->localized('short_description') ?: __('3D-модель на 3Dify')"
                                    :image="$coverImage ?? ''"
                                    :embed-route="route('products.embed', $product)"
                                />
                                <span class="ml-1 text-xs font-medium text-zinc-500">{{ __('Поширити') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="mt-6 grid gap-3">
                            <x-ui.button :href="route('login')" class="w-full">{{ __('Увійти для покупки') }}</x-ui.button>
                            <div class="flex items-center gap-2">
                                <x-ui.wishlist-button :product="$product" variant="icon" size="lg" class="[&_button]:h-11 [&_button]:w-11" />
                                <x-share-button
                                    :url="route('products.show', $product)"
                                    :title="$product->localized('title')"
                                    :description="$product->localized('short_description') ?: __('3D-модель на 3Dify')"
                                    :image="$coverImage ?? ''"
                                    :embed-route="route('products.embed', $product)"
                                />
                                <span class="ml-1 text-xs font-medium text-zinc-500">{{ __('Поширити') }}</span>
                            </div>
                        </div>
                    @endauth

                    <div class="mt-4">
                        <x-ui.printer-compat :product="$product" />
                    </div>

                    @if($reviewsCount > 0)
                        <a href="#reviews" @click.prevent="setTab('reviews')" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-amber-200 hover:text-amber-100">
                            <span class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="{{ $i <= round($avgRating) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                @endfor
                            </span>
                            {{ number_format($avgRating, 1) }} · {{ trans_choice('{1}:count відгук|[2,*]:count відгуки', $reviewsCount, ['count' => $reviewsCount]) }}
                        </a>
                    @endif

                    @auth
                        @if(false && $access)
                            <div class="mt-6">
                                <button
                                    type="button"
                                    data-download-trigger
                                    data-download-url="{{ route('products.download-options', $product) }}"
                                    data-download-title="{{ $product->localized('title') }}"
                                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-5 py-3 font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                    {{ __('Скачати / друкувати') }}
                                </button>
                                <p class="mt-2 text-center text-[11px] text-zinc-500">
                                    {{ __('Файли STL · OBJ · GLB · 3MF · ZIP. Безпечне завантаження після перевірки доступу.') }}
                                </p>
                            </div>
                        @endif
                    @endauth

                    {{-- Tip jar (any logged-in user except the author can tip) --}}
                    @auth
                        @if($product->user_id !== auth()->id())
                            <x-ui.card class="mt-4 p-4" x-data="{ open: false, amount: 100, msg: '' }">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 text-sm font-bold text-white">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-2xl border border-amber-300/25 bg-amber-300/[0.10] text-amber-200 shadow-lg shadow-amber-500/10">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                            </span>
                                            <span class="truncate">{{ __('Подякувати автору') }}</span>
                                        </div>
                                        <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Підтримайте автора, навіть якщо модель безкоштовна.') }}</p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="shrink-0 inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 transition hover:border-amber-300/25 hover:bg-amber-300/[0.08] hover:text-amber-100"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path x-show="!open" d="M12 5v14"/><path x-show="!open" d="M5 12h14"/>
                                            <path x-show="open" d="M5 12h14"/>
                                        </svg>
                                        <span x-text="open ? @js(__('Сховати')) : @js(__('Підтримати'))"></span>
                                    </button>
                                </div>

                                <form x-show="open" x-cloak method="POST" action="{{ route('products.tip', $product) }}" class="mt-4 grid gap-3">
                                    @csrf

                                    <div class="grid gap-2">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Оберіть суму') }}</p>
                                        <div class="grid grid-cols-4 gap-2">
                                            @foreach([50, 100, 200, 500] as $preset)
                                                <button
                                                    type="button"
                                                    @click="amount = {{ $preset }}"
                                                    :class="amount == {{ $preset }} ? 'border-amber-300/40 bg-amber-300/[0.12] text-amber-100' : 'border-white/10 bg-white/[0.04] text-zinc-300 hover:border-white/20 hover:bg-white/[0.07]'"
                                                    class="h-10 rounded-2xl border text-xs font-bold transition"
                                                >{{ $preset }} грн</button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="grid gap-1.5">
                                        <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Сума, грн') }}</span>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-amber-300/70">₴</span>
                                            <input
                                                type="number"
                                                name="amount"
                                                min="10"
                                                max="50000"
                                                step="1"
                                                x-model.number="amount"
                                                class="h-11 w-full rounded-2xl border border-white/10 bg-zinc-950/40 pl-8 pr-3 text-center font-mono text-sm font-bold text-white placeholder:text-zinc-500 focus:border-amber-300/40 focus:ring-1 focus:ring-amber-300/30"
                                            >
                                        </div>
                                    </div>

                                    <div class="grid gap-1.5">
                                        <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Повідомлення (необовʼязково)') }}</span>
                                        <input
                                            type="text"
                                            name="message"
                                            maxlength="280"
                                            x-model="msg"
                                            placeholder="{{ __('Напр. “Дякую за модель!”') }}"
                                            class="h-11 rounded-2xl border border-white/10 bg-zinc-950/40 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-amber-300/40 focus:ring-1 focus:ring-amber-300/30"
                                        >
                                    </div>

                                    <button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-amber-300 px-5 text-sm font-black text-zinc-950 shadow-lg shadow-amber-500/25 transition hover:bg-amber-200">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                        {{ __('Перейти до оплати') }}
                                    </button>
                                </form>
                            </x-ui.card>
                        @endif
                    @endauth

                    {{-- Report-issue trigger --}}
                    <button
                        type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-report-modal'))"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2.5 text-xs font-semibold text-zinc-300 transition hover:border-rose-300/30 hover:bg-rose-300/[0.08] hover:text-rose-100"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        {{ __('Повідомити про проблему') }}
                    </button>
                </x-ui.card>
            </aside>
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- TABS                                                                  --}}
    {{-- =================================================================== --}}
    <section
        id="tabs"
        x-data="productTabs()"
        class="mx-auto max-w-7xl px-4 pb-16 pt-12 sm:px-6 lg:px-8"
    >
        {{-- Tab nav --}}
        <div class="sticky top-[76px] z-20 mb-8 rounded-[1.5rem] border border-white/10 bg-white/[0.055] p-1.5 shadow-2xl shadow-black/20 backdrop-blur-xl">
            <nav class="flex gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($tabs as $t)
                    <button
                        type="button"
                        @click="setTab('{{ $t['key'] }}')"
                        :class="tab === '{{ $t['key'] }}'
                            ? 'bg-emerald-300/18 text-emerald-50 shadow-lg shadow-emerald-950/25 ring-1 ring-emerald-300/30'
                            : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white'"
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-[1.05rem] px-4 text-sm font-bold transition"
                    >
                        <span>{{ $t['label'] }}</span>
                        @if(isset($t['count']) && $t['count'] > 0)
                            <span
                                :class="tab === '{{ $t['key'] }}' ? 'bg-emerald-300/30 text-emerald-50' : 'bg-white/10 text-zinc-300'"
                                class="rounded-full px-1.5 py-0.5 text-[10px] font-black"
                            >{{ $t['count'] }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 1: INFO                                                     --}}
        {{-- ============================================================== --}}
        <div x-show="tab === 'info'" x-cloak class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="grid gap-6">
                <h2 class="sr-only">{{ __('Інформація') }}</h2>
                {{-- Description --}}
                <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
                    <header class="mb-5 flex items-center gap-3 border-b border-white/5 pb-4">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.10] text-emerald-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="14 2 14 8 20 8"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Опис') }}</p>
                            <h3 class="text-lg font-bold text-white">{{ __('Про цю модель') }}</h3>
                        </div>
                    </header>
                    <div class="prose prose-invert max-w-none text-zinc-300 prose-p:text-zinc-300 prose-p:leading-7">
                        @if($product->localized('description'))
                            {!! nl2br(e($product->localized('description'))) !!}
                        @else
                            <p class="text-zinc-500">{{ __('Автор поки не додав детальний опис.') }}</p>
                        @endif
                    </div>
                </article>

                {{-- Print settings --}}
                <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
                    <header class="mb-5 flex items-center gap-3 border-b border-white/5 pb-4">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl border border-sky-300/30 bg-sky-300/[0.10] text-sky-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-300">{{ __('Друк') }}</p>
                            <h3 class="text-lg font-bold text-white">{{ __('Налаштування 3D-друку') }}</h3>
                        </div>
                        <span class="ml-auto rounded-full border border-white/10 bg-zinc-950/60 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-zinc-400">{{ __('Рекомендовано') }}</span>
                    </header>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($printSettings as $setting)
                            <div class="rounded-2xl border border-white/10 bg-zinc-950/40 p-4 transition hover:border-white/20">
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">
                                    <span class="grid h-5 w-5 place-items-center text-emerald-300">{!! $settingIcon($setting['icon']) !!}</span>
                                    {{ $setting['label'] }}
                                </div>
                                <p class="mt-2 text-base font-bold text-white">{{ $setting['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                {{-- Metadata grid --}}
                <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
                    <header class="mb-5 flex items-center gap-3 border-b border-white/5 pb-4">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl border border-violet-300/30 bg-violet-300/[0.10] text-violet-200">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-violet-300">{{ __('Метадані') }}</p>
                            <h3 class="text-lg font-bold text-white">{{ __('Категорії, теги, ліцензія') }}</h3>
                        </div>
                    </header>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ __('Категорія') }}</dt>
                            <dd class="mt-1.5">
                                @if($product->category)
                                    <a href="{{ route('categories.show', $product->category) }}" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.06] px-3 py-1 text-sm font-semibold text-zinc-100 transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.10] hover:text-emerald-100">
                                        {{ $product->category->localized('name') }}
                                    </a>
                                @else
                                    <span class="text-sm text-zinc-500">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ __('Автор') }}</dt>
                            <dd class="mt-1.5 flex items-center gap-2">
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-400 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr($authorName, 0, 1)) }}</span>
                                <span class="text-sm font-semibold text-white">{{ $authorName }}</span>
                            </dd>
                        </div>
                        @if($product->tags->isNotEmpty())
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ __('Теги') }}</dt>
                                <dd class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach($product->tags as $tag)
                                        <a href="{{ route('products.index', ['tag' => $tag->slug]) }}" class="inline-flex items-center rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-xs font-medium text-zinc-300 hover:border-emerald-300/30 hover:bg-emerald-300/[0.08] hover:text-emerald-100">#{{ $tag->slug }}</a>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                </article>

                {{-- License summary --}}
                @if($product->license)
                    <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-1 shadow-xl shadow-black/20">
                        <div class="rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-5 sm:p-6">
                            <header class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-white">{{ __('Що дозволяє ліцензія') }}</h3>
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('Чек-лист прав використання моделі.') }}</p>
                                </div>
                            </header>
                            <x-license-summary :license="$product->license" :product="$product" />

                            @if($product->commercial_license_enabled && $product->commercialLicense && $product->commercial_license_id !== $product->license_id)
                                <div class="mt-5 border-t border-white/5 pt-5">
                                    <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-200">
                                        <x-license-icons name="commercial" class="h-3.5 w-3.5" />
                                        {{ __('Альтернативно: Commercial license') }}
                                    </p>
                                    <x-license-summary :license="$product->commercialLicense" :compact="true" />
                                </div>
                            @endif
                        </div>
                    </article>
                @endif
            </div>

            {{-- Sticky right rail (CTA + author) --}}
            <aside class="grid gap-4 self-start lg:sticky lg:top-44">
                {{-- Author CTA --}}
                <div class="relative overflow-hidden rounded-3xl border border-emerald-300/25 bg-gradient-to-br from-emerald-300/[0.10] via-emerald-300/[0.04] to-transparent p-6 shadow-xl shadow-emerald-500/10">
                    <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-emerald-400/30 blur-3xl"></div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Для авторів') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-white">{{ __('Хочете продавати власні 3D-моделі?') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Завантажуйте файли, отримуйте платежі через aifo.pro і фідбек від спільноти.') }}</p>
                    <a href="{{ auth()->check() ? route('author.products.create') : route('register') }}" class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                        {{ __('Стати автором') }}
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>

                {{-- Author card --}}
                <x-marketplace.author-card
                    :author="$product->author"
                    :is-following="$product->author->isFollowedBy(auth()->user())"
                    :is-self="auth()->id() === $product->author->id"
                />
            </aside>
        </div>

        {{-- ============================================================== --}}
        {{-- TAB: PRINT PROFILE                                              --}}
        {{-- ============================================================== --}}
        @if($hasPrintProfile)
            <div x-show="tab === 'profile'" x-cloak>
                <div class="mb-6">
                    <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ __('Профіль друку від автора') }}</h2>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-zinc-400">{{ __('Рекомендовані налаштування слайсера, габарити моделі та матеріали — використовуйте як стартову точку.') }}</p>
                </div>

                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="grid gap-4">
                        @if($product->print_profile_settings)
                            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/20">
                                <h3 class="mb-4 text-sm font-bold uppercase tracking-[0.14em] text-emerald-300">{{ __('Налаштування слайсера') }}</h3>
                                <dl class="grid gap-3 sm:grid-cols-2">
                                    @foreach([
                                        'layer_height' => __('Висота шару'),
                                        'nozzle' => __('Сопло'),
                                        'infill' => __('Заповнення'),
                                        'supports' => __('Підтримки'),
                                        'speed' => __('Швидкість'),
                                        'temp_nozzle' => __('Темп. сопла'),
                                    ] as $k => $label)
                                        @if(! empty($product->print_profile_settings[$k]))
                                            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-zinc-950/40 px-3 py-2">
                                                <dt class="text-xs text-zinc-400">{{ $label }}</dt>
                                                <dd class="text-sm font-bold text-white">{{ $product->print_profile_settings[$k] }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </div>
                        @endif

                        @if($product->dim_x || $product->dim_y || $product->dim_z)
                            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                <h3 class="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-emerald-300">{{ __('Габарити моделі') }}</h3>
                                <div class="grid grid-cols-3 gap-3 text-center">
                                    <div class="rounded-xl border border-white/10 bg-zinc-950/40 p-4">
                                        <p class="text-[10px] font-bold text-zinc-500">X</p>
                                        <p class="mt-1 text-2xl font-black text-white">{{ $product->dim_x ?? '—' }}<span class="text-sm font-medium text-zinc-500"> мм</span></p>
                                    </div>
                                    <div class="rounded-xl border border-white/10 bg-zinc-950/40 p-4">
                                        <p class="text-[10px] font-bold text-zinc-500">Y</p>
                                        <p class="mt-1 text-2xl font-black text-white">{{ $product->dim_y ?? '—' }}<span class="text-sm font-medium text-zinc-500"> мм</span></p>
                                    </div>
                                    <div class="rounded-xl border border-white/10 bg-zinc-950/40 p-4">
                                        <p class="text-[10px] font-bold text-zinc-500">Z</p>
                                        <p class="mt-1 text-2xl font-black text-white">{{ $product->dim_z ?? '—' }}<span class="text-sm font-medium text-zinc-500"> мм</span></p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($product->recommended_materials)
                            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                                <h3 class="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-emerald-300">{{ __('Рекомендовані матеріали') }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($product->recommended_materials as $m)
                                        <span class="inline-flex h-8 items-center rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-3 text-xs font-bold text-emerald-100">{{ $m }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <aside class="self-start lg:sticky lg:top-32">
                        @if($product->print_profile_path)
                            <div class="rounded-3xl border border-emerald-300/30 bg-gradient-to-br from-emerald-300/[0.08] to-sky-300/[0.04] p-6 shadow-xl shadow-emerald-500/10">
                                <div class="flex items-start gap-3">
                                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-300/20 text-xs font-black text-emerald-100">3MF</span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-300">{{ __('Готовий профіль') }}</p>
                                        <p class="mt-1 truncate text-sm font-bold text-white">{{ $product->print_profile_name }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-zinc-300">{{ __('Файл налаштувань для OrcaSlicer / Bambu Studio / PrusaSlicer. Завантажте та відкрийте у вашому слайсері.') }}</p>
                                @auth
                                    @if($access)
                                        <a href="{{ route('products.print-profile.download', $product) }}" class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            {{ __('Скачати профіль') }}
                                        </a>
                                    @else
                                        <p class="mt-4 rounded-xl border border-white/10 bg-zinc-950/40 px-4 py-2.5 text-center text-xs text-zinc-400">{{ __('Профіль доступний після покупки моделі.') }}</p>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-xl border border-white/15 bg-white/[0.05] px-5 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('Увійти для завантаження') }}</a>
                                @endauth
                            </div>
                        @else
                            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 text-center text-sm text-zinc-400">
                                {{ __('Автор не додав готовий файл профілю. Скористайтеся параметрами зліва.') }}
                            </div>
                        @endif
                    </aside>
                </div>
            </div>
        @endif

        {{-- ============================================================== --}}
        {{-- TAB: REVIEWS                                                    --}}
        {{-- ============================================================== --}}
        <div x-show="tab === 'reviews'" x-cloak>
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ __('Відгуки покупців') }}</h2>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-zinc-400">{{ __('Чесні оцінки покупців і завантажувачів моделі.') }}</p>
                </div>
                @if($reviewsCount > 0)
                    <div class="flex items-center gap-3 rounded-2xl border border-amber-300/30 bg-amber-300/[0.08] px-4 py-2.5">
                        <div class="flex items-center gap-1 text-amber-300">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="{{ $i <= round($avgRating) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            @endfor
                        </div>
                        <div>
                            <p class="text-lg font-black text-white">{{ number_format($avgRating, 1) }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-200">{{ trans_choice('{1}:count відгук|[2,*]:count відгуки', $reviewsCount, ['count' => $reviewsCount]) }}</p>
                        </div>
                    </div>
                @endif
            </div>

            @auth
                @if(auth()->id() !== $product->user_id)
                    <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="mb-6 rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/20" x-data="{ rating: {{ $userReview?->rating ?? 0 }} }">
                        @csrf
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ $userReview ? __('Ваш відгук') : __('Залишити відгук') }}</p>
                        <div class="mt-3 flex items-center gap-1">
                            <template x-for="n in 5" :key="n">
                                <button type="button" @click="rating = n" class="text-amber-300 transition hover:scale-110">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" :fill="n <= rating ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </button>
                            </template>
                            <span class="ml-2 text-xs text-zinc-500" x-text="rating + ' / 5'"></span>
                        </div>
                        <input type="hidden" name="rating" :value="rating" required>
                        @error('rating')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror

                        <textarea name="body" rows="3" maxlength="3000" placeholder="{{ __('Коротко поділіться враженнями (опціонально)') }}" class="mt-3 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">{{ $userReview?->body }}</textarea>
                        @error('body')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p class="text-[11px] text-zinc-500">{{ __('Ви побачите бейдж «Перевірений покупець», якщо вже придбали або завантажили модель.') }}</p>
                            <button type="submit" :disabled="rating < 1" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow shadow-emerald-500/25 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-40">
                                {{ $userReview ? __('Оновити відгук') : __('Опублікувати') }}
                            </button>
                        </div>
                    </form>
                @endif
            @else
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm text-zinc-300">{{ __('Увійдіть, щоб залишити відгук.') }}</p>
                    <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Увійти') }}</a>
                </div>
            @endauth

            @if($reviews->isNotEmpty())
                <ul class="grid gap-3">
                    @foreach($reviews as $rev)
                        <li class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                            <div class="flex items-start gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-400 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr($rev->user->name ?? '?', 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-bold text-white">{{ $rev->user->name }}</p>
                                        @if($rev->is_verified_buyer)
                                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-200">
                                                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                {{ __('перевірений') }}
                                            </span>
                                        @endif
                                        <span class="text-[11px] text-zinc-500">{{ $rev->created_at->diffForHumans() }}</span>
                                        @auth
                                            @if(auth()->id() === $rev->user_id || auth()->user()->canModerate())
                                                <form method="POST" action="{{ route('products.reviews.destroy', [$product, $rev]) }}" class="ml-auto" onsubmit="return confirm('{{ __('Видалити відгук?') }}');">
                                                    @csrf @method('DELETE')
                                                    <button class="text-[11px] text-zinc-500 hover:text-rose-300">{{ __('Видалити') }}</button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                    <div class="mt-1 flex items-center gap-1 text-amber-300">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="{{ $i <= $rev->rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        @endfor
                                    </div>
                                    @if($rev->body)
                                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-200">{{ $rev->body }}</p>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="grid place-items-center rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-amber-300/[0.10] text-amber-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-white">{{ __('Поки немає відгуків') }}</h3>
                    <p class="mt-1 max-w-md text-sm leading-6 text-zinc-400">{{ __('Будьте першим, хто оцінить цю модель.') }}</p>
                </div>
            @endif
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 2: MAKES                                                    --}}
        {{-- ============================================================== --}}
        <div x-show="tab === 'makes'" x-cloak>
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ __('Фото 3D-друку') }}</h2>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-zinc-400">{{ __('Поділіться фото надрукованої моделі — допоможіть іншим побачити результат у реальному житті.') }}</p>
                </div>
                @auth
                    <button
                        type="button"
                        @click="showMakeForm = ! showMakeForm"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('Додати свій make') }}
                    </button>
                @endauth
            </div>

            {{-- Upload form --}}
            @auth
                <div x-show="showMakeForm" x-cloak x-transition class="mb-6 rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20">
                    <form method="POST" action="{{ route('products.makes.store', $product) }}" enctype="multipart/form-data" class="grid gap-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Фото надруку') }} <span class="text-rose-300">*</span></label>
                            <input type="file" name="image" accept="image/*" required class="mt-2 w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-emerald-400 file:px-4 file:py-2 file:text-xs file:font-bold file:text-zinc-950 file:hover:bg-emerald-300">
                            <p class="mt-1 text-[11px] text-zinc-500">{{ __('JPG / PNG / WEBP, до 6 MB.') }}</p>
                            @error('image')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Коментар') }}</label>
                            <textarea name="comment" rows="3" maxlength="1000" placeholder="{{ __('Розкажіть про матеріал, налаштування, результат...') }}" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"></textarea>
                            @error('comment')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[11px] text-zinc-500">{{ __('Фото буде опубліковано після перевірки автором моделі.') }}</p>
                            <div class="flex gap-2">
                                <button type="button" @click="showMakeForm = false" class="rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-semibold text-zinc-300 hover:bg-white/[0.08]">{{ __('Скасувати') }}</button>
                                <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-xs font-bold text-zinc-950 shadow shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Опублікувати') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm text-zinc-300">{{ __('Увійдіть, щоб поділитися своїм фото друку.') }}</p>
                    <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Увійти') }}</a>
                </div>
            @endauth

            {{-- Makes grid --}}
            @if($makes->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($makes as $make)
                        <article class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20 transition hover:border-white/20">
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-950">
                                <img src="{{ Storage::disk('public')->url($make->image_path) }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @if($make->status !== 'approved')
                                    <x-ui.status :status="$make->status" size="xs" class="absolute left-3 top-3 backdrop-blur" />
                                @endif
                            </div>
                            <div class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-400 text-[10px] font-black text-zinc-950">{{ mb_strtoupper(mb_substr($make->user->name ?? '?', 0, 1)) }}</span>
                                    <span class="truncate text-xs font-semibold text-white">{{ $make->user->name }}</span>
                                    <span class="ml-auto text-[10px] text-zinc-500">{{ $make->created_at->diffForHumans() }}</span>
                                </div>
                                @if($make->comment)
                                    <p class="mt-2 text-xs leading-5 text-zinc-300 line-clamp-3">{{ $make->comment }}</p>
                                @endif
                                @auth
                                    @if($canModerate || auth()->id() === $make->user_id)
                                        <div class="mt-3 flex flex-wrap gap-1.5 border-t border-white/5 pt-3">
                                            @if($canModerate && $make->status !== 'approved')
                                                <form method="POST" action="{{ route('products.makes.moderate', [$product, $make]) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button class="inline-flex h-7 items-center gap-1 rounded-lg bg-emerald-400/20 px-2.5 text-[10px] font-bold text-emerald-100 hover:bg-emerald-400/30">
                                                        <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                        {{ __('Схвалити') }}
                                                    </button>
                                                </form>
                                            @endif
                                            @if($canModerate && $make->status !== 'rejected')
                                                <form method="POST" action="{{ route('products.makes.moderate', [$product, $make]) }}">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button class="inline-flex h-7 items-center gap-1 rounded-lg bg-amber-400/20 px-2.5 text-[10px] font-bold text-amber-100 hover:bg-amber-400/30">
                                                        {{ __('Відхилити') }}
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('products.makes.destroy', [$product, $make]) }}" onsubmit="return confirm('{{ __('Видалити фото?') }}');" class="ml-auto">
                                                @csrf @method('DELETE')
                                                <button class="inline-flex h-7 items-center gap-1 rounded-lg bg-rose-400/20 px-2.5 text-[10px] font-bold text-rose-100 hover:bg-rose-400/30">
                                                    <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                                    {{ __('Видалити') }}
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                @endauth
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="grid place-items-center rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-300/[0.10] text-emerald-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-white">{{ __('Поки немає фото надрукованих моделей') }}</h3>
                    <p class="mt-1 max-w-md text-sm leading-6 text-zinc-400">{{ __('Будьте першим, хто додасть фото — це допомагає авторам і покупцям зорієнтуватись у якості моделі.') }}</p>
                </div>
            @endif
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 3: COMMENTS                                                 --}}
        {{-- ============================================================== --}}
        <div x-show="tab === 'comments'" x-cloak>
            <div class="mb-6">
                <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ __('Коментарі') }}</h2>
                <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('Запитання, поради з друку, відгуки про якість моделі.') }}</p>
            </div>

            @auth
                <form method="POST" action="{{ route('products.comments.store', $product) }}" class="mb-8 rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/20">
                    @csrf
                    <div class="flex items-start gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-400 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <textarea name="body" rows="3" minlength="2" maxlength="2000" required placeholder="{{ __('Поділіться думкою...') }}" class="block w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"></textarea>
                            @error('body')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                            <div class="mt-2 flex items-center justify-between text-[11px] text-zinc-500">
                                <span>{{ __('Будьте ввічливими — публікації порушень модератор приховає.') }}</span>
                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow shadow-emerald-500/25 hover:bg-emerald-300">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    {{ __('Додати коментар') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="mb-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                    <p class="text-sm text-zinc-300">{{ __('Увійдіть, щоб коментувати модель.') }}</p>
                    <a href="{{ route('login') }}" class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Увійти, щоб коментувати') }}</a>
                </div>
            @endauth

            @if($comments->isNotEmpty())
                <ul class="grid gap-3">
                    @foreach($comments as $comment)
                        <li class="rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/15">
                            <div class="flex items-start gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-400 text-xs font-black text-zinc-950">{{ mb_strtoupper(mb_substr($comment->user->name ?? '?', 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-bold text-white">{{ $comment->user->name }}</p>
                                        @if($comment->user_id === $product->user_id)
                                            <span class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-300/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-200">{{ __('автор') }}</span>
                                        @endif
                                        <span class="text-[11px] text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
                                        @auth
                                            @if(auth()->id() === $comment->user_id || $canModerate)
                                                <form method="POST" action="{{ route('products.comments.destroy', [$product, $comment]) }}" onsubmit="return confirm('{{ __('Видалити коментар?') }}');" class="ml-auto">
                                                    @csrf @method('DELETE')
                                                    <button class="text-[11px] text-zinc-500 transition hover:text-rose-300">{{ __('Видалити') }}</button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-200">{{ $comment->body }}</p>
                                    @if($comment->replies->isNotEmpty())
                                        <ul class="mt-4 grid gap-3 border-l border-white/[0.07] pl-5">
                                            @foreach($comment->replies as $reply)
                                                <li class="rounded-2xl border border-white/5 bg-zinc-950/40 p-4">
                                                    <div class="flex items-center gap-2">
                                                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-sky-400 text-[10px] font-black text-zinc-950">{{ mb_strtoupper(mb_substr($reply->user->name ?? '?', 0, 1)) }}</span>
                                                        <p class="text-xs font-bold text-white">{{ $reply->user->name }}</p>
                                                        <span class="text-[10px] text-zinc-500">{{ $reply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <p class="mt-1.5 whitespace-pre-line text-xs leading-5 text-zinc-300">{{ $reply->body }}</p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="grid place-items-center rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-14 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-sky-300/[0.10] text-sky-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-white">{{ __('Коментарів ще немає') }}</h3>
                    <p class="mt-1 max-w-md text-sm leading-6 text-zinc-400">{{ __('Залиште перший коментар — питання, відгук про друк або поради з налаштувань.') }}</p>
                </div>
            @endif
        </div>

        {{-- ============================================================== --}}
        {{-- TAB 4: SIMILAR                                                  --}}
        {{-- ============================================================== --}}
        <div x-show="tab === 'similar'" x-cloak>
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ __('Схожі 3D-моделі') }}</h2>
                    <p class="mt-1 max-w-xl text-sm leading-6 text-zinc-400">{{ __('Підбірка моделей з тієї ж категорії та зі схожими тегами.') }}</p>
                </div>
                <a href="{{ route('products.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/15 bg-white/[0.05] px-4 text-xs font-semibold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.08]">
                    {{ __('Усі моделі') }}
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>

            @if($similar->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($similar as $sim)
                        <x-ui.model-card :product="$sim" />
                    @endforeach
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <x-ui.placeholder-card :title="__('Miniature Dragon')" :subtitle="__('Деталізована 32mm міньятюра.')" price="180 грн" tone="emerald" icon="dragon" />
                    <x-ui.placeholder-card :title="__('Phone Stand')" :subtitle="__('Підставка для смартфона.')" :free="true" tone="rose" icon="phone" />
                    <x-ui.placeholder-card :title="__('Desk Organizer')" :subtitle="__('Модульний органайзер.')" price="80 грн" tone="violet" icon="organizer" />
                    <x-ui.placeholder-card :title="__('Wall Hook')" :subtitle="__('Декоративний гачок.')" price="60 грн" tone="sky" icon="hook" />
                </div>
            @endif
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- REPORT MODAL                                                          --}}
    {{-- =================================================================== --}}
    <div
        x-data="reportModal()"
        x-cloak
        x-show="open"
        @keydown.escape.window="close()"
        @open-report-modal.window="open = true"
        class="fixed inset-0 z-[9999] grid place-items-center px-4 py-6"
        role="dialog"
        aria-modal="true"
    >
        <div x-show="open" x-transition.opacity @click="close()" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/60 backdrop-blur-xl"
        >
            <div class="border-b border-white/10 px-6 py-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-2 rounded-full border border-rose-300/25 bg-rose-300/[0.08] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-rose-200">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            {{ __('Скарга') }}
                        </div>
                        <h2 class="mt-2 text-xl font-black text-white">{{ __('Повідомити про проблему') }}</h2>
                        <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Опишіть, що саме не так — модератори отримають повідомлення.') }}</p>
                    </div>
                    <button type="button" @click="close()" class="grid h-8 w-8 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/[0.08]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('products.report', $product) }}" class="grid gap-4 px-6 py-5">
                @csrf
                <label class="grid gap-1.5">
                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Причина') }} <span class="text-rose-300">*</span></span>
                    <select name="reason" required class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                        @foreach(\App\Models\ProductReport::REASONS as $key => $label)
                            <option value="{{ $key }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1.5">
                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Опис') }}</span>
                    <textarea name="message" rows="4" maxlength="2000" placeholder="{{ __('Що саме не працює, які файли биті, які умови ліцензії порушено тощо.') }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"></textarea>
                </label>

                <div class="flex items-start gap-2 rounded-xl border border-white/5 bg-zinc-950/40 px-3 py-2.5 text-[11px] leading-5 text-zinc-400">
                    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-300/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    <span>{{ __('Скарга надходить у чергу модерації. Ми переглянемо її протягом 24 годин і повідомимо про результат.') }}</span>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" @click="close()" class="rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-semibold text-zinc-300 hover:bg-white/[0.08]">{{ __('Скасувати') }}</button>
                    <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-rose-500/25 transition hover:bg-rose-300">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        {{ __('Надіслати скаргу') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Download / slicer modal --}}
    @auth
        <x-ui.download-modal />
        <x-ui.contact-modal />
    @endauth

    <script>
        if (typeof window.productTabs === 'undefined') {
            const VALID = ['info', 'profile', 'reviews', 'makes', 'comments', 'similar'];
            window.productTabs = function () {
                return {
                    tab: VALID.includes((location.hash || '').replace('#', '')) ? location.hash.replace('#', '') : 'info',
                    showMakeForm: false,
                    init() {
                        window.addEventListener('hashchange', () => {
                            const h = location.hash.replace('#', '');
                            if (VALID.includes(h)) this.tab = h;
                        });
                    },
                    setTab(name) {
                        this.tab = name;
                        history.replaceState(null, '', '#' + name);
                    },
                };
            };
        }
        if (typeof window.reportModal === 'undefined') {
            window.reportModal = function () {
                return {
                    open: false,
                    close() { this.open = false; },
                };
            };
        }
    </script>
</x-layouts.marketplace>
