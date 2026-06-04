@php
    $s = app(\App\Services\SiteSettings::class);
    $currentTab = $tab ?? request('tab', 'general');

    $val = fn (string $key, $default = '') => (string) $s->get($key, $default);
    $bool = fn (string $key, bool $default = false) => (bool) $s->get($key, $default);
    $list = fn (string $key, array $default = []) => is_array($v = $s->get($key, $default)) ? $v : $default;

    $tabs = [
        ['key' => 'general', 'label' => __('Загальні'), 'icon' => 'sliders'],
        ['key' => 'branding', 'label' => __('Брендинг'), 'icon' => 'palette'],
        ['key' => 'homepage', 'label' => __('Головна сторінка'), 'icon' => 'home'],
        ['key' => 'seo', 'label' => __('SEO'), 'icon' => 'search'],
        ['key' => 'payments', 'label' => __('Платежі'), 'icon' => 'credit'],
        ['key' => 'mail', 'label' => __('SMTP / Email'), 'icon' => 'mail'],
        ['key' => 'translations', 'label' => __('Переклади'), 'icon' => 'globe'],
        ['key' => 'email_templates', 'label' => __('Email-шаблони'), 'icon' => 'inbox'],
        ['key' => 'pages', 'label' => __('Сторінки футера'), 'icon' => 'doc'],
        ['key' => 'legal', 'label' => __('Юридичні'), 'icon' => 'shield'],
        ['key' => 'social', 'label' => __('Соцмережі'), 'icon' => 'share'],
        ['key' => 'header_footer', 'label' => __('Header / Footer'), 'icon' => 'layout'],
    ];

    $tabIcon = function (string $name): string {
        return match ($name) {
            'sliders' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>',
            'palette' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>',
            'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
            'credit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
            'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
            'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
            'inbox' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'share' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
            'layout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>',
            'doc' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg>',
            default => '',
        };
    };

    // Group helpers for forms
    $supportedLanguages = $list('site.supported_languages', ['uk', 'en']);
    $mailPort = (int) $val('mail.port', config('mail.mailers.smtp.port', '587'));
    $mailEncryption = strtolower($val('mail.encryption', 'tls'));
    if ($mailPort === 587 && in_array($mailEncryption, ['ssl', 'smtps'], true)) {
        $mailEncryption = 'tls';
    }
    if ($mailPort === 465 && in_array($mailEncryption, ['tls', 'starttls'], true)) {
        $mailEncryption = 'ssl';
    }

    $emailTypes = [
        'registration' => __('Реєстрація'),
        'email_verification' => __('Підтвердження email'),
        'password_reset' => __('Скидання пароля'),
        'purchase_success' => __('Успішна покупка'),
        'model_sold' => __('Модель продано'),
        'model_approved' => __('Модель схвалено'),
        'model_rejected' => __('Модель відхилено'),
    ];
    $emailTypes = \App\Services\EmailTemplateCatalog::labels();
    $emailTemplateCatalog = \App\Services\EmailTemplateCatalog::templates();
@endphp

<x-layouts.admin
    :title="__('Налаштування сайту')"
    :description="__('Глобальні параметри маркетплейсу: бренд, контент, SEO, платежі, email та інтеграції.')"
    active="content"
    :breadcrumbs="[['label' => __('Налаштування')]]"
