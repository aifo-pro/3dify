@php
    $editable = $categories->map(fn (\App\Models\BlogCategory $c) => [
        'id' => $c->id,
        'update_url' => route('admin.blog.categories.update', $c),
        'slug' => $c->slug,
        'name_uk' => $c->name_uk,
        'name_en' => $c->name_en ?? '',
        'sort_order' => (int) $c->sort_order,
        'is_active' => (bool) $c->is_active,
        'description_uk' => $c->description_uk ?? '',
        'description_en' => $c->description_en ?? '',
        'seo_title_uk' => $c->seo_title_uk ?? '',
        'seo_title_en' => $c->seo_title_en ?? '',
        'seo_description_uk' => $c->seo_description_uk ?? '',
        'seo_description_en' => $c->seo_description_en ?? '',
    ])->values()->all();
@endphp

<x-layouts.admin
    :title="__('Категорії блогу')"
    :description="__('SEO-розділи для статей.')"
    active="blog"
    :breadcrumbs="[['label' => __('Блог'), 'href' => route('admin.blog.index')], ['label' => __('Категорії')]]"
>
    <x-slot:actions>
        <button x-data @click="$dispatch('open-blog-category', { mode: 'create' })" type="button" class="inline-flex h-9 items-center gap-2 rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Нова категорія') }}
        </button>
    </x-slot:actions>

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    <x-admin.section :padded="false" :title="__('Категорії')">
        @if($categories->isEmpty())
            <div class="grid place-items-center px-5 py-16 text-center">
                <div class="grid h-12 w-12 place-items-center rounded-full bg-white/[0.06] text-zinc-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <p class="mt-3 text-sm font-semibold text-white">{{ __('Категорій ще немає') }}</p>
                <p class="mt-1 max-w-sm text-xs text-zinc-500">{{ __('Додайте першу категорію для блогу.') }}</p>
                <button x-data @click="$dispatch('open-blog-category', { mode: 'create' })" type="button" class="mt-6 inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950">{{ __('Нова категорія') }}</button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-zinc-950/40">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                            <th class="px-4 py-3 w-12 text-center">#</th>
                            <th class="px-4 py-3">{{ __('Назва (UK)') }}</th>
                            <th class="px-4 py-3 hidden sm:table-cell">{{ __('Назва (EN)') }}</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center w-20">{{ __('Сортування') }}</th>
                            <th class="px-4 py-3 text-center w-24">{{ __('Статус') }}</th>
                            <th class="px-4 py-3 text-right w-28">{{ __('Дії') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($categories as $category)
                            <tr class="transition hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-center text-xs text-zinc-500">{{ $category->id }}</td>
                                <td class="px-4 py-2.5 font-semibold text-white">{{ $category->name_uk }}</td>
                                <td class="px-4 py-2.5 text-zinc-400 hidden sm:table-cell">{{ $category->name_en ?: '—' }}</td>
                                <td class="px-4 py-2.5"><span class="font-mono text-xs text-zinc-400">{{ $category->slug }}</span></td>
                                <td class="px-4 py-2.5 text-center text-xs text-zinc-400">{{ $category->sort_order }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($category->is_active)
                                        <x-admin.status-pill status="active">{{ __('Активна') }}</x-admin.status-pill>
                                    @else
                                        <x-admin.status-pill status="archived">{{ __('Вимкнено') }}</x-admin.status-pill>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" x-data @click="$dispatch('open-blog-category', { mode: 'edit', id: {{ $category->id }} })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100" title="{{ __('Редагувати') }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('admin.blog.categories.destroy', $category) }}" onsubmit="return confirm('{{ __('Видалити категорію?') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100" title="{{ __('Видалити') }}">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.section>

    {{-- Slide-over: create / edit --}}
    <div
        x-data="{
            open: false,
            mode: 'create',
            formAction: '{{ route('admin.blog.categories.store') }}',
            items: @js($editable),
            data: {
                id: null,
                slug: '',
                name_uk: '',
                name_en: '',
                sort_order: 0,
                is_active: true,
                description_uk: '',
                description_en: '',
                seo_title_uk: '',
                seo_title_en: '',
                seo_description_uk: '',
                seo_description_en: '',
            },
            openCreate() {
                this.mode = 'create';
                this.formAction = '{{ route('admin.blog.categories.store') }}';
                this.data = {
                    id: null,
                    slug: '',
                    name_uk: '',
                    name_en: '',
                    sort_order: 0,
                    is_active: true,
                    description_uk: '',
                    description_en: '',
                    seo_title_uk: '',
                    seo_title_en: '',
                    seo_description_uk: '',
                    seo_description_en: '',
                };
                this.open = true;
            },
            openEdit(id) {
                const row = this.items.find(x => x.id === id);
                if (!row) return;
                this.mode = 'edit';
                this.formAction = row.update_url;
                this.data = {
                    id: row.id,
                    slug: row.slug || '',
                    name_uk: row.name_uk || '',
                    name_en: row.name_en || '',
                    sort_order: row.sort_order ?? 0,
                    is_active: !!row.is_active,
                    description_uk: row.description_uk || '',
                    description_en: row.description_en || '',
                    seo_title_uk: row.seo_title_uk || '',
                    seo_title_en: row.seo_title_en || '',
                    seo_description_uk: row.seo_description_uk || '',
                    seo_description_en: row.seo_description_en || '',
                };
                this.open = true;
            },
        }"
        @open-blog-category.window="$event.detail.mode === 'edit' ? openEdit($event.detail.id) : openCreate()"
        @keydown.escape.window="open = false"
        x-cloak
    >
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"></div>

        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-lg flex-col border-l border-white/10 bg-zinc-950 shadow-2xl shadow-black/50"
        >
            <header class="flex h-[68px] shrink-0 items-center justify-between gap-3 border-b border-white/10 px-5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300" x-text="mode === 'edit' ? '{{ __('Редагування') }}' : '{{ __('Нова категорія') }}'"></p>
                    <h2 class="mt-0.5 truncate text-base font-bold text-white" x-text="data.name_uk || '{{ __('Категорія') }}'">{{ __('Категорія') }}</h2>
                </div>
                <button @click="open = false" type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/10">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </header>

            <form method="POST" :action="formAction" class="flex flex-1 flex-col overflow-hidden">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>

                <div class="flex-1 overflow-y-auto px-5 py-5 [scrollbar-width:thin]">
                    <div class="grid gap-4">
                        <x-admin.field name="name_uk" :label="__('Name UK')" required x-model="data.name_uk" :error="$errors->first('name_uk')" />
                        <x-admin.field name="name_en" :label="__('Name EN')" x-model="data.name_en" :error="$errors->first('name_en')" />
                        <x-admin.field name="slug" :label="__('Slug')" x-model="data.slug" :error="$errors->first('slug')" />
                        <x-admin.field name="sort_order" type="number" :label="__('Sort')" x-model="data.sort_order" :error="$errors->first('sort_order')" />

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-300">
                            <input type="checkbox" name="is_active" value="1" x-model="data.is_active" class="rounded border-white/20 bg-zinc-950 text-emerald-400">
                            {{ __('Активна') }}
                        </label>

                        <div class="border-t border-white/10 pt-2">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Опис') }}</p>
                        </div>
                        <x-admin.field name="description_uk" as="textarea" :rows="2" :label="__('Description UK')" x-model="data.description_uk" :error="$errors->first('description_uk')" />
                        <x-admin.field name="description_en" as="textarea" :rows="2" :label="__('Description EN')" x-model="data.description_en" :error="$errors->first('description_en')" />

                        <div class="border-t border-white/10 pt-2">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">SEO</p>
                        </div>
                        <x-admin.field name="seo_title_uk" :label="__('SEO title UK')" x-model="data.seo_title_uk" :error="$errors->first('seo_title_uk')" />
                        <x-admin.field name="seo_title_en" :label="__('SEO title EN')" x-model="data.seo_title_en" :error="$errors->first('seo_title_en')" />
                        <x-admin.field name="seo_description_uk" as="textarea" :rows="2" :label="__('SEO description UK')" x-model="data.seo_description_uk" :error="$errors->first('seo_description_uk')" />
                        <x-admin.field name="seo_description_en" as="textarea" :rows="2" :label="__('SEO description EN')" x-model="data.seo_description_en" :error="$errors->first('seo_description_en')" />
                    </div>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-white/10 bg-zinc-950/80 px-5 py-4">
                    <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm font-semibold text-zinc-200 hover:bg-white/10">{{ __('Скасувати') }}</button>
                    <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">
                        <span x-text="mode === 'edit' ? '{{ __('Зберегти') }}' : '{{ __('Створити') }}'">{{ __('Створити') }}</span>
                    </button>
                </footer>
            </form>
        </aside>
    </div>
</x-layouts.admin>
