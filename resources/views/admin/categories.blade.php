@php
    $editing = old('_edit_id') ? $categories->firstWhere('id', old('_edit_id')) : null;
@endphp

<x-layouts.admin
    :title="__('Категорії')"
    :description="__('Розділи каталогу: структура, порядок та видимість.')"
    active="categories"
    :breadcrumbs="[['label' => __('Каталог'), 'href' => route('admin.products')], ['label' => __('Категорії')]]"
>
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.categories') }}" class="relative">
            <input
                type="search"
                name="q"
                value="{{ $q }}"
                placeholder="{{ __('Пошук за slug…') }}"
                class="h-9 w-56 rounded-full border border-white/10 bg-white/[0.04] pl-9 pr-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"
            >
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </span>
        </form>
        <button
            x-data
            @click="$dispatch('open-form', { mode: 'create' })"
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Нова категорія') }}
        </button>
    </x-slot:actions>

    {{-- Mini stats --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Усього категорій') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($totalCount) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Моделей у каталозі') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($productsCount) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Активних') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $categories->where('is_active', true)->count() }}</p>
        </div>
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div class="mt-4 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Table --}}
    <div class="mt-5">
        <x-admin.section :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-950/40">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                            <th class="px-5 py-3 w-10"></th>
                            <th class="px-5 py-3">{{ __('Назва') }}</th>
                            <th class="px-5 py-3">Slug</th>
                            <th class="px-5 py-3">{{ __('Батьківська') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Сорт.') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Моделей') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Статус') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('Дії') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($categories as $category)
                            <tr class="transition hover:bg-white/[0.02]">
                                <td class="px-5 py-3">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-300/15 text-xs font-black text-emerald-100">
                                        {{ mb_strtoupper(mb_substr($category->localized('name') ?: '?', 0, 1)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-white">{{ $category->localized('name') }}</p>
                                    @php $desc = $category->localized('description'); @endphp
                                    @if($desc)
                                        <p class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $desc }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-zinc-300">/{{ $category->slug }}</td>
                                <td class="px-5 py-3 text-xs text-zinc-400">
                                    {{ $category->parent ? $category->parent->localized('name') : '—' }}
                                </td>
                                <td class="px-5 py-3 text-center text-xs text-zinc-300">{{ $category->sort_order }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $category->products_count }}</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <x-admin.status-pill :status="$category->is_active ? 'active' : 'archived'">
                                        {{ $category->is_active ? __('активна') : __('прихована') }}
                                    </x-admin.status-pill>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            x-data
                                            @click="$dispatch('open-form', { mode: 'edit', id: {{ $category->id }} })"
                                            class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100"
                                            title="{{ __('Редагувати') }}"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('{{ __('Видалити категорію?') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100" title="{{ __('Видалити') }}">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <div class="grid place-items-center">
                                        <div class="grid h-12 w-12 place-items-center rounded-full bg-white/[0.06] text-zinc-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-white">{{ $q ? __('Нічого не знайдено') : __('Категорій ще немає') }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $q ? __('Спробуйте інший пошуковий запит.') : __('Додайте першу категорію, щоб почати наповнювати каталог.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.section>

        @if($categories->hasPages())
            <div class="mt-5">{{ $categories->links() }}</div>
        @endif
    </div>

    {{-- Form panel (slide-over) --}}
    <div
        x-data="{
            open: false,
            mode: 'create',
            data: { id: null, slug: '', name_uk: '', name_en: '', description_uk: '', parent_id: '', sort_order: 0, is_active: true },
            items: @js($editable->all()),
            openCreate() {
                this.mode = 'create';
                this.data = { id: null, slug: '', name_uk: '', name_en: '', description_uk: '', parent_id: '', sort_order: 0, is_active: true };
                this.open = true;
            },
            openEdit(id) {
                const c = this.items.find(x => x.id === id);
                if (!c) return;
                this.mode = 'edit';
                this.data = {
                    id: c.id,
                    slug: c.slug,
                    name_uk: c.name_uk || '',
                    name_en: c.name_en || '',
                    description_uk: c.description_uk || '',
                    parent_id: c.parent_id ?? '',
                    sort_order: c.sort_order ?? 0,
                    is_active: !!c.is_active,
                };
                this.open = true;
            }
        }"
        @open-form.window="$event.detail.mode === 'edit' ? openEdit($event.detail.id) : openCreate()"
        @keydown.escape.window="open = false"
        x-cloak
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition.opacity
            @click="open = false"
            class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
        ></div>

        {{-- Panel --}}
        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-white/10 bg-zinc-950 shadow-2xl shadow-black/50"
        >
            <header class="flex h-[68px] shrink-0 items-center justify-between gap-3 border-b border-white/10 px-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300" x-text="mode === 'edit' ? '{{ __('Редагування') }}' : '{{ __('Нова категорія') }}'"></p>
                    <h2 class="mt-0.5 text-base font-bold text-white" x-text="data.name_uk || '{{ __('Категорія') }}'">{{ __('Категорія') }}</h2>
                </div>
                <button @click="open = false" type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/10">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </header>

            <form
                method="POST"
                action="{{ route('admin.categories.store') }}"
                :action="mode === 'edit' ? `/admin/categories/${data.id}` : `{{ route('admin.categories.store') }}`"
                class="flex flex-1 flex-col overflow-hidden"
            >
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>

                <div class="flex-1 overflow-y-auto px-5 py-5 [scrollbar-width:thin]">
                    <div class="grid gap-4">
                        <x-admin.field name="name_uk" :label="__('Назва (UK)')" required placeholder="{{ __('Наприклад: Мініатюри') }}" x-model="data.name_uk" :error="$errors->first('name_uk')" />
                        <x-admin.field name="name_en" :label="__('Назва (EN)')" placeholder="Miniatures" x-model="data.name_en" :error="$errors->first('name_en')" />
                        <x-admin.field name="slug" label="Slug" required placeholder="miniatures" :helper="__('Лише латиниця, цифри та дефіси.')" x-model="data.slug" :error="$errors->first('slug')" />
                        <x-admin.field as="textarea" name="description_uk" :label="__('Опис')" rows="3" placeholder="{{ __('Коротке пояснення для каталогу') }}" x-model="data.description_uk" :error="$errors->first('description_uk')" />

                        <div class="grid gap-4 sm:grid-cols-[1fr_120px]">
                            <x-admin.field as="select" name="parent_id" :label="__('Батьківська категорія')" x-model="data.parent_id" :error="$errors->first('parent_id')">
                                <option value="">{{ __('— Без батька —') }}</option>
                                @foreach($allCategories as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->localized('name') }}</option>
                                @endforeach
                            </x-admin.field>
                            <x-admin.field type="number" name="sort_order" :label="__('Порядок')" :min="0" :max="9999" x-model.number="data.sort_order" :error="$errors->first('sort_order')" />
                        </div>

                        <label class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-zinc-950/50 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-white">{{ __('Активна') }}</span>
                                <span class="block text-xs text-zinc-500">{{ __('Видна у фільтрах і навігації каталогу.') }}</span>
                            </span>
                            <input type="checkbox" name="is_active" value="1" x-model="data.is_active" class="h-5 w-5 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                        </label>
                    </div>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-white/10 bg-zinc-950/80 px-5 py-4">
                    <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm font-semibold text-zinc-200 hover:bg-white/10">{{ __('Скасувати') }}</button>
                    <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">
                        <span x-text="mode === 'edit' ? '{{ __('Оновити') }}' : '{{ __('Створити') }}'">{{ __('Створити') }}</span>
                    </button>
                </footer>
            </form>
        </aside>
    </div>
</x-layouts.admin>
