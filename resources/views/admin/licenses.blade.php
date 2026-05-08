@php
    $editable = $licenses->getCollection()->map(fn ($l) => [
        'id' => $l->id,
        'slug' => $l->slug,
        'name_uk' => is_array($l->name) ? ($l->name['uk'] ?? '') : (string) $l->name,
        'name_en' => is_array($l->name) ? ($l->name['en'] ?? '') : '',
        'description_uk' => is_array($l->description) ? ($l->description['uk'] ?? '') : (string) $l->description,
        'description_en' => is_array($l->description) ? ($l->description['en'] ?? '') : '',
        'allows_commercial_use' => (bool) $l->allows_commercial_use,
        'requires_attribution' => (bool) $l->requires_attribution,
    ])->all();
@endphp

<x-layouts.admin
    :title="__('Ліцензії')"
    :description="__('Правила використання моделей покупцями.')"
    active="licenses"
    :breadcrumbs="[['label' => __('Каталог'), 'href' => route('admin.products')], ['label' => __('Ліцензії')]]"
>
    <x-slot:actions>
        <form method="GET" action="{{ route('admin.licenses') }}" class="relative">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Пошук за slug…') }}" class="h-9 w-56 rounded-full border border-white/10 bg-white/[0.04] pl-9 pr-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </span>
        </form>
        <button x-data @click="$dispatch('open-form', { mode: 'create' })" type="button" class="inline-flex h-9 items-center gap-2 rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Нова ліцензія') }}
        </button>
    </x-slot:actions>

    {{-- Mini stats --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Усього ліцензій') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($totalCount) }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-300">{{ __('Дозволяють комерцію') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($commercialCount) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Тільки особисте') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format(max($totalCount - $commercialCount, 0)) }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    {{-- Cards grid --}}
    <div class="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse($licenses as $license)
            <article class="group flex flex-col rounded-3xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-black/20 transition hover:border-white/20 hover:bg-white/[0.06]">
                <header class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-300/15 text-emerald-100">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </span>
                        <h3 class="mt-3 truncate text-base font-bold text-white">{{ $license->localized('name') }}</h3>
                        <p class="text-xs font-mono text-zinc-500">/{{ $license->slug }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" x-data @click="$dispatch('open-form', { mode: 'edit', id: {{ $license->id }} })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100" title="{{ __('Редагувати') }}">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <form method="POST" action="{{ route('admin.licenses.destroy', $license) }}" onsubmit="return confirm('{{ __('Видалити ліцензію?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100" title="{{ __('Видалити') }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </header>

                <p class="mt-4 line-clamp-3 min-h-[3.75rem] text-sm leading-6 text-zinc-400">
                    {{ $license->localized('description') ?: __('Опис не вказано.') }}
                </p>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-white/10 bg-zinc-950/50 px-3 py-2 text-xs">
                        <p class="font-semibold uppercase tracking-wider text-zinc-500">{{ __('Комерція') }}</p>
                        <p class="mt-1 flex items-center gap-1.5 font-bold {{ $license->allows_commercial_use ? 'text-emerald-200' : 'text-zinc-400' }}">
                            @if($license->allows_commercial_use)
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ __('дозволено') }}
                            @else
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                {{ __('заборонено') }}
                            @endif
                        </p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-zinc-950/50 px-3 py-2 text-xs">
                        <p class="font-semibold uppercase tracking-wider text-zinc-500">{{ __('Attribution') }}</p>
                        <p class="mt-1 flex items-center gap-1.5 font-bold {{ $license->requires_attribution ? 'text-amber-200' : 'text-zinc-400' }}">
                            @if($license->requires_attribution)
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ __('обовʼязково') }}
                            @else
                                {{ __('опційно') }}
                            @endif
                        </p>
                    </div>
                </div>

                <footer class="mt-4 flex items-center justify-between border-t border-white/5 pt-4">
                    <span class="text-xs text-zinc-500">{{ __('Моделей:') }}</span>
                    <span class="inline-flex items-center justify-center rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $license->products_count }}</span>
                </footer>
            </article>
        @empty
            <div class="col-span-full">
                <div class="grid place-items-center rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-16 text-center">
                    <div class="grid h-12 w-12 place-items-center rounded-full bg-white/[0.06] text-zinc-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-white">{{ $q ? __('Нічого не знайдено') : __('Ліцензій ще немає') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ $q ? __('Спробуйте інший пошуковий запит.') : __('Додайте першу ліцензію, наприклад «Personal use only».') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($licenses->hasPages())
        <div class="mt-5">{{ $licenses->links() }}</div>
    @endif

    {{-- Slide-over form --}}
    <div
        x-data="{
            open: false,
            mode: 'create',
            data: { id: null, slug: '', name_uk: '', name_en: '', description_uk: '', description_en: '', allows_commercial_use: false, requires_attribution: true },
            items: @js($editable),
            openCreate() {
                this.mode = 'create';
                this.data = { id: null, slug: '', name_uk: '', name_en: '', description_uk: '', description_en: '', allows_commercial_use: false, requires_attribution: true };
                this.open = true;
            },
            openEdit(id) {
                const c = this.items.find(x => x.id === id);
                if (!c) return;
                this.mode = 'edit';
                this.data = {
                    id: c.id, slug: c.slug,
                    name_uk: c.name_uk || '', name_en: c.name_en || '',
                    description_uk: c.description_uk || '', description_en: c.description_en || '',
                    allows_commercial_use: !!c.allows_commercial_use,
                    requires_attribution: !!c.requires_attribution,
                };
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
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300" x-text="mode === 'edit' ? '{{ __('Редагування') }}' : '{{ __('Нова ліцензія') }}'"></p>
                    <h2 class="mt-0.5 text-base font-bold text-white" x-text="data.name_uk || '{{ __('Ліцензія') }}'">{{ __('Ліцензія') }}</h2>
                </div>
                <button @click="open = false" type="button" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/10">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </header>

            <form method="POST" action="{{ route('admin.licenses.store') }}" :action="mode === 'edit' ? `/admin/licenses/${data.id}` : `{{ route('admin.licenses.store') }}`" class="flex flex-1 flex-col overflow-hidden">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>

                <div class="flex-1 overflow-y-auto px-5 py-5 [scrollbar-width:thin]">
                    <div class="grid gap-4">
                        <x-admin.field name="name_uk" :label="__('Назва (UK)')" required placeholder="{{ __('Особисте використання') }}" x-model="data.name_uk" :error="$errors->first('name_uk')" />
                        <x-admin.field name="name_en" :label="__('Назва (EN)')" placeholder="Personal use only" x-model="data.name_en" :error="$errors->first('name_en')" />
                        <x-admin.field name="slug" label="Slug" required placeholder="personal-use" :helper="__('Лише латиниця, цифри та дефіси.')" x-model="data.slug" :error="$errors->first('slug')" />
                        <x-admin.field as="textarea" name="description_uk" :label="__('Опис (UK)')" rows="3" placeholder="{{ __('Що дозволено, що ні') }}" x-model="data.description_uk" :error="$errors->first('description_uk')" />
                        <x-admin.field as="textarea" name="description_en" :label="__('Опис (EN)')" rows="3" placeholder="What is allowed and what isn't" x-model="data.description_en" :error="$errors->first('description_en')" />

                        <label class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-zinc-950/50 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-white">{{ __('Дозволяє комерційне використання') }}</span>
                                <span class="block text-xs text-zinc-500">{{ __('Покупець може заробляти на надрукованих моделях.') }}</span>
                            </span>
                            <input type="checkbox" name="allows_commercial_use" value="1" x-model="data.allows_commercial_use" class="h-5 w-5 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                        </label>

                        <label class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-zinc-950/50 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-white">{{ __('Вимагає attribution') }}</span>
                                <span class="block text-xs text-zinc-500">{{ __('Покупець має вказати автора.') }}</span>
                            </span>
                            <input type="checkbox" name="requires_attribution" value="1" x-model="data.requires_attribution" class="h-5 w-5 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
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
