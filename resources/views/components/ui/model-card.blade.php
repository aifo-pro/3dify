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
            <img src="{{ $imageUrl }}" alt="{{ $product->localized('title') }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center bg-[linear-gradient(135deg,#101827,#06352d)]">
                <div class="grid h-24 w-24 place-items-center rounded-3xl border border-white/10 bg-white/10 text-4xl font-black text-emerald-200 shadow-2xl shadow-emerald-500/10">3D</div>
            </div>
        @endif
        <div class="absolute left-4 top-4">
            <x-ui.badge :variant="$product->is_free ? 'free' : 'paid'">{{ $product->is_free ? __('Безкоштовно') : __('Платна') }}</x-ui.badge>
        </div>
        <div class="absolute bottom-4 right-4 rounded-full border border-white/10 bg-zinc-950/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $product->display_price }}</div>
    </div>
    <div class="p-5">
        <div class="flex items-start justify-between gap-4">
            <h3 class="line-clamp-2 text-lg font-semibold leading-snug text-white group-hover:text-emerald-100">{{ $product->localized('title') }}</h3>
        </div>
        <p class="mt-3 line-clamp-2 min-h-10 text-sm leading-5 text-zinc-400">{{ $product->localized('short_description') ?: __('Готова до друку 3D-модель з перевіреними файлами.') }}</p>
        <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/10 pt-4 text-xs text-zinc-500">
            <span class="inline-flex items-center gap-1.5 truncate">
                {{ $product->author->name }}
                <x-ui.verified-badge :user="$product->author" size="xs" :show-label="false" />
            </span>
            <span class="truncate text-zinc-400">{{ $product->category?->localized('name') ?? __('3D модель') }}</span>
        </div>
    </div>
</a>
</div>
