<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Безпека') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Підтвердіть пароль') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Це захищена дія. Введіть пароль, щоб продовжити.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="grid gap-5">
        @csrf
        <div>
            <x-input-label for="password" :value="__('Пароль')" />
            <x-text-input id="password" class="mt-2" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">{{ __('Підтвердити') }}</x-primary-button>
    </form>
</x-guest-layout>
