@php
    $editable = $tags->map(fn (\App\Models\BlogTag $t) => [
        'id' => $t->id,
        'update_url' => route('admin.blog.tags.update', $t),
        'slug' => $t->slug,
        'name_uk' => $t->name_uk,
        'name_en' => $t->name_en ?? '',
        'is_active' => (bool) $t->is_active,
    ])->values()->all();
@endphp

<x-layouts.admin
    :title="__('Теги блогу')"
    :description="__('Ключові теми для SEO і related posts.')"
    active="blog"
    :breadcrumbs="[['label' => __('Блог'), 'href' => route('admin.blog.index')], ['label' => __('Теги')]]"
>
    <x-slot:actions>
        <button x-data @click="$dispatch('open-blog-tag', { mode: 'create' })" type="button" class="inline-flex h-9 items-center gap-2 rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Новий тег') }}
        </button>
    </x-slot:actions>

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    <x-admin.section :padded="false" :title="__('Теги')">
        @if($tags->isEmpty())
            <div class="grid place-items-center px-5 py-16 text-center">
                <div class="grid h-12 w-12 place-items-center rounded-full bg-white/[0.06] text-zinc-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <p class="mt-3 text-sm font-semibold text-white">{{ __('Тегів ще немає') }}</p>
                <p class="mt-1 max-w-sm text-xs text-zinc-500">{{ __('Додайте перший тег для блогу.') }}</p>
                <button x-data @click="$dispatch('open-blog-tag', { mode: 'create' })" type="button" class="mt-6 inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950">{{ __('Новий тег') }}</button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="bg-zinc-950/40">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                            <th class="px-4 py-3 w-12 text-center">#</th>
                            <th class="px-4 py-3">{{ __('Назва (UK)') }}</th>
                            <th class="px-4 py-3 hidden sm:table-cell">{{ __('Назва (EN)') }}</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center w-24">{{ __('Статус') }}</th>
                            <th class="px-4 py-3 text-right w-28">{{ __('Дії') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($tags as $tag)
                            <tr class="transition hover:bg-white/[0.02]">
                                <td class="px-4 py-2.5 text-center text-xs text-zinc-500">{{ $tag->id }}</td>
                                <td class="px-4 py-2.5 font-semibold text-white">{{ $tag->name_uk }}</td>
                                <td class="px-4 py-2.5 text-zinc-400 hidden sm:table-cell">{{ $tag->name_en ?: '—' }}</td>
                                <td class="px-4 py-2.5"><span class="font-mono text-xs text-zinc-400">{{ $tag->slug }}</span></td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($tag->is_active)
                                        <x-admin.status-pill status="active">{{ __('Активний') }}</x-admin.status-pill>
                                    @else
                                        <x-admin.status-pill status="archived">{{ __('Вимкнено') }}</x-admin.status-pill>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" x-data @click="$dispatch('open-blog-tag', { mode: 'edit', id: {{ $tag->id }} })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100" title="{{ __('Редагувати') }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('admin.blog.tags.destroy', $tag) }}" onsubmit="return confirm('{{ __('Видалити тег?') }}')">
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

    <div
        x-data="{
            open: false,
            mode: 'create',
            formAction: '{{ route('admin.blog.tags.store') }}',
            items: @js($editable),
            data: { id: null, slug: '', name_uk: '', name_en: '', is_active: true },
            openCreate() {
                this.mode = 'create';
                this.formAction = '{{ route('admin.blog.tags.store') }}';
                this.data = { id: null, slug: '', name_uk: '', name_en: '', is_active: true };
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
                    is_active: !!row.is_active,
                };
                this.open = true;
            },
        }"
        @open-blog-tag.window="$event.detail.mode === 'edit' ? openEdit($event.detail.id) : openCreate()"
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
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col border-l border-white/10 bg-zinc-950 shadow-2xl shadow-black/50"
        >
            <header class="flex h-[68px] shrink-0 items-center justify-between gap-3 border-b border-white/10 px-5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300" x-text="mode === 'edit' ? '{{ __('Редагування') }}' : '{{ __('Новий тег') }}'"></p>
                    <h2 class="mt-0.5 truncate text-base font-bold text-white" x-text="data.name_uk || '{{ __('Тег') }}'">{{ __('Тег') }}</h2>
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

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-300">
                            <input type="checkbox" name="is_active" value="1" x-model="data.is_active" class="rounded border-white/20 bg-zinc-950 text-emerald-400">
                            {{ __('Активний') }}
                        </label>
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
