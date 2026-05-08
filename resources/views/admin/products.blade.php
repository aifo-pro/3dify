<x-layouts.admin
    :title="__('Моделі та модерація')"
    :description="__('Керуйте статусами публікацій, виділяйте Featured-роботи та залишайте коментарі модерації.')"
    active="products"
    :breadcrumbs="[['label' => __('Моделі')]]"
>
    <x-slot:actions>
        <a href="{{ route('admin.products.featured') }}" class="inline-flex h-9 items-center gap-2 rounded-full border border-amber-300/30 bg-amber-300/[0.10] px-4 text-xs font-semibold text-amber-100 hover:bg-amber-300/[0.16]">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            {{ __('Featured-менеджер') }}
        </a>
        <a href="{{ route('admin.products') }}?status=pending" class="inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-200 hover:bg-white/10">{{ __('Тільки на модерації') }}</a>
        <a href="{{ route('products.index') }}" target="_blank" class="inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-200 hover:bg-white/10">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
            {{ __('Каталог') }}
        </a>
    </x-slot:actions>

    @if(session('status'))
        <div class="mb-4 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <x-admin.bulk-bar :action="route('admin.bulk.products')" :actions="[
        ['value' => 'publish', 'label' => __('Опублікувати')],
        ['value' => 'reject', 'label' => __('Відхилити')],
        ['value' => 'archive', 'label' => __('Архівувати')],
        ['value' => 'feature', 'label' => __('Додати у Featured')],
        ['value' => 'unfeature', 'label' => __('Прибрати з Featured')],
        ['value' => 'delete', 'label' => __('Видалити')],
    ]" />

    <div class="mb-3 flex items-center gap-2">
        <button type="button" onclick="document.querySelector('input.bulk-all').click()" class="inline-flex h-7 items-center rounded-md border border-white/10 bg-white/[0.04] px-2 text-[11px] font-bold text-white hover:bg-white/[0.10]">{{ __('Виділити все на сторінці') }}</button>
    </div>

    <div class="grid gap-3">
        @forelse($products as $product)
            <div class="grid gap-3 rounded-3xl border border-white/10 bg-white/[0.04] p-4 shadow-xl shadow-black/20 lg:grid-cols-[auto_auto_minmax(0,1fr)_160px_minmax(0,1fr)_auto] lg:items-center">
                <input type="checkbox" class="bulk-row mt-2 h-4 w-4 self-start rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40" value="{{ $product->id }}">

                <div class="grid h-14 w-14 place-items-center overflow-hidden rounded-xl border border-white/10 bg-zinc-950/70 text-xs font-bold text-emerald-100">
                    @if($product->cover_path)
                        <img src="{{ Storage::disk('public')->url($product->cover_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        3D
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-semibold text-white">{{ $product->localized('title') ?: __('Без назви') }}</p>
                        <x-admin.status-pill :status="$product->status" />
                        <form method="POST" action="{{ route('admin.products.toggle-featured', $product) }}">
                            @csrf @method('PATCH')
                            <button class="grid h-6 w-6 place-items-center rounded-md border border-white/10 transition {{ $product->is_featured ? 'border-amber-300/40 bg-amber-300/[0.16] text-amber-200' : 'bg-white/[0.04] text-zinc-500 hover:text-amber-200' }}" title="{{ __('Featured') }}">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="{{ $product->is_featured ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </button>
                        </form>
                    </div>
                    <p class="mt-1 truncate text-xs text-zinc-500">
                        {{ $product->author?->name ?? '—' }} · {{ $product->display_price ?? '—' }}
                        @if($product->category) · {{ $product->category->localized('name') }} @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.products.moderate', $product) }}" class="contents">
                    @csrf @method('PATCH')
                    <select name="status" class="h-10 rounded-xl border border-white/10 bg-zinc-950/70 px-3 text-sm text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                        @foreach(\App\Models\Product::STATUSES as $status)
                            <option @selected($product->status === $status)>{{ $status }}</option>
                        @endforeach
                    </select>

                    <input
                        name="moderation_note"
                        value="{{ $product->moderation_note }}"
                        placeholder="{{ __('Коментар модератора') }}"
                        class="h-10 rounded-xl border border-white/10 bg-zinc-950/70 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"
                    >

                    <div class="flex items-center gap-2">
                        <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:bg-white/10" title="{{ __('Переглянути') }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <button class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Зберегти') }}</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-12 text-center">
                <p class="text-sm font-semibold text-white">{{ __('Моделей поки немає') }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Очікуйте першу публікацію автора.') }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
</x-layouts.admin>
