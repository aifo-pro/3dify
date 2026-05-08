<x-layouts.marketplace>
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Hero --}}
        <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <x-ui.badge>{{ __('Безпека') }}</x-ui.badge>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('Двофакторна автентифікація') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('Додатковий шар захисту: при вході знадобиться 6-значний код з вашого телефону (Google Authenticator, 1Password, Authy тощо).') }}</p>
            </div>

            @if($confirmed)
                <x-ui.status status="active" :label="__('2FA увімкнено')" size="md" class="shrink-0" />
            @else
                <x-ui.status status="unverified" :label="__('Не налаштовано')" size="md" class="shrink-0" />
            @endif
        </header>

        @if(session('status'))
            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(! $confirmed)
            {{-- Setup card --}}
            <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20">
                <div class="border-b border-white/5 bg-gradient-to-br from-emerald-300/[0.06] to-transparent px-6 py-5">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                        <svg class="h-5 w-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        {{ __('Налаштувати 2FA за 3 кроки') }}
                    </h2>
                </div>

                <div class="grid gap-8 p-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:gap-10 lg:p-8">
                    {{-- Steps + form (left) --}}
                    <div class="min-w-0">
                        <ol class="space-y-4">
                            <li class="flex gap-3">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-300/[0.15] text-xs font-black text-emerald-200">1</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-white">{{ __('Встановіть Authenticator-додаток') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach([
                                            ['name' => 'Google Authenticator', 'icon' => 'G'],
                                            ['name' => '1Password', 'icon' => '1P'],
                                            ['name' => 'Authy', 'icon' => 'A'],
                                            ['name' => 'Microsoft Authenticator', 'icon' => 'MS'],
                                        ] as $app)
                                            <span class="inline-flex h-7 items-center gap-1.5 rounded-full border border-white/10 bg-zinc-950/60 pl-1 pr-2.5 text-[11px] text-zinc-300">
                                                <span class="grid h-5 w-5 place-items-center rounded-full bg-white/[0.08] text-[9px] font-black text-emerald-200">{{ $app['icon'] }}</span>
                                                {{ $app['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </li>

                            <li class="flex gap-3">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-300/[0.15] text-xs font-black text-emerald-200">2</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-white">{{ __('Відскануйте QR-код') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ __('Або введіть ключ вручну, якщо камера недоступна:') }}</p>

                                    <div x-data="{ key: @js($manual), copied: false, copy() { navigator.clipboard.writeText(this.key.replace(/\s/g, '')); this.copied = true; setTimeout(() => this.copied = false, 1600); } }" class="mt-2.5 flex items-stretch gap-2">
                                        <code class="min-w-0 flex-1 truncate rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 font-mono text-[11px] tracking-wider text-emerald-200" x-text="key"></code>
                                        <button type="button" @click="copy()" class="inline-flex h-auto shrink-0 items-center gap-1.5 rounded-xl border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 transition hover:bg-white/[0.10]" :class="copied && 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-200'">
                                            <template x-if="!copied">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                    {{ __('Копіювати') }}
                                                </span>
                                            </template>
                                            <template x-if="copied">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    {{ __('Скопійовано') }}
                                                </span>
                                            </template>
                                        </button>
                                    </div>
                                </div>
                            </li>

                            <li class="flex gap-3">
                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-emerald-300/[0.15] text-xs font-black text-emerald-200">3</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-white">{{ __('Введіть 6-значний код з додатка') }}</p>

                                    <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-3">
                                        @csrf
                                        <div class="flex flex-wrap items-center gap-3">
                                            <input
                                                type="text"
                                                name="code"
                                                inputmode="numeric"
                                                autocomplete="one-time-code"
                                                maxlength="7"
                                                pattern="[0-9 ]*"
                                                placeholder="000 000"
                                                required
                                                autofocus
                                                class="h-12 w-44 shrink-0 rounded-xl border border-white/10 bg-zinc-950/60 px-4 text-center font-mono text-xl tracking-[0.4em] text-white placeholder:text-zinc-600 focus:border-emerald-300 focus:ring-2 focus:ring-emerald-300/30"
                                            >
                                            <button class="inline-flex h-12 items-center gap-2 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                                {{ __('Увімкнути 2FA') }}
                                            </button>
                                        </div>
                                        @error('code')
                                            <p class="mt-2 inline-flex items-center gap-1.5 text-xs text-rose-300">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </form>
                                </div>
                            </li>
                        </ol>
                    </div>

                    {{-- QR (right) --}}
                    <div class="order-first flex justify-center lg:order-last lg:justify-start">
                        <div class="w-full max-w-[260px]">
                            <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-white p-3 shadow-2xl shadow-emerald-500/10">
                                <div class="aspect-square w-full [&>svg]:block [&>svg]:h-auto [&>svg]:w-full">
                                    {!! $qr !!}
                                </div>
                            </div>
                            <p class="mt-3 text-center text-[11px] leading-5 text-zinc-500">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/></svg>
                                    {{ __('Наведіть камеру на QR') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tips card --}}
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-300/[0.10] text-emerald-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <p class="mt-3 text-xs font-bold text-white">{{ __('Коди оновлюються кожні 30 секунд') }}</p>
                    <p class="mt-1 text-[11px] leading-5 text-zinc-500">{{ __('Введіть свіжий код, якщо попередній не спрацював.') }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-sky-300/[0.10] text-sky-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <p class="mt-3 text-xs font-bold text-white">{{ __('Без 2FA акаунт уразливіший') }}</p>
                    <p class="mt-1 text-[11px] leading-5 text-zinc-500">{{ __('Особливо важливо для авторів з виплатами та продажами.') }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <div class="grid h-8 w-8 place-items-center rounded-lg bg-amber-300/[0.10] text-amber-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <p class="mt-3 text-xs font-bold text-white">{{ __('Резервні коди') }}</p>
                    <p class="mt-1 text-[11px] leading-5 text-zinc-500">{{ __('Після увімкнення ми покажемо 8 одноразових кодів — збережіть їх.') }}</p>
                </div>
            </div>
        @else
            {{-- Confirmed --}}
            <div class="space-y-6">
                <div class="overflow-hidden rounded-3xl border border-emerald-300/30 bg-gradient-to-br from-emerald-300/[0.10] to-emerald-300/[0.02] p-6">
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-300/[0.20] text-emerald-100">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-white">{{ __('2FA увімкнено') }}</h2>
                            <p class="mt-1 text-sm text-emerald-200">{{ __('При наступному вході система запитає код з додатка.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Recovery codes --}}
                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                                <svg class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/></svg>
                                {{ __('Резервні коди') }}
                            </h3>
                            <p class="mt-1 max-w-prose text-xs leading-5 text-zinc-400">{{ __('Кожен код одноразовий. Зберігайте їх у менеджері паролів — увійдете без Authenticator, якщо втратите телефон.') }}</p>
                        </div>
                        <div x-data="{ copied: false, copy() { const codes = @js(implode(\"\\n\", $recoveryCodes)); navigator.clipboard.writeText(codes); this.copied = true; setTimeout(() => this.copied = false, 1600); } }">
                            <button type="button" @click="copy()" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 transition hover:bg-white/[0.10]" :class="copied && 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-200'">
                                <template x-if="!copied">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        {{ __('Скопіювати усі') }}
                                    </span>
                                </template>
                                <template x-if="copied">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        {{ __('Готово') }}
                                    </span>
                                </template>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 md:grid-cols-4">
                        @foreach($recoveryCodes as $code)
                            <code class="flex items-center justify-center rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-center font-mono text-xs tracking-widest text-emerald-200">{{ $code }}</code>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('two-factor.recovery') }}" class="mt-4">
                        @csrf
                        <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-white hover:bg-white/[0.10]">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            {{ __('Згенерувати нові коди') }}
                        </button>
                    </form>
                </div>

                {{-- Disable --}}
                <div class="rounded-3xl border border-rose-300/20 bg-rose-300/[0.03] p-6">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-rose-200">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        {{ __('Вимкнути 2FA') }}
                    </h3>
                    <p class="mt-1 text-xs leading-5 text-rose-200/70">{{ __('Це знизить безпеку вашого акаунту. Підтвердіть паролем, щоб продовжити.') }}</p>
                    <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-3 flex flex-wrap items-center gap-2">
                        @csrf @method('DELETE')
                        <input type="password" name="password" required placeholder="{{ __('Поточний пароль') }}" class="h-10 w-full max-w-xs rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-rose-300 focus:ring-1 focus:ring-rose-300/40">
                        <button class="inline-flex h-10 items-center rounded-xl border border-rose-300/30 bg-rose-300/[0.06] px-4 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('Вимкнути') }}</button>
                    </form>
                    @error('password')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif
    </section>
</x-layouts.marketplace>
