@php
    $isEdit = $product->exists;
    $titleUk = old('title_uk', $product->localized('title', 'uk'));
    $descriptionUk = old('description_uk', $product->localized('description', 'uk'));
    $hasCover = (bool) $product->cover_path;
    $hasFile = $product->files->where('type', 'source')->isNotEmpty();
    $hasLicense = (bool) old('license_id', $product->license_id);
    $hasTitle = filled($titleUk);
    $hasDescription = filled($descriptionUk);
    $imagePreview = $product->files
        ->first(fn ($file) => $file->is_preview && in_array($file->extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true));
    $coverUrl = null;
    if ($product->cover_path && Storage::disk('public')->exists($product->cover_path)) {
        $coverUrl = Storage::disk('public')->url($product->cover_path);
    } elseif (filled($product->gallery)) {
        $firstGalleryImage = collect($product->gallery)->first(fn ($path) => Storage::disk('public')->exists($path));
        $coverUrl = $firstGalleryImage ? Storage::disk('public')->url($firstGalleryImage) : null;
    } elseif ($imagePreview) {
        $coverUrl = Storage::disk($imagePreview->disk)->url($imagePreview->path);
    }

    $checklist = [
        ['label' => __('Назва'), 'done' => $hasTitle],
        ['label' => __('Опис'), 'done' => $hasDescription],
        ['label' => __('Обкладинка'), 'done' => $hasCover],
        ['label' => __('Файли'), 'done' => $hasFile],
        ['label' => __('Ліцензія'), 'done' => $hasLicense],
        ['label' => __('Авторські права'), 'done' => false],
    ];

    $doneCount = collect($checklist)->where('done', true)->count();
    $progressPct = (int) round($doneCount / count($checklist) * 100);

    $currentStep = $hasFile ? 4 : ($hasLicense ? 3 : ($hasTitle && $hasDescription ? 2 : 1));
    $steps = [
        ['n' => 1, 'label' => __('Деталі')],
        ['n' => 2, 'label' => __('Категорія')],
        ['n' => 3, 'label' => __('Файли')],
        ['n' => 4, 'label' => __('Публікація')],
    ];
@endphp