>
    <x-slot:actions>
        <span class="inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-3 text-[11px] font-bold uppercase tracking-wider text-zinc-300">
            <span class="grid h-1.5 w-1.5 place-items-center rounded-full {{ app()->environment('production') ? 'bg-emerald-400 ring-2 ring-emerald-400/30' : 'bg-amber-400 ring-2 ring-amber-400/30' }}"></span>
            {{ app()->environment() }}
        </span>
        <a href="{{ route('home') }}" target="_blank" class="inline-flex h-9 items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-200 hover:bg-white/10">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
            {{ __('Перейти на сайт') }}
        </a>
    </x-slot:actions>

    <div x-data="{ tab: '{{ $currentTab }}' }" class="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
        {{-- Sidebar --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-2 shadow-xl shadow-black/20">
                <ul class="grid gap-0.5">
                    @foreach($tabs as $t)
                        <li>
                            <a
                                href="{{ route('admin.content', ['tab' => $t['key']]) }}"
                                @click.prevent="tab = '{{ $t['key'] }}'; history.replaceState(null, '', '?tab={{ $t['key'] }}')"
                                :class="tab === '{{ $t['key'] }}'
                                    ? 'bg-emerald-300/15 text-emerald-100 shadow-inner shadow-emerald-500/10'
                                    : 'text-zinc-400 hover:bg-white/[0.06] hover:text-white'"
                                class="flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition"
                            >
                                <span class="grid h-5 w-5 shrink-0 place-items-center" :class="tab === '{{ $t['key'] }}' ? 'text-emerald-200' : 'text-zinc-500'">
                                    {!! $tabIcon($t['icon']) !!}
                                </span>
                                <span class="truncate">{{ $t['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        {{-- Content --}}
        <div class="min-w-0">

        {{-- ============================================================ --}}
        {{-- 1. ЗАГАЛЬНІ                                                    --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'general'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="site">
                <input type="hidden" name="tab" value="general">

                <x-admin.settings-card
                    :title="__('Основні параметри')"
                    :description="__('Назва сайту, домен, мова та контактні дані.')"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[site.name]" :label="__('Назва сайту')" :value="$val('site.name', '3Dify')" placeholder="3Dify" required />
                        <x-admin.field name="settings[site.url]" label="URL" :value="$val('site.url', config('app.url'))" placeholder="https://3dify.local" type="url" />
                        <x-admin.field name="settings[site.contact_email]" :label="__('Контактний email')" :value="$val('site.contact_email')" placeholder="hello@3dify.local" type="email" />
                        <x-admin.field name="settings[site.support_email]" :label="__('Email підтримки')" :value="$val('site.support_email')" placeholder="support@3dify.local" type="email" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card
                    :title="__('Регіональні налаштування')"
                    :description="__('Мова інтерфейсу, валюта та часова зона.')"
                >
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-admin.field as="select" name="settings[site.default_language]" :label="__('Мова за замовчуванням')">
                            <option value="uk" @selected($val('site.default_language', 'uk') === 'uk')>Українська</option>
                            <option value="en" @selected($val('site.default_language', 'uk') === 'en')>English</option>
                        </x-admin.field>
                        <x-admin.field as="select" name="settings[site.currency]" :label="__('Валюта')">
                            @foreach(['UAH'] as $cur)
                                <option value="{{ $cur }}" @selected($val('site.currency', 'UAH') === $cur)>{{ $cur }}</option>
                            @endforeach
                        </x-admin.field>
                        <x-admin.field as="select" name="settings[site.timezone]" :label="__('Часовий пояс')">
                            @foreach(['UTC', 'Europe/Kyiv', 'Europe/Warsaw', 'Europe/Berlin', 'Europe/London'] as $tz)
                                <option value="{{ $tz }}" @selected($val('site.timezone', 'Europe/Kyiv') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </x-admin.field>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Підтримувані мови') }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            @foreach([
                                'uk' => 'Українська',
                                'en' => 'English',
                                'pl' => 'Polski',
                                'de' => 'Deutsch',
                            ] as $code => $label)
                                <label class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-zinc-950/40 px-3 py-2 text-sm text-zinc-200">
                                    <input type="checkbox" name="lists[site.supported_languages][]" value="{{ $code }}" @checked(in_array($code, $supportedLanguages, true)) class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                                    <span>{{ $label }}</span>
                                    <span class="text-[10px] font-mono text-zinc-500">{{ $code }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card
                    :title="__('Доступ і реєстрація')"
                    :description="__('Контролюйте, хто може реєструватись і публікувати.')"
                >
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-admin.toggle
                            name="settings[site.maintenance_mode]"
                            :label="__('Maintenance mode')"
                            :description="__('Сайт буде показувати сторінку обслуговування.')"
                            :checked="$bool('site.maintenance_mode')"
                        />
                        <x-admin.toggle
                            name="settings[site.user_registration_enabled]"
                            :label="__('Реєстрація користувачів')"
                            :description="__('Дозволити нові акаунти.')"
                            :checked="$bool('site.user_registration_enabled', true)"
                        />
                        <x-admin.toggle
                            name="settings[site.author_registration_enabled]"
                            :label="__('Реєстрація авторів')"
                            :description="__('Дозволити публікувати моделі.')"
                            :checked="$bool('site.author_registration_enabled', true)"
                        />
                        <x-admin.toggle
                            name="settings[site.moderation_required]"
                            :label="__('Модерація обовʼязкова')"
                            :description="__('Кожна модель проходить ручну перевірку.')"
                            :checked="$bool('site.moderation_required', true)"
                        />
                    </div>
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        {{ __('Зберегти зміни') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- 2. БРЕНДИНГ                                                    --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'branding'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" enctype="multipart/form-data" class="grid gap-7">
                @csrf
                <input type="hidden" name="group" value="brand">
                <input type="hidden" name="tab" value="branding">

                @if($errors->any())
                    <div class="rounded-3xl border border-rose-400/25 bg-rose-400/[0.08] p-5 text-sm text-rose-100">
                        <p class="font-bold">{{ __('Не вдалося зберегти брендинг') }}</p>
                        <ul class="mt-3 grid gap-1.5 text-xs leading-5 text-rose-100/85">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <x-admin.settings-card :title="__('Логотипи та зображення')" :description="__('Завантажте логотип, фавікон та OG-зображення для соцмереж.')">
                    <div class="grid gap-5 xl:grid-cols-2">
                        <x-admin.asset-upload
                            name="assets[brand.logo_path]"
                            :label="__('Логотип (світлий)')"
                            :description="__('Основний логотип. SVG/PNG до 2MB.')"
                            :currentPath="$val('brand.logo_path')"
                            tab="branding"
                        />
                        <x-admin.asset-upload
                            name="assets[brand.dark_logo_path]"
                            :label="__('Логотип (темний)')"
                            :description="__('Для темних фонів.')"
                            :currentPath="$val('brand.dark_logo_path')"
                            tab="branding"
                        />
                        <x-admin.asset-upload
                            name="assets[brand.favicon_path]"
                            :label="__('Favicon')"
                            :description="__('ICO/PNG/SVG, 32×32 або 64×64.')"
                            :currentPath="$val('brand.favicon_path')"
                            tab="branding"
                        />
                        <x-admin.asset-upload
                            name="assets[brand.og_image_path]"
                            :label="__('OG image')"
                            :description="__('Для соцмереж і месенджерів. Рекомендовано 1200×630, JPG/PNG/WebP до 8MB.')"
                            :currentPath="$val('brand.og_image_path')"
                            tab="branding"
                        />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Кольори бренду')" :description="__('Основний та акцентний кольори.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Primary color') }}</span>
                            <div class="flex items-center gap-3">
                                <input type="color" name="settings[brand.primary_color]" value="{{ $val('brand.primary_color', '#34d399') }}" class="h-10 w-14 cursor-pointer rounded-lg border border-white/10 bg-zinc-950">
                                <input type="text" value="{{ $val('brand.primary_color', '#34d399') }}" oninput="this.previousElementSibling.value = this.value" class="h-10 flex-1 rounded-xl border border-white/10 bg-zinc-950/70 px-3 font-mono text-sm text-white">
                            </div>
                        </label>
                        <label class="grid gap-1.5">
                            <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Accent color') }}</span>
                            <div class="flex items-center gap-3">
                                <input type="color" name="settings[brand.accent_color]" value="{{ $val('brand.accent_color', '#38bdf8') }}" class="h-10 w-14 cursor-pointer rounded-lg border border-white/10 bg-zinc-950">
                                <input type="text" value="{{ $val('brand.accent_color', '#38bdf8') }}" oninput="this.previousElementSibling.value = this.value" class="h-10 flex-1 rounded-xl border border-white/10 bg-zinc-950/70 px-3 font-mono text-sm text-white">
                            </div>
                        </label>
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Слоган і опис')" :description="__('Текстове представлення проєкту.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[brand.slogan_uk]" :label="__('Слоган (UK)')" :value="$val('brand.slogan_uk')" placeholder="Друкуй впевнено" />
                        <x-admin.field name="settings[brand.slogan_en]" :label="__('Слоган (EN)')" :value="$val('brand.slogan_en')" placeholder="Print with confidence" />
                        <x-admin.field as="textarea" rows="3" name="settings[brand.short_description_uk]" :label="__('Короткий опис (UK)')" :value="$val('brand.short_description_uk')" />
                        <x-admin.field as="textarea" rows="3" name="settings[brand.short_description_en]" :label="__('Короткий опис (EN)')" :value="$val('brand.short_description_en')" />
                    </div>
                </x-admin.settings-card>

                <div class="sticky bottom-4 z-20 rounded-3xl border border-white/10 bg-zinc-950/85 p-3 shadow-2xl shadow-black/35 backdrop-blur-xl sm:flex sm:justify-end">
                    <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-400 px-7 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300 sm:w-auto">
                        {{ __('Зберегти брендинг') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- 3. ГОЛОВНА СТОРІНКА                                            --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'homepage'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="home">
                <input type="hidden" name="tab" value="homepage">

                <x-admin.settings-card :title="__('Hero-блок')" :description="__('Перший екран — заголовок, підзаголовок і CTA.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[home.hero_title_uk]" :label="__('Заголовок (UK)')" :value="$val('home.hero_title_uk')" placeholder="{{ __('Маркетплейс 3D-моделей') }}" />
                        <x-admin.field name="settings[home.hero_title_en]" :label="__('Заголовок (EN)')" :value="$val('home.hero_title_en')" placeholder="3D model marketplace" />
                        <x-admin.field as="textarea" rows="3" name="settings[home.hero_subtitle_uk]" :label="__('Підзаголовок (UK)')" :value="$val('home.hero_subtitle_uk')" />
                        <x-admin.field as="textarea" rows="3" name="settings[home.hero_subtitle_en]" :label="__('Підзаголовок (EN)')" :value="$val('home.hero_subtitle_en')" />
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-300">{{ __('CTA — primary') }}</p>
                            <x-admin.field name="settings[home.cta_primary_text]" :label="__('Текст')" :value="$val('home.cta_primary_text')" placeholder="{{ __('Дивитися каталог') }}" />
                            <x-admin.field name="settings[home.cta_primary_url]" label="URL" :value="$val('home.cta_primary_url')" placeholder="/models" />
                        </div>
                        <div class="grid gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('CTA — secondary') }}</p>
                            <x-admin.field name="settings[home.cta_secondary_text]" :label="__('Текст')" :value="$val('home.cta_secondary_text')" placeholder="{{ __('Стати автором') }}" />
                            <x-admin.field name="settings[home.cta_secondary_url]" label="URL" :value="$val('home.cta_secondary_url')" placeholder="/register" />
                        </div>
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Видимість блоків')" :description="__('Що показувати на головній сторінці.')">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <x-admin.toggle name="settings[home.featured_categories_enabled]" :label="__('Категорії')" :description="__('Блок категорій для швидкого старту.')" :checked="$bool('home.featured_categories_enabled', true)" />
                        <x-admin.toggle name="settings[home.popular_models_enabled]" :label="__('Популярні моделі')" :description="__('Топ за переглядами.')" :checked="$bool('home.popular_models_enabled', true)" />
                        <x-admin.toggle name="settings[home.free_models_enabled]" :label="__('Безкоштовні')" :description="__('Окремий блок з free-моделями.')" :checked="$bool('home.free_models_enabled', true)" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Блок «Для авторів»')" :description="__('Заохочення публікувати моделі.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[home.author_block_title_uk]" :label="__('Заголовок (UK)')" :value="$val('home.author_block_title_uk')" />
                        <x-admin.field name="settings[home.author_block_title_en]" :label="__('Заголовок (EN)')" :value="$val('home.author_block_title_en')" />
                        <x-admin.field as="textarea" rows="3" name="settings[home.author_block_description_uk]" :label="__('Опис (UK)')" :value="$val('home.author_block_description_uk')" />
                        <x-admin.field as="textarea" rows="3" name="settings[home.author_block_description_en]" :label="__('Опис (EN)')" :value="$val('home.author_block_description_en')" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('SEO-текст під каталогом')" :description="__('Корисний для пошукової видачі довгий текст внизу головної.')">
                    <div class="grid gap-4">
                        <x-admin.field as="textarea" rows="5" name="settings[home.seo_text_uk]" :label="__('SEO-текст (UK)')" :value="$val('home.seo_text_uk')" />
                        <x-admin.field as="textarea" rows="5" name="settings[home.seo_text_en]" :label="__('SEO-текст (EN)')" :value="$val('home.seo_text_en')" />
                    </div>
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти головну') }}</button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- 4. SEO                                                          --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'seo'" x-cloak class="grid gap-5">
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="seo">
                <input type="hidden" name="tab" value="seo">

                <x-admin.settings-card :title="__('Дефолтні мета-теги')" :description="__('Використовуються коли у сторінки немає власних SEO даних.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[seo.meta_title_uk]" :label="__('Meta title (UK)')" :value="$val('seo.meta_title_uk')" :helper="__('Оптимально 50–60 символів.')" />
                        <x-admin.field name="settings[seo.meta_title_en]" :label="__('Meta title (EN)')" :value="$val('seo.meta_title_en')" />
                        <x-admin.field as="textarea" rows="3" name="settings[seo.meta_description_uk]" :label="__('Meta description (UK)')" :value="$val('seo.meta_description_uk')" :helper="__('150–160 символів.')" />
                        <x-admin.field as="textarea" rows="3" name="settings[seo.meta_description_en]" :label="__('Meta description (EN)')" :value="$val('seo.meta_description_en')" />
                        <x-admin.field name="settings[seo.meta_keywords]" :label="__('Keywords (через кому)')" :value="$val('seo.meta_keywords')" />
                        <x-admin.field name="settings[seo.canonical_url]" :label="__('Canonical URL')" :value="$val('seo.canonical_url')" :placeholder="config('app.url')" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Open Graph')" :description="__('Як сайт виглядає при шерах у Facebook, LinkedIn, Telegram.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[seo.og_title]" label="OG title" :value="$val('seo.og_title')" />
                        <x-admin.field as="textarea" rows="3" name="settings[seo.og_description]" label="OG description" :value="$val('seo.og_description')" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('robots.txt та sitemap')" :description="__('Контроль індексації пошуковиками.')">
                    <x-admin.field as="textarea" rows="6" name="settings[seo.robots_txt]" label="robots.txt" :value="$val('seo.robots_txt', \"User-agent: *\nAllow: /\nSitemap: /sitemap.xml\")" />
                    <div class="mt-4">
                        <x-admin.toggle name="settings[seo.sitemap_enabled]" :label="__('Sitemap')" :description="__('Генерувати /sitemap.xml автоматично.')" :checked="$bool('seo.sitemap_enabled', true)" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Аналітика та верифікація')" :description="__('Підключення Google-сервісів.')">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-admin.field name="settings[seo.ga_id]" label="Google Analytics ID" :value="$val('seo.ga_id')" placeholder="G-XXXXXXXXXX" />
                        <x-admin.field name="settings[seo.gtm_id]" label="Google Tag Manager ID" :value="$val('seo.gtm_id')" placeholder="GTM-XXXXXXX" />
                        <x-admin.field name="settings[seo.gsc_verification]" label="Search Console" :value="$val('seo.gsc_verification')" :helper="__('Verification token')" />
                    </div>
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти SEO') }}</button>
                </div>
            </form>

            {{-- Per-route SEO pages --}}
            <x-admin.settings-card :title="__('SEO для окремих сторінок')" :description="__('Перевизначення мета-тегів для конкретних маршрутів.')">
                <form method="POST" action="{{ route('admin.seo.store') }}" class="grid gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4 sm:grid-cols-[1fr_120px_1fr] sm:items-end">
                    @csrf
                    <x-admin.field name="route_name" :label="__('Route name')" placeholder="products.index" required />
                    <x-admin.field as="select" name="locale" :label="__('Мова')">
                        <option value="uk">UK</option>
                        <option value="en">EN</option>
                    </x-admin.field>
                    <x-admin.field name="title" label="Title" placeholder="..." />
                    <x-admin.field as="textarea" rows="2" name="description" label="Description" placeholder="..." />
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300 sm:col-span-3">{{ __('Додати / оновити') }}</button>
                </form>

                <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-950/40">
                            <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                                <th class="px-4 py-3">Route</th>
                                <th class="px-4 py-3">{{ __('Мова') }}</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3 text-right">{{ __('Дії') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($seoPages as $page)
                                <tr>
                                    <td class="px-4 py-2.5 font-mono text-xs text-zinc-300">{{ $page->route_name }}</td>
                                    <td class="px-4 py-2.5 text-xs uppercase text-zinc-400">{{ $page->locale }}</td>
                                    <td class="px-4 py-2.5 text-sm text-white">{{ $page->title ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <form method="POST" action="{{ route('admin.seo.destroy', $page) }}" onsubmit="return confirm('{{ __('Видалити SEO?') }}')" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                    @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-xs text-zinc-500">{{ __('Поки немає переозначень.') }}</td></tr>
                    @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.settings-card>
        </div>

        {{-- ============================================================ --}}
        {{-- 5. ПЛАТЕЖІ                                                     --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'payments'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="payments">
                <input type="hidden" name="tab" value="payments">

                <x-admin.settings-card :title="__('aifo.pro інтеграція')" :description="__('Реквізити з кабінету aifo.pro. Endpoint і ключі також можна задати тут — вони зберігаються в БД.')">
                    <div class="mb-4">
                        <x-admin.toggle name="settings[payments.test_mode]" :label="__('Тестовий режим (sandbox)')" :description="__('Реальні гроші не списуються.')" :checked="$bool('payments.test_mode', true)" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-admin.field
                                name="settings[payments.aifo_endpoint]"
                                :label="__('AIFO API endpoint (POST, створення платежу)')"
                                :value="$val('payments.aifo_endpoint', $val('payments.api_endpoint'))"
                                :helper="__('Це URL API на aifo.pro для створення інвойсу (наприклад https://aifo.pro/api/v2/invoices/create). Не вставляйте сюди адресу webhook вашого сайту — вона нижче в блоці «Webhook AIFO». Поле можна залишити порожнім: тоді використовується значення за замовчуванням.')"
                                placeholder="https://aifo.pro/api/v2/invoices/create"
                            />
                        </div>
                        <x-admin.field name="settings[payments.aifo_merchant_id]" label="Merchant ID" :value="$val('payments.aifo_merchant_id', $val('payments.merchant_id'))" />
                        <x-admin.field name="settings[payments.aifo_api_key]" :label="__('API key (Bearer)')" :value="$val('payments.aifo_api_key', $val('payments.api_key'))" />
                        <x-admin.field type="password" name="settings[payments.aifo_webhook_secret]" :label="__('Секрет для webhook (HMAC)')" :value="$val('payments.aifo_webhook_secret', $val('payments.secret_key'))" :helper="__('Використовується для перевірки заголовка X-Aifo-Signature. Зазвичай збігається з «Secret key» у кабінеті мерчанта.')" />
                        <x-admin.field name="settings[payments.platform_commission_percent]" :label="__('Комісія платформи (%)')" :value="$val('payments.platform_commission_percent', '10')" type="number" :min="0" :max="100" :step="0.1" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('URL-и в кабінеті aifo.pro')" :description="__('У застосунку success/webhook передаються в API при кожному платежі. Нижче — що зазвичай треба вписати в панелі AIFO як базові адреси.')">
                    <div class="mb-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-xs leading-relaxed text-zinc-400">
                        <p class="font-bold text-zinc-300">{{ __('Webhook AIFO (один URL на все)') }}</p>
                        <p class="mt-2">{{ __('У кабінеті AIFO вкажіть лише цей POST webhook — і замовлення моделей, і подяки автору обробляються разом:') }}</p>
                        <p class="mt-2"><code class="rounded bg-zinc-950/80 px-1.5 py-0.5 font-mono text-[11px] text-emerald-200/90">{{ url('/payments/aifo/webhook') }}</code></p>
                        <p class="mt-3 text-[11px] text-zinc-500">{{ __('Старий шлях /payments/aifo/tips/webhook ще працює як перенаправлення на той самий обробник, але в панелі AIFO достатньо основного URL.') }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field name="settings[payments.webhook_url]" label="Webhook URL (копія для довідки)" :value="$val('payments.webhook_url', url('/payments/aifo/webhook'))" :helper="__('Можна залишити як у кабінеті AIFO.')" />
                        <x-admin.field name="settings[payments.success_url]" :label="__('Success URL (опційно, для кабінету AIFO)')" :value="$val('payments.success_url', url('/'))" :helper="__('Після оплати користувач часто повертається на сторінку замовлення / моделі — це формує застосунок.')" />
                        <x-admin.field name="settings[payments.fail_url]" :label="__('Fail URL (опційно)')" :value="$val('payments.fail_url', url('/'))" />
                    </div>
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти платежі') }}</button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- 6. SMTP / EMAIL                                                --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'mail'" x-cloak class="grid gap-5">
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="mail">
                <input type="hidden" name="tab" value="mail">

                <x-admin.settings-card :title="__('SMTP-сервер')" :description="__('Параметри підключення до поштового сервера.')">
                    <div class="mb-5 rounded-2xl border border-sky-300/20 bg-sky-300/[0.06] p-4 text-xs leading-6 text-sky-100">
                        <p class="font-black text-white">Standard SMTP</p>
                        <p class="mt-1 text-sky-100/80">Enter the SMTP host, port, username, password, encryption, and sender address from your mail provider.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field as="select" name="settings[mail.mailer]" label="Mailer">
                            @foreach(['smtp' => 'SMTP', 'log' => 'Log only', 'array' => 'Array test'] as $mailer => $label)
                                <option value="{{ $mailer }}" @selected($val('mail.mailer', config('mail.default')) === $mailer)>{{ $label }}</option>
                            @endforeach
                        </x-admin.field>
                        <x-admin.field name="settings[mail.host]" label="Host" :value="$val('mail.host', config('mail.mailers.smtp.host'))" placeholder="smtp.example.com" />
                        <x-admin.field type="number" name="settings[mail.port]" label="Port" :value="$val('mail.port', config('mail.mailers.smtp.port', '587'))" placeholder="587" />
                        <x-admin.field name="settings[mail.username]" label="Username" :value="$val('mail.username')" placeholder="SMTP username" />
                        <x-admin.field type="password" name="settings[mail.password]" label="Password" :value="''" placeholder="Leave empty to keep current password" />
                        <x-admin.field as="select" name="settings[mail.encryption]" label="Encryption">
                            @foreach(['tls', 'ssl', ''] as $enc)
                                <option value="{{ $enc }}" @selected($mailEncryption === $enc)>{{ $enc ?: '— none' }}</option>
                            @endforeach
                        </x-admin.field>
                        <x-admin.field name="settings[mail.from_address]" label="From address" :value="$val('mail.from_address', config('mail.from.address'))" type="email" />
                        <x-admin.field name="settings[mail.from_name]" label="From name" :value="$val('mail.from_name', config('mail.from.name'))" />
                        <x-admin.field name="settings[mail.ehlo_domain]" label="EHLO domain" :value="$val('mail.ehlo_domain', parse_url(config('app.url'), PHP_URL_HOST))" placeholder="3dify.dev" />
                    </div>
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти SMTP') }}</button>
                </div>
            </form>

            <x-admin.settings-card :title="__('Тестовий лист')" :description="__('Перевірте, чи коректно надсилаються листи.')">
                <form method="POST" action="{{ route('admin.mail.test') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-[260px] flex-1">
                        <x-admin.field type="email" name="to" :label="__('Кому надіслати')" placeholder="you@example.com" required />
                    </div>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 text-sm font-bold text-amber-100 hover:bg-amber-300/15">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        {{ __('Надіслати тест') }}
                    </button>
                </form>
            </x-admin.settings-card>
        </div>

        {{-- ============================================================ --}}
        {{-- 7. ПЕРЕКЛАДИ                                                   --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'translations'" x-cloak class="grid gap-5">
            <x-admin.settings-card :title="__('Переклади інтерфейсу')" :description="__('Власні рядки для замін стандартних повідомлень.')">
                <div class="mb-4 grid gap-3 lg:grid-cols-2">
                    @foreach($emailTemplateCatalog as $tplKey => $meta)
                        @php
                            $hasUk = $emailTemplates->contains(fn ($tpl) => $tpl->key === $tplKey && $tpl->locale === 'uk');
                            $hasEn = $emailTemplates->contains(fn ($tpl) => $tpl->key === $tplKey && $tpl->locale === 'en');
                        @endphp
                        <div class="rounded-2xl border border-white/10 bg-white/[0.035] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-white">{{ $emailTypes[$tplKey] ?? $tplKey }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __($meta['description'] ?? '') }}</p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $hasUk ? 'bg-emerald-300/15 text-emerald-200' : 'bg-amber-300/15 text-amber-200' }}">UK</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $hasEn ? 'bg-emerald-300/15 text-emerald-200' : 'bg-amber-300/15 text-amber-200' }}">EN</span>
                                </div>
                            </div>
                            <p class="mt-3 line-clamp-2 font-mono text-[10px] leading-relaxed text-zinc-500">{{ implode(' · ', $meta['variables'] ?? []) }}</p>
                            <button
                                type="button"
                                x-data
                                @click="$dispatch('edit-email', {
                                    id: '',
                                    key: '{{ $tplKey }}',
                                    locale: 'uk',
                                    subject: @js($meta['defaults']['uk']['subject'] ?? ''),
                                    body: @js($meta['defaults']['uk']['body'] ?? ''),
                                    is_active: true
                                })"
                                class="mt-3 inline-flex h-8 items-center rounded-xl border border-emerald-300/20 bg-emerald-300/[0.08] px-3 text-xs font-bold text-emerald-100 transition hover:bg-emerald-300/[0.14]"
                            >
                                {{ __('Вставити базовий UK шаблон') }}
                            </button>
                        </div>
                    @endforeach
                </div>

                <form
                    x-data="{ id: '', locale: 'uk', group: 'messages', key: '', value: '' }"
                    method="POST"
                    action="{{ route('admin.translations.store') }}"
                    class="grid gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4 sm:grid-cols-[100px_140px_1fr_2fr_auto] sm:items-end"
                    @edit-translation.window="id = $event.detail.id; locale = $event.detail.locale; group = $event.detail.group; key = $event.detail.key; value = $event.detail.value || ''; $el.scrollIntoView({behavior:'smooth', block:'center'})"
                >
                    @csrf
                    <input type="hidden" name="id" x-model="id">
                    <x-admin.field as="select" name="locale" :label="__('Мова')" x-model="locale">
                        <option value="uk">UK</option>
                        <option value="en">EN</option>
                    </x-admin.field>
                    <x-admin.field name="group" :label="__('Група')" x-model="group" placeholder="messages" required />
                    <x-admin.field name="key" :label="__('Ключ')" x-model="key" placeholder="home.title" required />
                    <x-admin.field name="value" :label="__('Значення')" x-model="value" />
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300">
                        <span x-text="id ? '{{ __('Оновити') }}' : '{{ __('Додати') }}'">{{ __('Додати') }}</span>
                    </button>
                </form>

                <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-950/40">
                            <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                                <th class="px-4 py-3">{{ __('Мова') }}</th>
                                <th class="px-4 py-3">{{ __('Група') }}</th>
                                <th class="px-4 py-3">{{ __('Ключ') }}</th>
                                <th class="px-4 py-3">{{ __('Значення') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Дії') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($translations as $t)
                                <tr class="transition hover:bg-white/[0.02]">
                                    <td class="px-4 py-2.5"><span class="rounded-full bg-white/[0.06] px-2 py-0.5 text-[10px] font-bold uppercase text-zinc-200">{{ $t->locale }}</span></td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-zinc-400">{{ $t->group }}</td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-zinc-300">{{ $t->key }}</td>
                                    <td class="px-4 py-2.5 text-xs text-white"><span class="line-clamp-1">{{ $t->value }}</span></td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" x-data @click="$dispatch('edit-translation', { id: {{ $t->id }}, locale: '{{ $t->locale }}', group: '{{ $t->group }}', key: '{{ $t->key }}', value: @js($t->value) })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('admin.translations.destroy', $t) }}" onsubmit="return confirm('{{ __('Видалити переклад?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-xs text-zinc-500">{{ __('Перекладів ще немає. Додайте перший вище.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($translations->hasPages())
                    <div class="mt-4">{{ $translations->links() }}</div>
                @endif
            </x-admin.settings-card>
        </div>

        {{-- ============================================================ --}}
        {{-- 8. EMAIL TEMPLATES                                             --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'email_templates'" x-cloak class="grid gap-5">
            <x-admin.settings-card :title="__('Email-шаблони')" :description="__('Тексти автоматичних листів за кожним типом події.')">
                <form
                    method="POST"
                    action="{{ route('admin.email-templates.store') }}"
                    class="grid gap-3 rounded-2xl border border-white/10 bg-zinc-950/40 p-4"
                    x-data="{!! \Illuminate\Support\Js::from([
                        'id' => '',
                        'emailTplKey' => 'registration',
                        'locale' => 'uk',
                        'subject' => '',
                        'body' => '',
                        'is_active' => true,
                        'placeholders' => $emailPlaceholderMap,
                    ]) !!}"
                    @edit-email.window="id = $event.detail.id; emailTplKey = $event.detail.key; locale = $event.detail.locale; subject = $event.detail.subject; body = $event.detail.body; is_active = !!$event.detail.is_active; $el.scrollIntoView({behavior:'smooth', block:'center'})"
                >
                    @csrf
                    <input type="hidden" name="id" x-model="id">
                    <div class="grid gap-3 sm:grid-cols-[1fr_140px]">
                        <x-admin.field as="select" name="key" :label="__('Тип події')" x-model="emailTplKey" required>
                            @foreach($emailTypes as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </x-admin.field>
                        <x-admin.field as="select" name="locale" :label="__('Мова')" x-model="locale">
                            <option value="uk">UK</option>
                            <option value="en">EN</option>
                        </x-admin.field>
                    </div>
                    <x-admin.field name="subject" :label="__('Тема листа')" x-model="subject" required placeholder="{{ __('Ласкаво просимо до 3Dify') }}" />
                    <x-admin.field as="textarea" rows="8" name="body" :label="__('Тіло листа')" x-model="body" required :placeholder="__('Привіт, ім\'я! Текст листа...')" />

                    <div class="rounded-xl border border-sky-300/20 bg-sky-300/[0.06] p-3 text-xs leading-5 text-sky-100">
                        <p class="font-semibold">{{ __('Доступні змінні для обраного типу події') }}</p>
                        <p class="mt-2 font-mono text-[11px] leading-relaxed text-sky-50/95 break-words" x-text="(placeholders[emailTplKey] || []).join(' · ')"></p>
                        <p class="mt-2 text-[11px] text-sky-200/80">{{ __('Приклад') }}: <code class="font-mono text-sky-50">@verbatim{{ order.number }}@endverbatim</code>, @verbatim{{link}}@endverbatim.</p>
                    </div>

                    <details class="rounded-xl border border-white/10 bg-zinc-950/40 p-3 text-xs text-zinc-300">
                        <summary class="cursor-pointer font-semibold text-zinc-200">{{ __('Усі змінні за типом події (повний довідник)') }}</summary>
                        <dl class="mt-3 grid gap-3">
                            @foreach($emailPlaceholderMap as $phKey => $vars)
                                <div>
                                    <dt class="text-emerald-200/90">{{ $emailTypes[$phKey] ?? $phKey }}</dt>
                                    <dd class="mt-1 font-mono text-[10px] leading-relaxed text-zinc-400 break-words">{{ implode(' · ', $vars) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </details>

                    <div class="flex items-center justify-between gap-3 pt-1">
                        <label class="inline-flex items-center gap-2 text-xs text-zinc-300">
                            <input type="checkbox" name="is_active" value="1" x-model="is_active" class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                            {{ __('Активний') }}
                        </label>
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">
                            <span x-text="id ? '{{ __('Оновити') }}' : '{{ __('Зберегти') }}'">{{ __('Зберегти') }}</span>
                        </button>
                    </div>
                </form>

                <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-950/40">
                            <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                                <th class="px-4 py-3">{{ __('Тип') }}</th>
                                <th class="px-4 py-3">{{ __('Мова') }}</th>
                                <th class="px-4 py-3">{{ __('Тема') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Активний') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Дії') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($emailTemplates as $tpl)
                                <tr class="transition hover:bg-white/[0.02]">
                                    <td class="px-4 py-2.5">
                                        <span class="rounded-lg bg-emerald-300/10 px-2 py-0.5 text-[11px] font-bold text-emerald-200">{{ $emailTypes[$tpl->key] ?? $tpl->key }}</span>
                                    </td>
                                    <td class="px-4 py-2.5"><span class="rounded-full bg-white/[0.06] px-2 py-0.5 text-[10px] font-bold uppercase text-zinc-200">{{ $tpl->locale }}</span></td>
                                    <td class="px-4 py-2.5 text-sm text-white"><span class="line-clamp-1">{{ $tpl->subject }}</span></td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if($tpl->is_active)
                                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-emerald-400/30"></span>
                                        @else
                                            <span class="inline-flex h-2 w-2 rounded-full bg-zinc-600"></span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" x-data @click="$dispatch('edit-email', { id: {{ $tpl->id }}, key: '{{ $tpl->key }}', locale: '{{ $tpl->locale }}', subject: @js($tpl->subject), body: @js($tpl->body), is_active: {{ $tpl->is_active ? 'true' : 'false' }} })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('admin.email-templates.destroy', $tpl) }}" onsubmit="return confirm('{{ __('Видалити шаблон?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-xs text-zinc-500">{{ __('Шаблонів ще немає.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.settings-card>
        </div>

        {{-- ============================================================ --}}
        {{-- 8.5 СТОРІНКИ ФУТЕРА (CMS)                                    --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'pages'" x-cloak class="grid gap-5">
            <x-admin.settings-card
                :title="__('Сторінки футера (CMS)')"
                :description="__('Редагуйте контент сторінок «Правила публікації», «Авторські права», «Terms», «Контакти», «FAQ», «Privacy». Кожна сторінка має дві локалі — UK та EN. На публічному сайті сторінки доступні за адресою /page/{slug}.')"
            >
                @php
                    $pagesBySlug = $legalPages->groupBy('slug');
                    $existingSlugs = $pagesBySlug->keys()->all();
                    $missingSlugs = collect($legalSlugs)
                        ->reject(fn ($s) => in_array($s['slug'], $existingSlugs, true))
                        ->values();
                @endphp

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-zinc-400">
                        {{ __('Усього сторінок') }}:
                        <span class="font-bold text-emerald-300">{{ $legalPages->count() }}</span>
                    </div>
                    <a href="{{ route('admin.pages.create') }}"
                       class="inline-flex h-9 items-center gap-2 rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        {{ __('Додати сторінку') }}
                    </a>
                </div>

                @if($missingSlugs->isNotEmpty())
                    <div class="mb-4 rounded-2xl border border-amber-300/30 bg-amber-300/[0.08] p-4">
                        <p class="text-sm font-semibold text-amber-100">{{ __('Не вистачає сторінок') }}</p>
                        <p class="mt-1 text-xs text-amber-200/80">
                            {{ __('Запустіть') }} <code class="rounded bg-zinc-950/40 px-1.5 py-0.5 text-[11px]">php artisan db:seed --class=LegalPagesSeeder</code> {{ __('або створіть вручну:') }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($missingSlugs as $missing)
                                <a href="{{ route('admin.pages.create', ['slug' => $missing['slug']]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300/30 bg-amber-300/10 px-2.5 py-1 text-[11px] font-semibold text-amber-100 hover:bg-amber-300/20">
                                    + {{ $missing['label_uk'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="overflow-hidden rounded-2xl border border-white/10">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-950/40">
                            <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                                <th class="px-4 py-3">Slug</th>
                                <th class="px-4 py-3">{{ __('Назва') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Мова') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Статус') }}</th>
                                <th class="px-4 py-3">{{ __('Оновлено') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Дії') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($legalPages as $lp)
                                <tr class="transition hover:bg-white/[0.03]">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('pages.show', $lp->slug) }}" target="_blank" class="font-mono text-xs text-emerald-300 hover:underline">/{{ $lp->slug }}</a>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="text-sm text-white">{{ $lp->title }}</div>
                                        @if($lp->subtitle)
                                            <div class="mt-0.5 line-clamp-1 text-[11px] text-zinc-500">{{ $lp->subtitle }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="inline-flex h-6 items-center rounded-full border border-white/10 bg-white/[0.04] px-2 text-[10px] font-bold uppercase tracking-wider text-zinc-300">{{ $lp->locale }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if($lp->is_published)
                                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>{{ __('Опубліковано') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full border border-zinc-500/30 bg-zinc-500/[0.08] px-2 py-0.5 text-[10px] font-bold uppercase text-zinc-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-zinc-500"></span>{{ __('Прихована') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-400">
                                        {{ $lp->updated_at?->translatedFormat('d M Y · H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.pages.edit', $lp) }}"
                                               class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-white/10 bg-white/[0.04] px-2.5 text-xs font-semibold text-zinc-200 hover:bg-white/10">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                                {{ __('Редагувати') }}
                                            </a>
                                            <form method="POST" action="{{ route('admin.pages.toggle', $lp) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/10" title="{{ $lp->is_published ? __('Приховати') : __('Опублікувати') }}">
                                                    @if($lp->is_published)
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                                    @else
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.pages.destroy', $lp) }}"
                                                  onsubmit="return confirm('{{ __('Видалити сторінку?') }}')" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 hover:border-rose-300/30 hover:bg-rose-300/10 hover:text-rose-100" title="{{ __('Видалити') }}">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500">
                                    {{ __('Сторінок ще немає. Запустіть seeder або створіть першу.') }}
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.settings-card>
        </div>

        {{-- ============================================================ --}}
        {{-- 9. ЮРИДИЧНІ                                                   --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'legal'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="legal">
                <input type="hidden" name="tab" value="legal">

                @foreach([
                    ['key' => 'terms', 'title' => __('Terms of Service'), 'desc' => __('Умови використання сервісу.')],
                    ['key' => 'privacy', 'title' => __('Privacy Policy'), 'desc' => __('Політика конфіденційності та обробки даних.')],
                    ['key' => 'copyright', 'title' => __('Copyright'), 'desc' => __('Правила використання авторських прав.')],
                    ['key' => 'publishing_rules', 'title' => __('Правила публікації'), 'desc' => __('Що дозволено публікувати на маркетплейсі.')],
                ] as $page)
                    <x-admin.settings-card :title="$page['title']" :description="$page['desc']">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-admin.field as="textarea" rows="8" name="settings[legal.{{ $page['key'] }}_uk]" :label="__('Текст (UK)')" :value="$val('legal.'.$page['key'].'_uk')" />
                            <x-admin.field as="textarea" rows="8" name="settings[legal.{{ $page['key'] }}_en]" :label="__('Текст (EN)')" :value="$val('legal.'.$page['key'].'_en')" />
                        </div>
                    </x-admin.settings-card>
                @endforeach

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти юридичні') }}</button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- 10. СОЦМЕРЕЖІ                                                  --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'social'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="social">
                <input type="hidden" name="tab" value="social">

                <x-admin.settings-card :title="__('Соціальні мережі')" :description="__('Посилання, які зʼявляться в footer та на сторінках авторів.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach([
                            ['key' => 'x_url', 'label' => 'X / Twitter', 'placeholder' => 'https://x.com/3dify', 'tint' => 'zinc'],
                            ['key' => 'github_url', 'label' => 'GitHub', 'placeholder' => 'https://github.com/3dify', 'tint' => 'zinc'],
                            ['key' => 'telegram_url', 'label' => 'Telegram', 'placeholder' => 'https://t.me/3dify', 'tint' => 'sky'],
                            ['key' => 'instagram_url', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/3dify', 'tint' => 'rose'],
                            ['key' => 'youtube_url', 'label' => 'YouTube', 'placeholder' => 'https://youtube.com/@3dify', 'tint' => 'rose'],
                            ['key' => 'discord_url', 'label' => 'Discord', 'placeholder' => 'https://discord.gg/3dify', 'tint' => 'violet'],
                        ] as $sm)
                            <x-admin.field type="url" name="settings[social.{{ $sm['key'] }}]" :label="$sm['label']" :value="$val('social.'.$sm['key'])" :placeholder="$sm['placeholder']" />
                        @endforeach
                    </div>
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти соцмережі') }}</button>
                </div>
            </form>
        </div>

        {{-- ============================================================ --}}
        {{-- 11. HEADER / FOOTER                                            --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'header_footer'" x-cloak>
            <form method="POST" action="{{ route('admin.settings.bulk') }}" class="grid gap-5">
                @csrf
                <input type="hidden" name="group" value="layout">
                <input type="hidden" name="tab" value="header_footer">

                <x-admin.settings-card :title="__('Header')" :description="__('Налаштування шапки сайту.')">
                    <div class="grid gap-3">
                        <x-admin.toggle name="settings[header.language_selector_enabled]" :label="__('Перемикач мови')" :description="__('Показувати кнопку UK/EN у шапці.')" :checked="$bool('header.language_selector_enabled', true)" />
                        <x-admin.toggle name="settings[header.search_enabled]" :label="__('Пошук у шапці')" :description="__('Показувати search input.')" :checked="$bool('header.search_enabled', true)" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Footer — текст')" :description="__('Опис компанії та copyright.')">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.field as="textarea" rows="3" name="settings[footer.description_uk]" :label="__('Опис (UK)')" :value="$val('footer.description_uk')" />
                        <x-admin.field as="textarea" rows="3" name="settings[footer.description_en]" :label="__('Опис (EN)')" :value="$val('footer.description_en')" />
                        <x-admin.field name="settings[footer.copyright_text_uk]" :label="__('Copyright (UK)')" :value="$val('footer.copyright_text_uk')" placeholder="© 2026 3Dify" />
                        <x-admin.field name="settings[footer.copyright_text_en]" :label="__('Copyright (EN)')" :value="$val('footer.copyright_text_en')" placeholder="© 2026 3Dify" />
                    </div>
                </x-admin.settings-card>

                <x-admin.settings-card :title="__('Footer — колонки')" :description="__('JSON-опис колонок та посилань. Залишіть порожнім для дефолтних.')">
                    <x-admin.field
                        as="textarea"
                        rows="8"
                        name="settings[footer.columns]"
                        label="footer.columns"
                        :value="$val('footer.columns')"
                        :helper="__('Приклад: [{label: \'Marketplace\', links: [{text:\'Каталог\', href:\'/models\'}]}]')"
                    />
                </x-admin.settings-card>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-400 px-6 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти Header/Footer') }}</button>
                </div>
            </form>
        </div>

        </div>{{-- /content --}}
    </div>
</x-layouts.admin>
