@props([
    'product',
    'size' => 'md',
    'variant' => 'pill',
])

@php
    $isWishlisted = auth()->check() && auth()->user()->wishlist()->where('product_id', $product->id)->exists();
    $sizeCls = $size === 'sm' ? 'h-8 w-8' : 'h-10 w-10';
    $iconSize = $size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4';
    $route = auth()->check() ? route('wishlist.toggle', $product) : route('login');
@endphp

@if($variant === 'icon')
    <form method="POST" action="{{ $route }}" {{ $attributes }} class="inline-flex">
        @if(auth()->check())@csrf @endif
        <button
            type="submit"
            title="{{ $isWishlisted ? __('Видалити з обраного') : __('Додати в обране') }}"
            class="grid {{ $sizeCls }} place-items-center rounded-full border backdrop-blur transition {{ $isWishlisted ? 'border-rose-300/40 bg-rose-300/[0.15] text-rose-200 hover:bg-rose-300/[0.20]' : 'border-white/10 bg-zinc-950/60 text-zinc-300 hover:border-rose-300/30 hover:bg-rose-300/[0.10] hover:text-rose-200' }}"
        >
            <svg class="{{ $iconSize }}" viewBox="0 0 24 24" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </button>
    </form>
@else
    <form method="POST" action="{{ $route }}" {{ $attributes }} class="inline-flex">
        @if(auth()->check())@csrf @endif
        <button
            type="submit"
            class="inline-flex h-10 items-center gap-1.5 whitespace-nowrap rounded-xl border px-4 text-xs font-bold transition {{ $isWishlisted ? 'border-rose-300/40 bg-rose-300/[0.10] text-rose-100 hover:bg-rose-300/[0.15]' : 'border-white/10 bg-white/[0.05] text-white hover:border-rose-300/30 hover:bg-rose-300/[0.08] hover:text-rose-200' }}"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            {{ $isWishlisted ? __('В обраному') : __('В обране') }}
        </button>
    </form>
@endif
