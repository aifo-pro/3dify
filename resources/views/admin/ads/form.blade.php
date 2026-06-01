@php $isEdit = $ad->exists; @endphp

<x-layouts.admin
    :title="$isEdit ? 'Редагувати рекламу' : 'Нова реклама'"
    :breadcrumbs="[['label' => 'Реклама', 'url' => route('admin.ads.index')]]"
    :breadcrumb-current="$isEdit ? 'Редагувати' : 'Створити'"
>
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-300/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
            <ul class="grid gap-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.ads.update', $ad) : route('admin.ads.store') }}"
          enctype="multipart/form-data"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Left: main fields --}}
        <div class="space-y-5 lg:col-span-2">

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="mb-5 text-xs font-black uppercase tracking-widest text-zinc-500">Контент</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Заголовок (UK) *</label>
                        <input name="title_uk" value="{{ old('title_uk', $ad->localized('title','uk')) }}" required
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Заголовок (EN)</label>
                        <input name="title_en" value="{{ old('title_en', $ad->localized('title','en')) }}"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Короткий опис (UK)</label>
                        <input name="desc_uk" value="{{ old('desc_uk', $ad->localized('description','uk')) }}"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">URL переходу *</label>
                        <input name="target_url" type="url" value="{{ old('target_url', $ad->target_url) }}" required
                               placeholder="https://example.com"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Мітка на картці</label>
                        <input name="badge_label" value="{{ old('badge_label', $ad->badge_label ?: 'Реклама') }}"
                               placeholder="Реклама"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                        <p class="mt-1 text-xs text-zinc-600">Показується в кутку картки. Напр: «Реклама», «Спонсор», «Партнер»</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="mb-5 text-xs font-black uppercase tracking-widest text-zinc-500">Розміщення</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Тип реклами</label>
                        <select name="ad_type" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                            <option value="grid"    @selected(old('ad_type', $ad->ad_type) === 'grid')>В каталозі (нативна картка)</option>
                            <option value="banner"  @selected(old('ad_type', $ad->ad_type) === 'banner')>Банер (верх сторінки)</option>
                            <option value="sidebar" @selected(old('ad_type', $ad->ad_type) === 'sidebar')>Сайдбар</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Показувати кожні N карток</label>
                        <input name="grid_every" type="number" min="2" max="100"
                               value="{{ old('grid_every', $ad->grid_every ?: 8) }}"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                        <p class="mt-1 text-xs text-zinc-600">Тільки для типу «В каталозі». 8 = після 8-ї, 16-ї, 24-ї картки…</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-bold text-zinc-400">Сторінки показу</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach(\App\Models\Advertisement::PAGES as $page)
                                @php $checked = in_array($page, old('pages', $ad->pages ?? \App\Models\Advertisement::PAGES)); @endphp
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-300">
                                    <input type="checkbox" name="pages[]" value="{{ $page }}" @checked($checked)
                                           class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400">
                                    {{ ['catalog' => 'Каталог', 'category' => 'Категорія', 'home' => 'Головна', 'search' => 'Пошук'][$page] }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Дата початку</label>
                        <input type="datetime-local" name="starts_at"
                               value="{{ old('starts_at', $ad->starts_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-zinc-400">Дата закінчення</label>
                        <input type="datetime-local" name="ends_at"
                               value="{{ old('ends_at', $ad->ends_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: image + actions --}}
        <div class="space-y-5">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <h2 class="mb-4 text-xs font-black uppercase tracking-widest text-zinc-500">Зображення</h2>
                @if($ad->imageUrl())
                    <div class="mb-3 overflow-hidden rounded-xl">
                        <img src="{{ $ad->imageUrl() }}" class="aspect-video w-full object-cover">
                    </div>
                @else
                    <div class="mb-3 flex aspect-video items-center justify-center rounded-xl border border-dashed border-white/10 text-xs text-zinc-600">
                        Зображення не завантажено
                    </div>
                @endif
                <input type="file" name="image" accept="image/*"
                       class="w-full text-sm text-zinc-400 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-400/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-300">
                <p class="mt-2 text-xs text-zinc-600">Рекомендований розмір: 800×450px (16:9) або 400×500px для портрет.</p>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <label class="mb-4 flex items-center gap-3">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $ad->is_active))
                           class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400">
                    <div>
                        <p class="text-sm font-bold text-white">Активна</p>
                        <p class="text-xs text-zinc-500">Показується відвідувачам</p>
                    </div>
                </label>

                @if($isEdit)
                    {{-- Live stats --}}
                    <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-black/20 p-3 text-center">
                        <div>
                            <p class="text-xs text-zinc-600">Покази</p>
                            <p class="text-base font-black text-white">{{ number_format($ad->impressions) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-600">Кліки</p>
                            <p class="text-base font-black text-white">{{ number_format($ad->clicks) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-600">CTR</p>
                            <p class="text-base font-black {{ $ad->ctr() >= 2 ? 'text-emerald-400' : 'text-white' }}">{{ $ad->ctr() }}%</p>
                        </div>
                    </div>
                @endif

                <div class="mt-4 grid gap-2">
                    <button type="submit" class="w-full rounded-xl bg-emerald-400 py-3 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">
                        {{ $isEdit ? 'Зберегти зміни' : 'Створити рекламу' }}
                    </button>
                    <a href="{{ route('admin.ads.index') }}" class="block w-full rounded-xl border border-white/10 py-2.5 text-center text-sm text-zinc-400 hover:bg-white/[0.04]">Скасувати</a>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
