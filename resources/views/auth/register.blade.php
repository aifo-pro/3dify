<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Реєстрація') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Створіть акаунт автора або покупця') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Почніть купувати моделі або завантажте власний перший файл у каталог.') }}</p>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-2xl border border-rose-300/20 bg-rose-300/10 px-4 py-3 text-sm font-semibold text-rose-100">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="grid gap-5">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Імʼя')" />
            <x-text-input id="name" class="mt-2" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div
            x-data="{
                pwd: '',
                show: false,
                get checks() {
                    return {
                        length: this.pwd.length >= 8,
                        lower: /[a-zа-яё]/.test(this.pwd),
                        upper: /[A-ZА-ЯЁ]/.test(this.pwd),
                        number: /[0-9]/.test(this.pwd),
                        symbol: /[^A-Za-zА-Яа-яЁё0-9]/.test(this.pwd),
                    };
                },
                get score() {
                    const c = this.checks;
                    let s = (c.length ? 1 : 0) + (c.lower && c.upper ? 1 : 0) + (c.number ? 1 : 0) + (c.symbol ? 1 : 0);
                    if (this.pwd.length >= 12 && s >= 3) s = 4;
                    return s;
                },
                get label() {
                    return ['', @js(__('Слабкий')), @js(__('Середній')), @js(__('Добрий')), @js(__('Надійний'))][this.score] || '';
                }
            }"
        >
            <x-input-label for="password" :value="__('Пароль')" />
            <div class="relative mt-2">
                <x-text-input id="password" class="w-full pr-12" type="password" name="password" required autocomplete="new-password"
                    x-model="pwd"
                    x-ref="pwd"
                    @input="$refs.pwd.type = show ? 'text' : 'password'" />
                <button type="button" tabindex="-1"
                    @click="show = !show; $refs.pwd.type = show ? 'text' : 'password'"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 transition hover:text-zinc-300"
                    :aria-label="show ? @js(__('Сховати пароль')) : @js(__('Показати пароль'))">
                    <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>

            {{-- Strength meter --}}
            <div x-show="pwd.length > 0" x-cloak class="mt-3">
                <div class="flex gap-1.5">
                    <template x-for="i in 4" :key="i">
                        <div class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                            :class="i <= score
                                ? (score <= 1 ? 'bg-rose-400' : score === 2 ? 'bg-amber-400' : score === 3 ? 'bg-lime-400' : 'bg-emerald-400')
                                : 'bg-white/10'"></div>
                    </template>
                </div>
                <p class="mt-1.5 text-xs font-semibold"
                    :class="score <= 1 ? 'text-rose-300' : score === 2 ? 'text-amber-300' : score === 3 ? 'text-lime-300' : 'text-emerald-300'"
                    x-text="label"></p>
            </div>

            {{-- Requirements checklist --}}
            <ul class="mt-3 grid gap-1.5 text-xs">
                <template x-for="req in [
                    { key: 'length', label: @js(__('Мінімум 8 символів')) },
                    { key: 'upper',  label: @js(__('Велика літера (A–Z)')) },
                    { key: 'lower',  label: @js(__('Мала літера (a–z)')) },
                    { key: 'number', label: @js(__('Цифра (0–9)')) },
                    { key: 'symbol', label: @js(__('Спецсимвол (!@#$…)')) },
                ]" :key="req.key">
                    <li class="flex items-center gap-2 transition-colors"
                        :class="checks[req.key] ? 'text-emerald-300' : 'text-zinc-500'">
                        <svg x-show="checks[req.key]" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        <svg x-show="!checks[req.key]" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/></svg>
                        <span x-text="req.label"></span>
                    </li>
                </template>
            </ul>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div x-data="{ pwd: '', confirm: '' }" x-init="
            $watch('confirm', () => {});
            const main = document.getElementById('password');
            main && main.addEventListener('input', e => pwd = e.target.value);
        ">
            <x-input-label for="password_confirmation" :value="__('Підтвердження пароля')" />
            <x-text-input id="password_confirmation" class="mt-2 w-full" type="password" name="password_confirmation" required autocomplete="new-password"
                x-model="confirm" />
            <p x-show="confirm.length > 0 && confirm !== pwd" x-cloak class="mt-1.5 text-xs font-semibold text-rose-300">{{ __('Паролі не співпадають') }}</p>
            <p x-show="confirm.length > 0 && confirm === pwd" x-cloak class="mt-1.5 text-xs font-semibold text-emerald-300">{{ __('Паролі співпадають') }}</p>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">{{ __('Зареєструватися') }}</x-primary-button>
    </form>

    <div class="mt-6 grid gap-3">
        <a href="{{ route('auth.github.redirect') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.08] px-4 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">GitHub login</a>
        <x-auth.telegram-login />
    </div>

    <p class="mt-6 text-center text-sm text-zinc-400">
        {{ __('Вже маєте акаунт?') }}
        <a href="{{ route('login') }}" class="font-semibold text-emerald-200 hover:text-emerald-100">{{ __('Увійти') }}</a>
    </p>
</x-guest-layout>