<x-layouts.marketplace>
    <section class="mx-auto w-full max-w-[1280px] px-4 py-8 sm:px-6 lg:px-8">
        {{-- Compact hero --}}
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <x-ui.badge>{{ $isEdit ? __('Редагування') : __('Publish wizard') }}</x-ui.badge>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $isEdit ? __('Редагувати 3D-модель') : __('Опублікувати 3D-модель') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('Заповніть деталі, додайте preview і source-файли, визначте ціну та відправте модель на модерацію.') }}</p>
            </div>

            {{-- Progress steps --}}
            <ol class="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] p-1.5">
                @foreach($steps as $step)
                    @php
                        $isActive = $step['n'] === $currentStep;
                        $isDone = $step['n'] < $currentStep;
                    @endphp
                    <li class="flex items-center gap-2">
                        <div class="inline-flex h-8 items-center gap-2 rounded-full px-3 text-xs font-semibold transition
                            {{ $isActive ? 'bg-emerald-300/15 text-emerald-100' : '' }}
                            {{ $isDone ? 'text-emerald-200' : '' }}
                            {{ ! $isActive && ! $isDone ? 'text-zinc-500' : '' }}">
                            <span class="grid h-5 w-5 place-items-center rounded-full text-[10px] font-black
                                {{ $isActive ? 'bg-emerald-400 text-zinc-950' : '' }}
                                {{ $isDone ? 'bg-emerald-400/30 text-emerald-100' : '' }}
                                {{ ! $isActive && ! $isDone ? 'bg-white/10 text-zinc-500' : '' }}">
                                {{ $step['n'] }}
                            </span>
                            <span class="hidden sm:inline">{{ $step['label'] }}</span>
                        </div>
                        @if(! $loop->last)
                            <span class="hidden h-px w-4 bg-white/10 sm:block"></span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        @if (session('status') || $errors->any())
            <div class="mt-6 grid gap-3">
                @if (session('status'))
                    <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-3 text-sm font-semibold text-emerald-100 shadow-lg shadow-emerald-500/10">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100 shadow-lg shadow-rose-500/10">
                        <p class="font-bold">{{ __('Не вдалося зберегти модель') }}</p>
                        <ul class="mt-2 grid gap-1 text-xs leading-5 text-rose-100/90">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Wizard layout --}}
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ $isEdit ? route('author.products.update', $product) : route('author.products.store') }}"
            class="mx-auto mt-8 grid w-full gap-6 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start"
            x-data="{ invalidNotice: false, saving: false }"
            @invalid.capture="invalidNotice = true; saving = false"
            @submit="invalidNotice = false; saving = true"
        >
            @csrf
            @if($isEdit) @method('PATCH') @endif

            {{-- Form column --}}
            <div class="grid gap-5">
                <div
                    x-show="invalidNotice"
                    x-cloak
                    class="rounded-2xl border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100 shadow-lg shadow-amber-500/10"
                >
                    <p class="font-bold">{{ __('Заповніть обовʼязкові поля') }}</p>
                    <p class="mt-1 text-xs leading-5 text-amber-100/80">{{ __('Браузер підсвітить поле, яке потрібно виправити перед збереженням.') }}</p>
                </div>

                {{-- Section 1: Основна інформація --}}
                <section class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7">
                    <header class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-sm font-black text-emerald-100">1</span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('Основна інформація') }}</p>
                            <h2 class="mt-0.5 text-xl font-bold text-white">{{ __('Назва та опис') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('Поясніть, що це за модель, для кого вона і чому її варто завантажити.') }}</p>
                        </div>
                    </header>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <x-ui.input name="title_uk" :value="$titleUk" :label="__('Назва (UK)')" required :error="$errors->first('title_uk')" placeholder="{{ __('Наприклад: Органайзер для столу') }}" />
                        <x-ui.input name="title_en" value="{{ old('title_en', $product->localized('title', 'en')) }}" :label="__('Назва (EN)')" :error="$errors->first('title_en')" placeholder="Desk organizer" />
                    </div>
                    <div class="mt-4">
                        <x-ui.textarea name="short_description_uk" rows="2" :label="__('Короткий опис')" :helper="__('1–2 речення, які зʼявляться на картці в каталозі.')" :error="$errors->first('short_description_uk')">{{ old('short_description_uk', $product->localized('short_description', 'uk')) }}</x-ui.textarea>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <x-ui.textarea name="description_uk" rows="7" :label="__('Опис (UK)')" required :helper="__('Сценарії використання, особливості друку, комплектація.')" :error="$errors->first('description_uk')">{{ $descriptionUk }}</x-ui.textarea>
                        <x-ui.textarea name="description_en" rows="7" :label="__('Опис (EN)')" :helper="__('English fallback для міжнародних покупців.')" :error="$errors->first('description_en')">{{ old('description_en', $product->localized('description', 'en')) }}</x-ui.textarea>
                    </div>
                </section>

                {{-- Section 2: Категорія та ліцензія --}}
                <section class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7">
                    <header class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-sm font-black text-emerald-100">2</span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('Категорія та ліцензія') }}</p>
                            <h2 class="mt-0.5 text-xl font-bold text-white">{{ __('Позиціонування в каталозі') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('Допомагає покупцю знайти модель і зрозуміти права використання.') }}</p>
                        </div>
                    </header>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <x-ui.select name="category_id" :label="__('Категорія')" :helper="__('Допомагає потрапити в правильну добірку.')" :error="$errors->first('category_id')">
                            <option value="">{{ __('Оберіть категорію') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->localized('name') }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.select name="license_id" :label="__('Ліцензія')" :helper="__('Пояснює покупцю права використання.')" :error="$errors->first('license_id')">
                            <option value="">{{ __('Оберіть ліцензію') }}</option>
                            @foreach($licenses as $license)
                                <option value="{{ $license->id }}" @selected(old('license_id', $product->license_id) == $license->id)>{{ $license->localized('name') }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div class="mt-4">
                        <x-ui.select name="tags[]" multiple :label="__('Теги')" :helper="__('Утримуйте Ctrl / Cmd, щоб обрати кілька тегів.')" :error="$errors->first('tags')">
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" @selected($product->tags->contains($tag))>{{ $tag->localized('name') }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </section>

                {{-- Section 3: Ціна та ліцензії --}}
                <section
                    x-data="{ commercial: {{ old('commercial_license_enabled', $product->commercial_license_enabled ?? false) ? 'true' : 'false' }} }"
                    class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7"
                >
                    <header class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-sm font-black text-emerald-100">3</span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('Ціна та ліцензії') }}</p>
                            <h2 class="mt-0.5 text-xl font-bold text-white">{{ __('Цінова політика') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('Поставте 0, щоб опублікувати безкоштовну модель. Можна додати окрему комерційну ліцензію.') }}</p>
                        </div>
                    </header>

                    {{-- Personal price (legacy "price" stays for back-compat) --}}
                    <div class="mt-6 grid gap-4 md:grid-cols-[1fr_180px]">
                        <x-ui.input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price ?? 0) }}" :label="__('Ціна (Personal license)')" :helper="__('0 означає безкоштовну модель.')" :error="$errors->first('price')" />
                        <x-ui.select name="currency" :label="__('Валюта')" :error="$errors->first('currency')">
                            @foreach(['EUR', 'USD', 'UAH'] as $currency)
                                <option value="{{ $currency }}" @selected(old('currency', $product->currency ?? 'EUR') === $currency)>{{ $currency }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    {{-- Commercial license toggle --}}
                    <label class="mt-5 flex items-start justify-between gap-4 rounded-2xl border border-white/10 bg-zinc-950/50 p-4">
                        <span class="min-w-0">
                            <span class="block font-semibold text-white">{{ __('Продавати також Commercial license') }}</span>
                            <span class="block text-sm text-zinc-500">{{ __('Покупець зможе обрати між Personal та Commercial ліцензією при оформленні замовлення.') }}</span>
                        </span>
                        <input type="checkbox" name="commercial_license_enabled" value="1" x-model="commercial"
                            class="h-5 w-5 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300"
                            @if(old('commercial_license_enabled', $product->commercial_license_enabled ?? false)) checked @endif>
                    </label>

                    {{-- Commercial details --}}
                    <div x-show="commercial" x-cloak class="mt-4 grid gap-4 rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.04] p-4">
                        <div class="grid gap-4 md:grid-cols-[1fr_220px]">
                            <x-ui.input type="number" step="0.01" min="0" name="commercial_price"
                                value="{{ old('commercial_price', $product->commercial_price ?? '') }}"
                                :label="__('Ціна Commercial')"
                                :helper="__('Як правило, у 2-5x вище за Personal.')"
                                :error="$errors->first('commercial_price')" />
                            <x-ui.select name="commercial_license_id" :label="__('Тип Commercial ліцензії')" :error="$errors->first('commercial_license_id')">
                                <option value="">{{ __('Як основна ліцензія') }}</option>
                                @foreach($licenses as $license)
                                    <option value="{{ $license->id }}" @selected(old('commercial_license_id', $product->commercial_license_id ?? null) == $license->id)>{{ $license->localized('name') }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.12em] text-zinc-400">{{ __('Опис Commercial (UK)') }}</label>
                                <textarea name="commercial_license_description_uk" rows="3"
                                    class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"
                                    placeholder="{{ __('Що саме отримує покупець за Commercial-ліцензію.') }}">{{ old('commercial_license_description_uk', $product->commercial_license_description['uk'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.12em] text-zinc-400">{{ __('Опис Commercial (EN)') }}</label>
                                <textarea name="commercial_license_description_en" rows="3"
                                    class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"
                                    placeholder="What exactly the buyer receives for Commercial.">{{ old('commercial_license_description_en', $product->commercial_license_description['en'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 rounded-2xl border border-sky-300/20 bg-sky-300/[0.07] p-4 text-sm leading-6 text-sky-100">
                        {{ __('Безкоштовні моделі підвищують довіру до автора. Окрема Commercial-ціна — спосіб монетизувати топові моделі для бізнес-клієнтів.') }}
                    </p>
                </section>

                {{-- Section 4: Файли --}}
                <section class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7">
                    <header class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-sm font-black text-emerald-100">4</span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('Файли') }}</p>
                            <h2 class="mt-0.5 text-xl font-bold text-white">{{ __('Завантаження файлів') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('Обкладинка для каталогу, опціональний 3D-preview і захищені source-файли.') }}</p>
                        </div>
                    </header>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="group flex flex-col rounded-2xl border border-dashed border-white/15 bg-zinc-950/40 p-5 transition hover:border-emerald-300/50 hover:bg-zinc-900/60">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-300/15 text-xs font-bold text-emerald-100">IMG</span>
                            <span class="mt-4 block text-sm font-semibold text-white">{{ __('Обкладинка') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Видна у каталозі та на сторінці моделі.') }}</span>
                            <input type="file" name="cover" accept="image/*" class="mt-3 w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-zinc-100 file:hover:bg-white/15">
                        </label>
                        <label class="group flex flex-col rounded-2xl border border-dashed border-white/15 bg-zinc-950/40 p-5 transition hover:border-emerald-300/50 hover:bg-zinc-900/60">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-300/15 text-xs font-bold text-emerald-100">MORE</span>
                            <span class="mt-4 block text-sm font-semibold text-white">{{ __('Фото галереї') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Додайте кілька фото для перегляду публікації: JPG, PNG, WEBP, GIF.') }}</span>
                            <input type="file" name="gallery[]" multiple accept="image/*" class="mt-3 w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-zinc-100 file:hover:bg-white/15">
                        </label>
                        <label class="group flex flex-col rounded-2xl border border-dashed border-white/15 bg-zinc-950/40 p-5 transition hover:border-sky-300/50 hover:bg-zinc-900/60">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-sky-300/15 text-xs font-bold text-sky-100">3D</span>
                            <span class="mt-4 block text-sm font-semibold text-white">{{ __('3D preview або зображення') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('GLB · GLTF · OBJ · STL для 3D-сцени, або GIF / PNG / JPG / WEBP як статичне прев’ю. Бачать усі відвідувачі.') }}</span>
                            <input type="file" name="preview_file" accept=".glb,.gltf,.obj,.stl,.gif,.png,.jpg,.jpeg,.webp" class="mt-3 w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-zinc-100 file:hover:bg-white/15">
                        </label>
                        <label class="group flex flex-col rounded-2xl border border-dashed border-white/15 bg-zinc-950/40 p-5 transition hover:border-violet-300/50 hover:bg-zinc-900/60">
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-violet-300/15 text-xs font-bold text-violet-100">ZIP</span>
                            <span class="mt-4 block text-sm font-semibold text-white">{{ __('Файли для завантаження') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('STL, OBJ, GLB, GLTF, ZIP, 3MF. Доступ після покупки.') }}</span>
                            <input type="file" name="files[]" multiple accept=".stl,.obj,.glb,.gltf,.zip,.3mf" class="mt-3 w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-zinc-100 file:hover:bg-white/15">
                        </label>
                    </div>
                    @if($isEdit && ($product->files->isNotEmpty() || filled($product->gallery) || $coverUrl))
                        <div class="mt-5 rounded-2xl border border-white/10 bg-zinc-950/50 p-4">
                            <h3 class="mb-3 text-sm font-semibold text-white">{{ __('Завантажені файли') }}</h3>
                            @if($coverUrl || filled($product->gallery))
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @if($coverUrl)
                                        <a href="{{ $coverUrl }}" target="_blank" class="group overflow-hidden rounded-2xl border border-white/10 bg-zinc-950">
                                            <img src="{{ $coverUrl }}" alt="{{ $product->localized('title') }}" class="aspect-square w-full object-cover transition group-hover:scale-105">
                                        </a>
                                    @endif
                                    @foreach(($product->gallery ?? []) as $image)
                                        @if(Storage::disk('public')->exists($image))
                                            <a href="{{ Storage::disk('public')->url($image) }}" target="_blank" class="group overflow-hidden rounded-2xl border border-white/10 bg-zinc-950">
                                                <img src="{{ Storage::disk('public')->url($image) }}" alt="{{ $product->localized('title') }}" class="aspect-square w-full object-cover transition group-hover:scale-105">
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <div class="divide-y divide-white/5">
                                @foreach($product->files as $file)
                                    <div class="flex flex-col gap-2 py-3 text-sm text-zinc-300 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-white">{{ $file->original_name }}</p>
                                            <p class="text-xs text-zinc-500">{{ strtoupper($file->extension) }} · {{ number_format($file->size / 1024, 1) }} KB @if($file->is_preview) · Preview @endif</p>
                                        </div>
                                        <button type="submit" form="delete-product-file-{{ $file->id }}" onclick="return confirm('{{ __('Видалити цей файл?') }}')" class="rounded-full border border-red-400/40 px-3 py-1.5 text-xs font-semibold text-red-200 hover:bg-red-400/10">
                                            {{ __('Видалити') }}
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                {{-- Section 5: Налаштування друку --}}
                <section class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7">
                    <header class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-sm font-black text-emerald-100">5</span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('Налаштування друку') }}</p>
                            <h2 class="mt-0.5 text-xl font-bold text-white">{{ __('Рекомендації для друку') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('Опційно — допоможе покупцю налаштувати слайсер.') }}</p>
                        </div>
                    </header>

                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Габарити моделі (мм)') }}</p>
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        @foreach(['x' => 'X', 'y' => 'Y', 'z' => 'Z'] as $axis => $label)
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-black uppercase tracking-widest text-emerald-300/70">{{ $label }}</span>
                                <input type="number" name="dim_{{ $axis }}" min="1" max="2000" value="{{ old('dim_'.$axis, $product->{'dim_'.$axis}) }}" placeholder="—" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 pl-7 pr-8 text-center font-mono text-sm font-semibold tabular-nums text-white placeholder:text-zinc-600 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] uppercase tracking-wider text-zinc-500">мм</span>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Рекомендовані матеріали') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @php $current = (array) old('recommended_materials', (array) $product->recommended_materials); @endphp
                        @foreach(['PLA', 'PETG', 'ABS', 'TPU', 'ASA', 'PC', 'PA', 'Resin'] as $m)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="recommended_materials[]" value="{{ $m }}" @checked(in_array($m, $current, true)) class="peer hidden">
                                <span class="inline-flex h-8 items-center rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-300 transition peer-checked:border-emerald-300/40 peer-checked:bg-emerald-300/[0.10] peer-checked:text-emerald-100">{{ $m }}</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Налаштування слайсера') }}</p>
                    @php $ps = (array) old('print_profile_settings', (array) $product->print_profile_settings); @endphp
                    <div class="mt-2 grid gap-3 md:grid-cols-3">
                        <label class="block">
                            <span class="text-[11px] text-zinc-500">{{ __('Висота шару') }}</span>
                            <input type="text" name="print_profile_settings[layer_height]" value="{{ $ps['layer_height'] ?? '' }}" placeholder="0.2 mm" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                        </label>
                        <label class="block">
                            <span class="text-[11px] text-zinc-500">{{ __('Сопло') }}</span>
                            <input type="text" name="print_profile_settings[nozzle]" value="{{ $ps['nozzle'] ?? '' }}" placeholder="0.4 mm" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                        </label>
                        <label class="block">
                            <span class="text-[11px] text-zinc-500">{{ __('Заповнення') }}</span>
                            <input type="text" name="print_profile_settings[infill]" value="{{ $ps['infill'] ?? '' }}" placeholder="20%" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                        </label>
                        <label class="block">
                            <span class="text-[11px] text-zinc-500">{{ __('Підтримки') }}</span>
                            <input type="text" name="print_profile_settings[supports]" value="{{ $ps['supports'] ?? '' }}" placeholder="{{ __('Так / Ні') }}" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                        </label>
                        <label class="block">
                            <span class="text-[11px] text-zinc-500">{{ __('Швидкість') }}</span>
                            <input type="text" name="print_profile_settings[speed]" value="{{ $ps['speed'] ?? '' }}" placeholder="60 mm/s" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                        </label>
                        <label class="block">
                            <span class="text-[11px] text-zinc-500">{{ __('Темп. сопла') }}</span>
                            <input type="text" name="print_profile_settings[temp_nozzle]" value="{{ $ps['temp_nozzle'] ?? '' }}" placeholder="210 °C" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                        </label>
                    </div>

                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Файл профілю слайсера') }}</p>
                    <p class="text-[11px] text-zinc-500">{{ __('3MF проєкт OrcaSlicer / Bambu Studio або готовий .gcode. Доступ після покупки.') }}</p>
                    <input type="file" name="print_profile_file" accept=".3mf,.gcode,.bgcode,.zip" class="mt-2 w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-zinc-100 file:hover:bg-white/15">
                    @if($product->print_profile_path)
                        <p class="mt-2 text-xs text-emerald-200">{{ __('Поточний:') }} <strong>{{ $product->print_profile_name ?: basename($product->print_profile_path) }}</strong>
                            <label class="ml-2 inline-flex items-center gap-1 text-rose-200">
                                <input type="checkbox" name="print_profile_remove" value="1" class="h-3 w-3 rounded border-white/20 bg-zinc-950 text-rose-400">
                                {{ __('видалити') }}
                            </label>
                        </p>
                    @endif
                </section>

                {{-- Section 6: Авторські права --}}
                <section class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-7">
                    <header class="flex items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-sm font-black text-emerald-100">6</span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300">{{ __('Авторські права') }}</p>
                            <h2 class="mt-0.5 text-xl font-bold text-white">{{ __('Підтвердження прав') }}</h2>
                        </div>
                    </header>
                    <div class="mt-5 rounded-2xl border border-amber-300/25 bg-amber-300/[0.08] p-4 text-sm leading-6 text-amber-100">
                        {{ __('Публікуйте лише власні моделі або файли, на які маєте право розповсюдження. Порушення авторських прав може призвести до видалення моделі.') }}
                    </div>
                    <label class="mt-4 flex items-start gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4 text-sm leading-6 text-zinc-200">
                        <input type="checkbox" class="mt-1 h-5 w-5 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                        <span>{{ __('Я підтверджую, що маю права на публікацію цієї моделі та погоджуюся з правилами публікації.') }}</span>
                    </label>
                </section>

                @if($errors->any())
                    <div class="rounded-2xl border border-red-400/30 bg-red-400/10 p-4 text-sm text-red-100">{{ $errors->first() }}</div>
                @endif
            </div>

            {{-- Sticky preview / checklist sidebar --}}
            <aside class="xl:sticky xl:top-24">
                <div class="flex flex-col overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-2xl shadow-black/30 xl:max-h-[calc(100vh-7rem)]">
                    <div class="overflow-y-auto p-5 [scrollbar-width:thin]">
                        {{-- Preview --}}
                        <div class="overflow-hidden rounded-2xl border border-white/10">
                            <div class="aspect-[4/3] bg-zinc-900">
                                @if($coverUrl)
                                    <img src="{{ $coverUrl }}" alt="{{ $product->localized('title') }}" class="h-full w-full object-cover">
                                @else
                                    <div class="grid h-full place-items-center bg-[linear-gradient(135deg,#101827,#06352d)]">
                                        <div class="grid h-16 w-16 place-items-center rounded-2xl bg-emerald-300/15 text-lg font-black text-emerald-100">3D</div>
                                    </div>
                                @endif
                            </div>
                            <div class="bg-zinc-950/60 p-4">
                                <x-ui.status :status="$product->status ?: 'draft'" />
                                <h3 class="mt-3 truncate text-base font-bold text-white">{{ $titleUk ?: __('Назва моделі') }}</h3>
                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-zinc-400">{{ old('short_description_uk', $product->localized('short_description', 'uk')) ?: __('Короткий опис зʼявиться у картці каталогу.') }}</p>
                            </div>
                        </div>

                        {{-- Progress --}}
                        <div class="mt-5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('Готовність') }}</span>
                                <span class="font-bold text-emerald-200">{{ $progressPct }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/[0.08]">
                                <div class="h-full rounded-full bg-emerald-400 transition-all" style="width: {{ $progressPct }}%"></div>
                            </div>
                        </div>

                        {{-- Checklist --}}
                        <ul class="mt-4 grid gap-2">
                            @foreach($checklist as $item)
                                <li class="flex items-center justify-between rounded-xl bg-zinc-950/50 px-3 py-2 text-sm">
                                    <span class="text-zinc-300">{{ $item['label'] }}</span>
                                    @if($item['done'])
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-200">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                            {{ __('готово') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-500">{{ __('потрібно') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Sticky actions --}}
                    <div class="border-t border-white/10 bg-zinc-950/70 p-4">
                        <div class="grid gap-2">
                            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300 disabled:cursor-wait disabled:opacity-70" :disabled="saving">
                                <span x-show="!saving">{{ $isEdit ? __('Оновити модель') : __('Відправити на модерацію') }}</span>
                                <span x-show="saving" x-cloak>{{ __('Зберігаємо...') }}</span>
                            </button>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl border border-white/15 bg-white/[0.05] px-4 text-sm font-semibold text-white transition hover:bg-white/[0.1] disabled:cursor-wait disabled:opacity-70" :disabled="saving">
                                {{ __('Зберегти чернетку') }}
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </form>

        @if($isEdit)
            @foreach($product->files as $file)
                <form id="delete-product-file-{{ $file->id }}" method="POST" action="{{ route('author.products.files.destroy', [$product, $file]) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </section>
</x-layouts.marketplace>
