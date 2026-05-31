@php
    $title = $bundle->localized('title');
    $description = $bundle->localized('description');
@endphp

<x-layouts.marketplace :seo-title="$title . ' · Bundle · 3Dify'" :seo-description="$description">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        {{-- Header --}}
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12 lg:items-start">

            {{-- Cover --}}
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-zinc-900 aspect-video">
                @if($bundle->coverUrl())
                    <img src="{{ $bundle->coverUrl() }}" alt="{{ $title }}" class="h-full w-full object-cover">
                @else
                    <div class="grid h-full w-full place-items-center text-5xl font-black text-zinc-700">BUNDLE</div>
                @endif
            </div>

            {{-- Info --}}
            <div>
                <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-black uppercase tracking-widest text-emerald-300">Bundle · {{ $bundle->items->count() }} models</span>
                <h1 class="mt-4 text-3xl font-black text-white">{{ $title }}</h1>
                @if($description)
                    <p class="mt-3 text-zinc-400 leading-relaxed">{{ $description }}</p>
                @endif

                {{-- Pricing --}}
                <div class="mt-6 flex items-end gap-4">
                    <span class="text-4xl font-black text-white">{{ number_format((float) $bundle->price, 2) }} {{ $bundle->currency }}</span>
                    @if($bundle->originalTotal() > (float) $bundle->price)
                        <div class="mb-1">
                            <span class="text-lg text-zinc-600 line-through">{{ number_format($bundle->originalTotal(), 2) }} {{ $bundle->currency }}</span>
                            <span class="ml-2 rounded-full bg-red-500/20 px-2.5 py-0.5 text-sm font-bold text-red-400">-{{ $bundle->discount_percent }}%</span>
                        </div>
                    @endif
                </div>

                @if($bundle->savings() > 0)
                    <p class="mt-1 text-sm text-emerald-400">You save {{ number_format($bundle->savings(), 2) }} {{ $bundle->currency }}</p>
                @endif

                @auth
                    <form action="{{ route('bundles.checkout', $bundle) }}" method="POST" class="mt-6">
                        @csrf
                        <button type="submit" class="w-full rounded-2xl bg-emerald-400 py-3.5 text-base font-black text-zinc-950 transition hover:bg-emerald-300">
                            Buy Bundle →
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="mt-6 block w-full rounded-2xl bg-emerald-400 py-3.5 text-center text-base font-black text-zinc-950 transition hover:bg-emerald-300">
                        Login to Buy
                    </a>
                @endauth
            </div>
        </div>

        {{-- Models in bundle --}}
        <div class="mt-12">
            <h2 class="mb-5 text-xl font-black text-white">Models in this bundle</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($bundle->items as $product)
                    <div class="flex items-start gap-4 rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-4">
                        <a href="{{ route('products.show', $product) }}" class="shrink-0">
                            <div class="h-16 w-16 overflow-hidden rounded-xl bg-zinc-800">
                                @if($product->cover_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_path) }}" class="h-full w-full object-cover">
                                @else
                                    <div class="grid h-full w-full place-items-center text-xl font-black text-zinc-600">3D</div>
                                @endif
                            </div>
                        </a>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('products.show', $product) }}" class="block text-sm font-bold text-white truncate hover:text-emerald-300">{{ $product->localized('title') }}</a>
                            <p class="text-xs text-zinc-500">by {{ $product->author?->displayName() }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-xs text-zinc-400">{{ $product->is_free ? 'Free' : number_format((float)$product->price, 2).' '.$product->currency }}</span>
                                @if($owned[$product->id] ?? false)
                                    <span class="rounded-full bg-emerald-400/15 px-2 py-0.5 text-[10px] font-bold text-emerald-400">Owned</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.marketplace>
