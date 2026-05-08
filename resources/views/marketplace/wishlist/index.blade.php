<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8">
            <x-ui.badge>{{ __('Обране') }}</x-ui.badge>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Моє обране') }}</h1>
            <p class="mt-3 max-w-3xl text-zinc-400">{{ __('Зберігайте моделі та повертайтеся до них пізніше — натисніть сердечко на будь-якій моделі.') }}</p>
        </header>

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif

        @if($items->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($items as $product)
                    <div class="relative">
                        <x-ui.model-card :product="$product" />
                        <div class="absolute right-4 top-4 z-10">
                            <x-ui.wishlist-button :product="$product" variant="icon" size="md" />
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-8">{{ $items->links() }}</div>
        @else
            <div class="grid place-items-center rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-20 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-rose-300/[0.10] text-rose-200">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </span>
                <h2 class="mt-4 text-xl font-bold text-white">{{ __('Ваше обране поки порожнє') }}</h2>
                <p class="mt-1 max-w-md text-sm text-zinc-400">{{ __('Знайдіть моделі, які вам подобаються, і додавайте їх в обране — вони збиратимуться тут.') }}</p>
                <a href="{{ route('products.index') }}" class="mt-5 inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Перейти у каталог') }}</a>
            </div>
        @endif
    </section>
</x-layouts.marketplace>
