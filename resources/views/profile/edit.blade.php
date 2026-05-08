<x-layouts.marketplace>
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <x-ui.badge>{{ __('Профіль') }}</x-ui.badge>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-white">{{ __('Налаштування акаунта') }}</h1>
            <p class="mt-3 max-w-2xl text-zinc-400">{{ __('Оновіть особисті дані, авторський профіль, пароль і параметри безпеки в одному місці.') }}</p>
        </div>

        <div class="grid gap-6">
            <x-ui.card class="p-6 sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </x-ui.card>
            <x-ui.card class="p-6 sm:p-8">
                @include('profile.partials.update-password-form')
            </x-ui.card>
            <x-ui.card class="border-red-400/20 bg-red-400/[0.04] p-6 sm:p-8">
                @include('profile.partials.delete-user-form')
            </x-ui.card>
        </div>
    </section>
</x-layouts.marketplace>
