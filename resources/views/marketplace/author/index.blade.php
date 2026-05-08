@php
    $statusCounts = collect(['published', 'pending', 'draft', 'rejected', 'archived'])->mapWithKeys(function ($s) use ($products) {
        return [$s => $products->getCollection()->where('status', $s)->count() + 0];
    });
@endphp

<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <x-ui.badge>{{ __('Авторський кабінет') }}</x-ui.badge>
                <h1 class="mt-4 text-4xl font-black tracking-tight text-white">{{ __('Мої 3D-моделі') }}</h1>
                <p class="mt-3 max-w-2xl text-zinc-400">{{ __('Керуйте статусами, цінами, файлами та описами моделей перед публікацією або після модерації.') }}</p>
            </div>
            <x-ui.button :href="route('author.products.create')">{{ __('Додати модель') }}</x-ui.button>
        </div>

        <x-ui.card class="overflow-hidden">
            <div class="hidden grid-cols-[minmax(0,1fr)_180px_140px_140px] border-b border-white/10 bg-white/[0.04] px-5 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500 md:grid">
                <span>{{ __('Назва') }}</span>
                <span>{{ __('Статус') }}</span>
                <span>{{ __('Ціна') }}</span>
                <span class="text-right">{{ __('Дія') }}</span>
            </div>
            <div class="divide-y divide-white/10">
                @forelse($products as $product)
                    <div class="grid gap-4 px-5 py-5 text-sm md:grid-cols-[minmax(0,1fr)_180px_140px_140px] md:items-center">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($product->cover_path)
                                <img src="{{ Storage::disk('public')->url($product->cover_path) }}" alt="" class="h-11 w-11 shrink-0 rounded-xl border border-white/10 object-cover">
                            @else
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-white/10 bg-zinc-900 text-[10px] font-bold text-emerald-200">3D</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white">{{ $product->localized('title') }}</p>
                                <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $product->slug }}</p>
                            </div>
                        </div>
                        <div>
                            <x-ui.status :status="$product->status" />
                            @if($product->status === 'rejected' && $product->moderation_note)
                                <p class="mt-1 text-[11px] leading-4 text-rose-300/80" title="{{ $product->moderation_note }}">{{ Str::limit($product->moderation_note, 60) }}</p>
                            @endif
                        </div>
                        <span class="font-semibold text-zinc-200">{{ $product->display_price }}</span>
                        <a class="inline-flex items-center justify-end gap-1.5 text-right font-semibold text-emerald-200 transition hover:text-emerald-100" href="{{ route('author.products.edit', $product) }}">
                            {{ __('Редагувати') }}
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>
                @empty
                    <div class="p-6">
                        <x-ui.empty-state :title="__('Моделей ще немає')" :description="__('Створіть першу модель і пройдіть publish wizard.')" :href="route('author.products.create')" :action="__('Опублікувати')" />
                    </div>
                @endforelse
            </div>
        </x-ui.card>
        <div class="mt-6">{{ $products->links() }}</div>
    </section>
</x-layouts.marketplace>
