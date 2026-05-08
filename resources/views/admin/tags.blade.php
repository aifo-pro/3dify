@php
    $editable = $tags->getCollection()->map(fn ($t) => [
        'id' => $t->id,
        'slug' => $t->slug,
        'name_uk' => is_array($t->name) ? ($t->name['uk'] ?? '') : (string) $t->name,
        'name_en' => is_array($t->name) ? ($t->name['en'] ?? '') : '',
        'products_count' => $t->products_count,
    ])->all();
@endphp

<x-layouts.admin
    :title="__('Теги')"
    :description="__('Швидка фільтрація та зв’язки між моделями.')"
    active="tags"
    :breadcrumbs="[['label' => __('Каталог'), 'href' => route('admin.products')], ['label' => __('Теги')]]"
>
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.tags') }}" class="relative">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Пошук за slug…') }}" class="h-9 w-56 rounded-full border border-white/10 bg-white/[0.04] pl-9 pr-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </span>
        </form>
        <button x-data @click="$dispatch('open-form', { mode: 'create' })" type="button" class="inline-flex h-9 items-center gap-2 rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Новий тег') }}
        </button>
    </x-slot:actions>

    {{-- Mini stats --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Усього тегів') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($totalCount) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Використовуються') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($usedCount) }}</p>
            <p class="text-[11px] text-zinc-500">{{ $totalCount > 0 ? round($usedCount / $totalCount * 100) : 0 }}% {{ __('від загальної кількості') }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Невикористані') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format(max($totalCount - $usedCount, 0)) }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    {{-- Tag cloud / list --}}
    <div class="mt-5">
        <x-admin.section :padded="false">
            @if($tags->isEmpty())
                <div class="grid place-items-center px-5 py-16 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-full bg-white/[0.06] text-zinc-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-white">{{ $q ? __('Нічого не знайдено') : __('Тегів ще немає') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $q ? __('Спробуйте інший пошуковий запит.') : __('Додайте теги, щоб полегшити фільтрацію.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-950/40">
                            <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                                <th class="px-5 py-3 w-10"></th>
                                <th class="px-5 py-3">{{ __('Назва') }}</th>
                                <th class="px-5 py-3">Slug</th>
                                <th class="px-5 py-3 text-center">{{ __('Моделей') }}</th>
                                <th class="px-5 py-3 text-center">{{ __('Статус') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Дії') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($tags as $tag)
                                <tr class="transition hover:bg-white/[0.02]">
                                    <td class="px-5 py-3">
                                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-300/15 text-sky-100">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-white">{{ $tag->localized('name') }}</td>
                                    <td class="px-5 py-3"><span class="font-mono text-xs text-zinc-300">#{{ $tag->slug }}</span></td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center justify-center rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $tag->products_count }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @if($tag->products_count > 0)
                                            <x-admin.status-pill status="active">{{ __('використовується') }}</x-admin.status-pill>
                                        @else
                                            <x-admin.status-pill status="archived">{{ __('не використовується') }}</x-admin.status-pill>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" x-data @click="$dispatch('open-form', { mode: 'edit', id: {{ $tag->id }} })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100" title="{{ __('Редагувати') }}">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('{{ __('Видалити тег?') }}')">
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

        @if($tags->hasPages())
            <div class="mt-5">{{ $tags->links() }}</div>
        @endif
    </div>

    {{-- Slide-over form --}}
    <div
        x-data="{
            open: false,
            mode: 'create',
            data: { id: null, slug: '', name_uk: '', name_en: '' },
            items: @js($editable),
            openCreate() {
                this.mode = 'create';
                this.data = { id: null, slug: '', name_uk: '', name_en: '' };
                this.open = true;
            },
            openEdit(id) {
                const c = this.items.find(x => x.id === id);
                if (!c) return;
                this.mode = 'edit';
                this.data = { id: c.id, slug: c.slug, name_uk: c.name_uk || '', name_en: c.name_en || '' };
                this.open = true;
            }
        }"
        @open-form.window="$event.detail.mode === 'edit' ? openEdit($event.detail.id) : openCreate()"
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
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-white/10 bg-zinc-950 shadow-2xl shadow-black/50"
        >
            <header class="flex h-[68px] shrink-0 items-center justify-between gap-3 border-b border-white/10 px-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300" x-text="mode === 'edit' ? '{{ __('Редагування') }}' : '{{ __('Новий тег') }}'"></p>
                    <h2 class="mt-0.5 text-base font-bold text-white" x-text="data.name_uk || '{{ __('Тег') }}'">{{ __('Тег') }}</h2>
                </div>
                <button @click="open = false" type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/10">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </header>

            <form method="POST" action="{{ route('admin.tags.store') }}" :action="mode === 'edit' ? `/admin/tags/${data.id}` : `{{ route('admin.tags.store') }}`" class="flex flex-1 flex-col overflow-hidden">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>

                <div class="flex-1 overflow-y-auto px-5 py-5 [scrollbar-width:thin]">
                    <div class="grid gap-4">
                        <x-admin.field name="name_uk" :label="__('Назва (UK)')" required placeholder="{{ __('Наприклад: FDM') }}" x-model="data.name_uk" :error="$errors->first('name_uk')" />
                        <x-admin.field name="name_en" :label="__('Назва (EN)')" placeholder="FDM" x-model="data.name_en" :error="$errors->first('name_en')" />
                        <x-admin.field name="slug" label="Slug" required placeholder="fdm" :helper="__('Лише латиниця, цифри та дефіси.')" x-model="data.slug" :error="$errors->first('slug')" />
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
