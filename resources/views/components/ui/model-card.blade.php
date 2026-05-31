@props(['product'])

@php
    $imagePreview = $product->relationLoaded('files')
        ? $product->files->first(fn ($file) => $file->is_preview && in_array($file->extension, ['gif', 'png', 'jpg', 'jpeg', 'webp'], true))
        : null;
    $imagePath = $product->cover_path
        ?: ($product->gallery[0] ?? null)
        ?: ($imagePreview?->disk === 'public' ? $imagePreview->path : null);
    $imageUrl = $imagePath && Storage::disk('public')->exists($imagePath)
        ? Storage::disk('public')->url($imagePath)
        : null;
@endphp

<div class="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/[0.06] shadow-xl shadow-black/20 transition duration-300 hover:-translate-y-1 hover:border-emerald-300/40 hover:bg-white/[0.09]">
    @auth
        <div class="absolute right-4 top-4 z-10 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
            <x-ui.wishlist-button :product="$product" variant="icon" size="sm" />
        </div>
    @endauth
<a href="{{ route('products.show', $product) }}" class="block">
    <div class="relative aspect-[4/3] overflow-hidden bg-zinc-900">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product->localized('title') }}" width="400" height="300" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center bg-[linear-gradient(135deg,#101827,#06352d)]">
                <div class="grid h-24 w-24 place-items-center rounded-3xl border border-white/10 bg-white/10 text-4xl font-black text-emerald-200 shadow-2xl shadow-emerald-500/10">3D</div>
            </div>
        @endif
        <div class="absolute left-4 top-4 flex flex-wrap items-center gap-2">
            <x-ui.badge :variant="$product->is_free ? 'free' : 'paid'">{{ $product->is_free ? __('Безкоштовно') : __('Платна') }}</x-ui.badge>
            @if($product->commercial_license_enabled)
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-100">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    {{ __('Commercial') }}
                </span>
            @endif
        </div>
        <div class="absolute bottom-4 right-4 rounded-full border border-white/10 bg-zinc-950/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $product->display_price }}</div>
    </div>
    <div class="p-5">
        <div class="flex items-start justify-between gap-4">
            <h3 class="line-clamp-2 text-lg font-semibold leading-snug text-white group-hover:text-emerald-100">{{ $product->localized('title') }}</h3>
        </div>
        <p class="mt-3 line-clamp-2 min-h-10 text-sm leading-5 text-zinc-400">{{ $product->localized('short_description') ?: __('Готова до друку 3D-модель з перевіреними файлами.') }}</p>
        @if($product->license)
            <div class="mt-3">
                <x-license-badge :license="$product->license" size="sm" :tooltip="false" />
            </div>
        @endif
        <div class="mt-4 flex items-center justify-between gap-3 border-t border-white/10 pt-4 text-xs text-zinc-500">
            <a href="{{ route('authors.show', $product->author) }}" class="inline-flex items-center gap-1.5 truncate hover:text-emerald-300 transition">
                {{ $product->author->name }}
                <x-ui.verified-badge :user="$product->author" size="xs" :show-label="false" />
            </a>
            <div class="flex shrink-0 items-center gap-3 text-zinc-500">
                @if($product->downloads_count > 0)
                    <span class="flex items-center gap-1" title="{{ __('Завантажень') }}">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        {{ $product->downloads_count >= 1000 ? round($product->downloads_count / 1000, 1).'k' : $product->downloads_count }}
                    </span>
                @endif
                @if($product->reviews()->exists() || ($product->relationLoaded('reviews') && $product->reviews->isNotEmpty()))
                    <span class="flex items-center gap-0.5 text-amber-400/80" title="{{ __('Рейтинг') }}">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        {{ number_format($product->reviews_avg_rating ?? $product->reviews()->avg('rating') ?? 0, 1) }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</a>
</div>
