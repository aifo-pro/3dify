<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Вхід') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Увійдіть у 3Dify') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Керуйте покупками, публікуйте моделі та відкривайте захищені завантаження.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 grid gap-3">
        <a href="{{ route('auth.github.redirect') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/[0.08] px-4 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12]">GitHub</a>
        @if(config('services.telegram.bot_username'))
            <div class="rounded-2xl border border-white/10 bg-zinc-950/70 p-3">
                <script async src="https://telegram.org/js/telegram-widget.js?22"
                    data-telegram-login="{{ config('services.telegram.bot_username') }}"
                    data-size="large"
                    data-auth-url="{{ route('auth.telegram') }}"
                    data-request-access="write"></script>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-white/10 bg-zinc-950/50 px-4 py-3 text-center text-sm text-zinc-500">{{ __('Telegram login буде доступний після налаштування бота.') }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('login') }}" class="grid gap-5">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@3dify.local" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Пароль')" />
            <x-text-input id="password" class="mt-2" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-zinc-400">
                <input id="remember_me" type="checkbox" class="rounded border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300" name="remember">
                {{ __('Запамʼятати мене') }}
            </label>
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-emerald-200 hover:text-emerald-100" href="{{ route('password.request') }}">{{ __('Забули пароль?') }}</a>
            @endif
        </div>

        <x-primary-button class="w-full">{{ __('Увійти') }}</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-400">
        {{ __('Ще немає акаунта?') }}
        <a href="{{ route('register') }}" class="font-semibold text-emerald-200 hover:text-emerald-100">{{ __('Створити акаунт') }}</a>
    </p>
</x-guest-layout>
