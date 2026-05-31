<x-layouts.marketplace
    :seo-title="__('makes.title') . ' · 3Dify'"
    :seo-description="__('makes.description')"
>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        <div class="mb-10">
            <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-black uppercase tracking-widest text-emerald-300">{{ __('makes.badge') }}</span>
            <h1 class="mt-4 text-4xl font-black text-white sm:text-5xl">{{ __('makes.title') }}</h1>
            <p class="mt-3 max-w-2xl text-zinc-400">{{ __('makes.subtitle') }}</p>
        </div>

        @if($makes->isEmpty())
            <x-ui.empty-state :title="__('makes.empty_title')" :description="__('makes.empty_hint')" />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($makes as $make)
                    @php
                        $imgUrl = $make->image_path
                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($make->image_path)
                            : null;
                    @endphp
                    <div class="group overflow-hidden rounded-2xl border border-white/[0.07] bg-zinc-900/50 transition hover:border-emerald-400/25">
                        @if($imgUrl)
                            <div class="aspect-square overflow-hidden bg-zinc-950">
                                <img src="{{ $imgUrl }}" alt="{{ $make->product?->localized('title') }}"
                                     loading="lazy" width="400" height="400"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        @else
                            <div class="grid aspect-square place-items-center bg-zinc-900 text-4xl font-black text-zinc-700">3D</div>
                        @endif

                        <div class="p-4">
                            @if($make->product)
                                <a href="{{ route('products.show', $make->product) }}" class="block text-sm font-bold text-white transition hover:text-emerald-300 line-clamp-1">
                                    {{ $make->product->localized('title') }}
                                </a>
                            @endif
                            @if($make->note)
                                <p class="mt-1 line-clamp-2 text-xs text-zinc-400">{{ $make->note }}</p>
                            @endif
                            <div class="mt-3 flex items-center gap-2 text-xs text-zinc-500">
                                <span class="font-semibold text-zinc-400">{{ $make->user?->displayName() }}</span>
                                <span>·</span>
                                <time>{{ $make->created_at->diffForHumans() }}</time>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($makes->hasPages())
                <div class="mt-10">{{ $makes->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.marketplace>
