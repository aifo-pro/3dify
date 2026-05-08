<x-guest-layout>
    <div class="mb-8">
        <x-ui.badge>{{ __('Двофакторна автентифікація') }}</x-ui.badge>
        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">{{ __('Введіть код') }}</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Відкрийте Authenticator-додаток і введіть 6-значний код. Якщо ви втратили доступ — використайте резервний код.') }}</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-2 text-xs text-rose-200">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.challenge.submit') }}" class="grid gap-4" x-data="{ recovery: false }">
        @csrf
        <div x-show="!recovery">
            <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Код з додатка') }}</label>
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="8" pattern="[0-9 ]*" autofocus class="mt-1 h-12 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 font-mono text-2xl tracking-[0.4em] text-white focus:border-emerald-300">
        </div>
        <div x-show="recovery" x-cloak>
            <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Резервний код') }}</label>
            <input type="text" name="recovery_code" placeholder="abc12-de345" class="mt-1 h-12 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 font-mono text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300">
        </div>
        <button type="button" @click="recovery = !recovery" class="text-left text-xs font-bold text-emerald-200 hover:text-emerald-100">
            <span x-show="!recovery">{{ __('Втратили доступ до додатка? Ввести резервний код →') }}</span>
            <span x-show="recovery" x-cloak>{{ __('← Повернутися до коду з додатка') }}</span>
        </button>
        <button class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-400 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Підтвердити') }}</button>
    </form>
</x-guest-layout>
