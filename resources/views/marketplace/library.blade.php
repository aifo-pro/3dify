<x-layouts.marketplace
    :seo-title="__('library.title') . ' · 3Dify'"
    :seo-description="__('library.description')"
>
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-black text-white">{{ __('library.title') }}</h1>
            <p class="mt-2 text-zinc-400">{{ __('library.subtitle') }}</p>
        </div>

        @if($orders->isEmpty())
            <div class="rounded-2xl border border-white/10 bg-zinc-900/50 px-8 py-16 text-center">
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-2xl bg-zinc-800">
                    <svg class="h-8 w-8 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </div>
                <p class="text-lg font-semibold text-white">{{ __('library.empty_title') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('library.empty_hint') }}</p>
                <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center rounded-xl bg-emerald-400 px-6 py-2.5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">
                    {{ __('library.browse_catalog') }}
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($orders as $order)
                    @foreach($order->items as $item)
                        @php $product = $item->product; @endphp
                        @if(! $product) @continue @endif

                        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-900/50">
                            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:gap-6">

                                {{-- Cover --}}
                                <a href="{{ route('products.show', $product) }}" class="shrink-0">
                                    <div class="h-20 w-20 overflow-hidden rounded-xl bg-zinc-800 sm:h-24 sm:w-24">
                                        @if($product->cover_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_path) }}"
                                                 alt="{{ $product->localized('title') }}"
                                                 class="h-full w-full object-cover">
                                        @else
                                            <div class="grid h-full w-full place-items-center text-2xl font-black text-zinc-600">3D</div>
                                        @endif
                                    </div>
                                </a>

                                {{-- Info --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <a href="{{ route('products.show', $product) }}" class="text-base font-bold text-white transition hover:text-emerald-300">
                                                {{ $product->localized('title') }}
                                            </a>
                                            <p class="mt-0.5 text-xs text-zinc-500">
                                                {{ __('library.by') }} {{ $product->author?->displayName() }}
                                                · {{ optional($order->paid_at ?? $order->updated_at)->translatedFormat('d M Y') }}
                                                · {{ ucfirst($item->license_type) }}
                                            </p>
                                        </div>
                                        <span class="shrink-0 rounded-full border border-emerald-400/25 bg-emerald-400/10 px-2.5 py-0.5 text-xs font-bold text-emerald-300">
                                            {{ __('library.purchased') }}
                                        </span>
                                    </div>

                                    {{-- Files --}}
                                    @if($product->files->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($product->files as $file)
                                                <a href="{{ route('products.download', [$product, $file]) }}"
                                                   class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-zinc-800/60 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:border-emerald-400/30 hover:bg-emerald-400/10 hover:text-emerald-200">
                                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                                    {{ $file->original_name }}
                                                    <span class="text-zinc-600">{{ strtoupper($file->extension) }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="mt-2 text-xs text-zinc-600">{{ __('library.no_files') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>

            @if($orders->hasPages())
                <div class="mt-8">{{ $orders->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.marketplace>
