<section>
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-300">{{ __('Публічний профіль автора') }}</p>
            <h2 class="mt-2 text-2xl font-black tracking-tight text-white">{{ __('Дані профілю') }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">
                {{ __('Оновіть імʼя, аватар, обкладинку, біографію та контактні посилання, які бачать покупці на сторінці автора.') }}
            </p>
        </div>

        @if (session('status') === 'profile-updated')
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2600)"
                class="rounded-2xl border border-emerald-300/25 bg-emerald-300/10 px-4 py-3 text-sm font-semibold text-emerald-100"
            >
                {{ __('Профіль збережено.') }}
            </div>
        @endif
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-8 space-y-8">
        @csrf
        @method('patch')

        <div class="grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
            <label class="group relative overflow-hidden rounded-3xl border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/20">
                <span class="text-sm font-bold text-white">{{ __('Обкладинка профілю') }}</span>
                <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('JPG, PNG або WebP до 4MB. Оптимальна пропорція ~ 3:1, наприклад 1500×500 px.') }}</span>
                <div class="mt-4 aspect-[3/1] overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-emerald-400/20 via-sky-400/10 to-zinc-900">
                    @if($user->coverUrl())
                        <img src="{{ $user->coverUrl() }}" alt="{{ __('Обкладинка профілю') }}" class="h-full w-full object-cover object-center">
                    @else
                        <div class="flex h-full items-center justify-center text-xs font-bold uppercase tracking-[0.18em] text-zinc-500">{{ __('Додати обкладинку') }}</div>
                    @endif
                </div>
                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="sr-only">
                <span class="mt-4 inline-flex rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-2 text-xs font-bold text-emerald-100 transition group-hover:border-emerald-300/40 group-hover:bg-emerald-300/10">{{ __('Завантажити cover') }}</span>
                <x-input-error class="mt-3" :messages="$errors->get('cover')" />
            </label>

            <label class="group rounded-3xl border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/20">
                <span class="text-sm font-bold text-white">{{ __('Аватар') }}</span>
                <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Квадратне зображення до 4MB. Email автора не показується публічно.') }}</span>
                <div class="mt-4 flex items-center gap-4">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-3xl border border-emerald-300/30 bg-emerald-300 text-2xl font-black text-zinc-950 shadow-lg shadow-emerald-500/20">
                        @if($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->displayName() }}" class="h-full w-full object-cover">
                        @else
                            {{ mb_substr($user->displayName(), 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <span class="inline-flex rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-2 text-xs font-bold text-emerald-100 transition group-hover:border-emerald-300/40 group-hover:bg-emerald-300/10">{{ __('Завантажити аватар') }}</span>
                        <p class="mt-3 text-xs leading-5 text-zinc-500">{{ __('Аватар використовується у картках моделей, профілі автора та коментарях.') }}</p>
                    </div>
                </div>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only">
                <x-input-error class="mt-3" :messages="$errors->get('avatar')" />
            </label>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <x-ui.input id="name" name="name" type="text" :label="__('Імʼя акаунта')" :value="old('name', $user->name)" :error="$errors->first('name')" required autocomplete="name" />
            <x-ui.input id="display_name" name="display_name" type="text" :label="__('Публічне імʼя')" :value="old('display_name', $user->display_name)" :error="$errors->first('display_name')" />
            <x-ui.input id="username" name="username" type="text" :label="__('Username')" :value="old('username', $user->username)" :error="$errors->first('username')" placeholder="studio-maker" />
            <x-ui.input id="email" name="email" type="email" :label="__('Email')" :value="old('email', $user->email)" :error="$errors->first('email')" required autocomplete="username" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-2xl border border-amber-300/20 bg-amber-300/10 p-4 text-sm text-amber-100">
                {{ __('Ваш email ще не підтверджено.') }}
                <button form="send-verification" class="ml-2 font-bold underline underline-offset-4 hover:text-white">
                    {{ __('Надіслати лист підтвердження ще раз') }}
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-emerald-100">{{ __('Новий лист підтвердження надіслано.') }}</p>
                @endif
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <x-ui.textarea id="bio_uk" name="bio_uk" rows="7" :label="__('Біографія українською')" :helper="__('Коротко розкажіть покупцям про себе, стиль моделей і досвід друку.')" :error="$errors->first('bio_uk')">{{ old('bio_uk', $user->bio_uk) }}</x-ui.textarea>
            <x-ui.textarea id="bio_en" name="bio_en" rows="7" :label="__('Біографія англійською')" :helper="__('Англійська версія показується, коли користувач перемикає сайт на EN.')" :error="$errors->first('bio_en')">{{ old('bio_en', $user->bio_en) }}</x-ui.textarea>
        </div>

        <div>
            <p class="text-sm font-bold text-white">{{ __('Контакти та соцмережі') }}</p>
            @php
                $countryOptions = $countries ?? config('countries', []);
                $selectedCountry = old('country_code', $user->country_code);
                $selectedCountryMeta = $selectedCountry ? ($countryOptions[$selectedCountry] ?? null) : null;
            @endphp
            <div
                x-data="{ country: @js($selectedCountry), countries: @js($countryOptions) }"
                class="mt-4 rounded-3xl border border-white/10 bg-white/[0.035] p-4"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium text-zinc-200">
                        <span>{{ __('Країна') }}</span>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-lg" x-text="countries[country]?.flag || '🌍'">{{ $selectedCountryMeta['flag'] ?? '🌍' }}</span>
                            <select name="country_code" x-model="country" class="h-13 w-full appearance-none rounded-2xl border border-white/10 bg-zinc-950/80 py-3 pl-12 pr-11 text-white shadow-inner shadow-black/20 transition focus:border-emerald-300 focus:ring-emerald-300">
                                <option value="">{{ __('Оберіть країну') }}</option>
                                @foreach($countryOptions as $code => $country)
                                    <option value="{{ $code }}">{{ $country[app()->getLocale()] ?? $country['en'] }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        @if($errors->first('country_code'))
                            <span class="text-xs text-red-300">{{ $errors->first('country_code') }}</span>
                        @endif
                    </label>

                    <x-ui.input name="city" type="text" :label="__('Місто')" :value="old('city', $user->city)" :error="$errors->first('city')" placeholder="Kyiv" class="h-13" />
                </div>
                <p class="mt-3 text-xs leading-5 text-zinc-500">{{ __('Країна буде показана на сторінці автора з прапором.') }}</p>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <x-ui.input name="website_url" type="url" :label="__('Website')" :value="old('website_url', $user->website_url)" :error="$errors->first('website_url')" placeholder="https://example.com" />
                <x-ui.input name="telegram_url" type="url" :label="__('Telegram')" :value="old('telegram_url', $user->telegram_url)" :error="$errors->first('telegram_url')" placeholder="https://t.me/username" />
                <x-ui.input name="instagram_url" type="url" :label="__('Instagram')" :value="old('instagram_url', $user->instagram_url)" :error="$errors->first('instagram_url')" placeholder="https://instagram.com/username" />
                <x-ui.input name="youtube_url" type="url" :label="__('YouTube')" :value="old('youtube_url', $user->youtube_url)" :error="$errors->first('youtube_url')" placeholder="https://youtube.com/@channel" />
                <x-ui.input name="github_url" type="url" :label="__('GitHub')" :value="old('github_url', $user->github_url)" :error="$errors->first('github_url')" placeholder="https://github.com/username" />
                <x-ui.input name="twitter_url" type="url" :label="__('X / Twitter')" :value="old('twitter_url', $user->twitter_url)" :error="$errors->first('twitter_url')" placeholder="https://x.com/username" />
            </div>
        </div>

        <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 text-sm text-zinc-300">
            <input type="checkbox" name="contact_enabled" value="1" @checked(old('contact_enabled', $user->contact_enabled ?? true)) class="mt-1 rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
            <span>
                <span class="block font-bold text-white">{{ __('Дозволити покупцям надсилати контактні повідомлення') }}</span>
                <span class="mt-1 block text-xs leading-5 text-zinc-500">{{ __('Email не показується публічно. Повідомлення надсилаються через форму 3Dify.') }}</span>
            </span>
        </label>

        <div class="flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.button type="submit">{{ __('Зберегти профіль') }}</x-ui.button>
            <a href="{{ route('authors.show', $user->username ?: $user->id) }}" class="text-sm font-bold text-emerald-200 hover:text-white">
                {{ __('Переглянути публічний профіль') }} →
            </a>
        </div>
    </form>
</section>
