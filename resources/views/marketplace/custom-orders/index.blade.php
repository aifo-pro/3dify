<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="inline-flex rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-3 py-1 text-[11px] font-black uppercase tracking-[0.18em] text-emerald-200">3Dify services</p>
                <h1 class="mt-5 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('custom_orders.title') }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('custom_orders.create_hint') }}</p>
            </div>
            <a href="{{ route('custom-orders.create') }}" class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-300">
                {{ __('custom_orders.new_order') }}
            </a>
        </div>

        @if(session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
        @endif

        <div class="mt-8 flex flex-wrap gap-2">
            <a href="{{ route('custom-orders.index') }}" class="rounded-full px-4 py-2 text-xs font-bold {{ $scope !== 'author' ? 'bg-emerald-400 text-zinc-950' : 'border border-white/10 bg-white/[0.04] text-zinc-300' }}">{{ __('custom_orders.my_orders') }}</a>
            <a href="{{ route('custom-orders.index', ['scope' => 'author']) }}" class="rounded-full px-4 py-2 text-xs font-bold {{ $scope === 'author' ? 'bg-emerald-400 text-zinc-950' : 'border border-white/10 bg-white/[0.04] text-zinc-300' }}">{{ __('custom_orders.author_orders') }}</a>
        </div>

        <div class="mt-6 grid gap-4">
            @forelse($orders as $order)
                <a href="{{ route('custom-orders.show', $order) }}" class="group overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/20 transition hover:border-emerald-300/35 hover:bg-white/[0.06]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-200">{{ $order->statusLabel() }}</span>
                                <span class="text-xs font-semibold text-zinc-500">{{ $order->number }}</span>
                                <span class="text-xs text-zinc-600">·</span>
                                <span class="text-xs text-zinc-400">{{ $order->typeLabel() }}</span>
                            </div>
                            <h2 class="mt-3 truncate text-lg font-black text-white transition group-hover:text-emerald-100">{{ $order->title }}</h2>
                            <p class="mt-1 line-clamp-1 text-sm text-zinc-500">
                                {{ __('Від') }} {{ $order->buyer?->displayName() }} · {{ __('Автор') }} {{ $order->author?->displayName() ?: '—' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-left sm:text-right">
                            <p class="text-xl font-black text-white">{{ $order->price ? number_format((float) $order->price, 2).' UAH' : ($order->budget_amount ? number_format((float) $order->budget_amount, 2).' UAH' : '—') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $order->updated_at->translatedFormat('d M Y H:i') }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-3xl border border-dashed border-white/15 bg-white/[0.03] px-6 py-14 text-center">
                    <p class="text-xl font-black text-white">{{ __('custom_orders.no_orders') }}</p>
                    <p class="mt-2 text-sm text-zinc-500">{{ __('custom_orders.no_orders_hint') }}</p>
                    <a href="{{ route('custom-orders.create') }}" class="mt-6 inline-flex h-11 items-center rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950">{{ __('custom_orders.new_order') }}</a>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $orders->links() }}</div>
    </section>
</x-layouts.marketplace>
