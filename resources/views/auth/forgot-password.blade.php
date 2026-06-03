<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Відновлення') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Відновити пароль') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Вкажіть email, і ми надішлемо посилання для зміни пароля.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="grid gap-5">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-2" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">{{ __('Надіслати посилання') }}</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-400">
        <a href="{{ route('login') }}" class="font-semibold text-emerald-200 hover:text-emerald-100">{{ __('Повернутися до входу') }}</a>
    </p>
</x-guest-layout>
