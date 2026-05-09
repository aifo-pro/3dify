@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $action = $isEdit ? route('admin.pages.update', $page) : route('admin.pages.store');
    $title = $isEdit ? __('Редагувати сторінку') : __('Нова сторінка');
@endphp

<x-layouts.admin
    :title="$title"
    :description="__('Контент сторінки футера. Підтримує HTML — заголовки <h2>/<h3>, списки, посилання, теги <details> для FAQ.')"
    active="content"
    :breadcrumbs="[
        ['label' => __('Налаштування'), 'href' => route('admin.content', ['tab' => 'pages'])],
        ['label' => __('Сторінки футера'), 'href' => route('admin.content', ['tab' => 'pages'])],
        ['label' => $title],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('admin.content', ['tab' => 'pages']) }}"
           class="inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-200 hover:bg-white/10">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            {{ __('До списку') }}
        </a>
        @if($isEdit)
            <a href="{{ route('pages.show', $page->slug) }}" target="_blank"
               class="inline-flex h-9 items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-4 text-xs font-semibold text-emerald-200 hover:bg-emerald-300/15">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                {{ __('Переглянути') }}
            </a>
        @endif
    </x-slot:actions>

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">
            <p class="font-bold">{{ __('Помилки валідації:') }}</p>
            <ul class="mt-1.5 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="grid gap-6 lg:grid-cols-[1fr_320px]">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Main column: title + body --}}
        <div class="grid gap-5">
            <x-admin.settings-card :title="__('Зміст')" :description="__('Заголовок та основний текст сторінки.')">
                <div class="grid gap-4">
                    <x-admin.field
                        name="title"
                        :label="__('Заголовок')"
                        :value="old('title', $page->title)"
                        required
                        autofocus
                    />
                    <x-admin.field
                        name="subtitle"
                        :label="__('Підзаголовок (необовʼязково)')"
                        :value="old('subtitle', $page->subtitle)"
                        :helper="__('Коротке речення під заголовком на героїчному блоці.')"
                    />

                    <div class="grid gap-1.5">
                        <label class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Тіло сторінки (HTML)') }}</span>
                            <span class="text-[10px] uppercase tracking-wider text-zinc-600">{{ __('Підтримуються') }}: h2, h3, p, ul/ol/li, a, strong, code, blockquote, details, summary, hr</span>
                        </label>
                        <textarea
                            name="body"
                            rows="22"
                            class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-3 font-mono text-[13px] leading-6 text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"
                            placeholder="<h2>Розділ</h2>&#10;<p>Текст параграфа...</p>&#10;<ul>&#10;    <li>Пункт 1</li>&#10;</ul>"
                        >{{ old('body', $page->body) }}</textarea>
                        <p class="text-xs text-zinc-500">
                            {{ __('Можна писати чистий HTML. Складні стилі (margin, color) задавати не треба — оформлення накладається автоматично.') }}
                        </p>
                    </div>
                </div>
            </x-admin.settings-card>

            <x-admin.settings-card :title="__('SEO')" :description="__('Окремі мета-теги для цієї сторінки. Якщо порожні — використовується заголовок.')">
                <div class="grid gap-4">
                    <x-admin.field
                        name="meta_title"
                        :label="__('Meta title')"
                        :value="old('meta_title', $page->meta_title)"
                        :helper="__('Оптимально 50–60 символів.')"
                    />
                    <x-admin.field
                        as="textarea"
                        rows="3"
                        name="meta_description"
                        :label="__('Meta description')"
                        :value="old('meta_description', $page->meta_description)"
                        :helper="__('Оптимально 150–160 символів.')"
                    />
                </div>
            </x-admin.settings-card>
        </div>

        {{-- Sidebar: meta + actions --}}
        <aside class="grid gap-5 lg:sticky lg:top-24 lg:self-start">
            <x-admin.settings-card :title="__('Параметри')">
                <div class="grid gap-4">
                    <x-admin.field
                        name="slug"
                        :label="__('Slug')"
                        :value="old('slug', $page->slug)"
                        required
                        :helper="__('Латиниця, цифри, дефіс. URL: /page/slug')"
                        :placeholder="'terms'"
                    />
                    <x-admin.field
                        as="select"
                        name="locale"
                        :label="__('Мова')"
                        required
                    >
                        @foreach(['uk' => 'Українська', 'en' => 'English'] as $code => $name)
                            <option value="{{ $code }}" @selected(old('locale', $page->locale) === $code)>{{ $name }} ({{ strtoupper($code) }})</option>
                        @endforeach
                    </x-admin.field>
                    <x-admin.field
                        name="sort_order"
                        type="number"
                        :label="__('Порядок')"
                        :value="old('sort_order', $page->sort_order ?? 0)"
                        :min="0"
                        :max="9999"
                    />
                    <div>
                        <x-admin.toggle
                            name="is_published"
                            :label="__('Опубліковано')"
                            :description="__('Показувати на сайті відвідувачам.')"
                            :checked="(bool) old('is_published', $page->is_published ?? true)"
                        />
                    </div>
                </div>
            </x-admin.settings-card>

            <div class="grid gap-2">
                <button type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    {{ $isEdit ? __('Зберегти зміни') : __('Створити') }}
                </button>

                @if($isEdit)
                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                          onsubmit="return confirm('{{ __('Видалити сторінку безповоротно?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-rose-300/30 bg-rose-300/[0.08] px-4 text-sm font-semibold text-rose-100 hover:bg-rose-300/15">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                            {{ __('Видалити') }}
                        </button>
                    </form>
                @endif
            </div>

            <x-admin.settings-card :title="__('Стандартні слаги')">
                <p class="text-xs leading-6 text-zinc-400">{{ __('Канонічні slug-и, які підвʼязані до футера. Інші можна створювати, але вони не будуть посилатися автоматично.') }}</p>
                <ul class="mt-3 grid gap-1.5">
                    @foreach($defaultSlugs as $s)
                        <li class="flex items-center justify-between rounded-lg border border-white/10 bg-zinc-950/40 px-2.5 py-1.5">
                            <code class="text-[11px] font-semibold text-emerald-300">{{ $s['slug'] }}</code>
                            <span class="text-[10px] text-zinc-500">{{ $s['label_uk'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-admin.settings-card>
        </aside>
    </form>
</x-layouts.admin>
