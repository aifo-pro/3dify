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

        <div>
            <x-input-label for="password" :value="__('Пароль')" />
            <x-text-input id="password" class="mt-2" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Підтвердження пароля')" />
            <x-text-input id="password_confirmation" class="mt-2" type="password" name="password_confirmation" required autocomplete="new-password" />
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
