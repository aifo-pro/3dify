@php $isEdit = $bundle->exists; @endphp

<x-layouts.admin
    :title="$isEdit ? 'Редагувати бандл' : 'Новий бандл'"
    :breadcrumb-current="$isEdit ? 'Редагувати' : 'Створити'"
    :breadcrumbs="[['label' => 'Бандли', 'url' => route('admin.bundles.index')]]"
>
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
            <ul class="grid gap-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.bundles.update', $bundle) : route('admin.bundles.store') }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Main form --}}
            <div class="space-y-5 lg:col-span-2">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <h2 class="mb-5 text-sm font-black uppercase tracking-widest text-zinc-400">Основне</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-zinc-400">Назва (UK) *</label>
                            <input name="title_uk" value="{{ old('title_uk', $bundle->localized('title','uk')) }}" required
                                   class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-zinc-400">Назва (EN)</label>
                            <input name="title_en" value="{{ old('title_en', $bundle->localized('title','en')) }}"
                                   class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-zinc-400">Опис (UK)</label>
                            <textarea name="description_uk" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">{{ old('description_uk', $bundle->localized('description','uk')) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-zinc-400">Ціна (UAH) *</label>
                            <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $bundle->price) }}" required
                                   class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-zinc-400">Знижка %</label>
                            <input name="discount_percent" type="number" min="0" max="99" value="{{ old('discount_percent', $bundle->discount_percent) }}"
                                   class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                        </div>
                    </div>

                    <label class="mt-5 flex items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bundle->is_active))
                               class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400">
                        <span class="text-sm text-zinc-300">Активний (видимий на сайті)</span>
                    </label>
                </div>

                {{-- Product selection --}}
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <h2 class="mb-4 text-sm font-black uppercase tracking-widest text-zinc-400">Моделі в бандлі</h2>
                    <p class="mb-4 text-xs text-zinc-500">Оберіть моделі — порядок відповідає порядку вибору.</p>

                    <div class="mb-3">
                        <input type="text" id="product-search" placeholder="Пошук моделі..."
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none">
                    </div>

                    @php $selectedIds = old('product_ids', $bundle->items->pluck('id')->toArray()); @endphp

                    <div class="max-h-80 space-y-1 overflow-y-auto pr-1" id="product-list">
                        @foreach($products as $product)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-white/[0.04] product-item" data-name="{{ strtolower($product->localized('title')) }}">
                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                                       @checked(in_array($product->id, $selectedIds))
                                       class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-white">{{ $product->localized('title') }}</p>
                                    <p class="text-xs text-zinc-500">{{ $product->author?->displayName() }} · {{ number_format((float)$product->price, 2) }} UAH</p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @if($selectedIds)
                        <p class="mt-3 text-xs text-emerald-400">Обрано: {{ count($selectedIds) }} моделей</p>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-5">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <h2 class="mb-4 text-xs font-black uppercase tracking-widest text-zinc-500">Обкладинка</h2>
                    @if($bundle->cover_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($bundle->cover_path) }}" class="mb-3 h-32 w-full rounded-xl object-cover">
                    @endif
                    <input type="file" name="cover" accept="image/*" class="w-full text-sm text-zinc-400 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-400/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-300">
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <div class="grid gap-3">
                        <button type="submit" class="w-full rounded-xl bg-emerald-400 py-3 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">
                            {{ $isEdit ? 'Зберегти зміни' : 'Створити бандл' }}
                        </button>
                        <a href="{{ route('admin.bundles.index') }}" class="block w-full rounded-xl border border-white/10 py-2.5 text-center text-sm text-zinc-400 hover:bg-white/[0.04]">Скасувати</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('product-search').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.product-item').forEach(item => {
                item.style.display = item.dataset.name.includes(q) ? '' : 'none';
            });
        });
    </script>
</x-layouts.admin>
