<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Новий пароль') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Створіть новий пароль') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Введіть email і новий пароль для відновлення доступу до акаунта 3Dify.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="grid gap-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
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

        <x-primary-button class="w-full">{{ __('Змінити пароль') }}</x-primary-button>
    </form>
</x-guest-layout>
