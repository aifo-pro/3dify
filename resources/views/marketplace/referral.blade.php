<x-layouts.marketplace seo-title="Реферальна програма · 3Dify">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        <div class="mb-8">
            <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-black uppercase tracking-widest text-emerald-300">Реферальна програма</span>
            <h1 class="mt-4 text-3xl font-black text-white">Запрошуйте друзів — заробляйте</h1>
            <p class="mt-2 text-zinc-400">За кожну першу покупку запрошеного вами користувача ви отримуєте <strong class="text-emerald-300">5%</strong> від суми на ваш баланс.</p>
        </div>

        {{-- Stats --}}
        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-zinc-900/50 p-5 text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-zinc-500">Запрошено</p>
                <p class="mt-2 text-3xl font-black text-white">{{ $referralsCount }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-400/20 bg-emerald-400/[0.06] p-5 text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-400">Зароблено</p>
                <p class="mt-2 text-3xl font-black text-white">{{ number_format((float)$totalEarned, 2) }} UAH</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-zinc-900/50 p-5 text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-zinc-500">Комісія</p>
                <p class="mt-2 text-3xl font-black text-emerald-300">5%</p>
            </div>
        </div>

        {{-- Referral link --}}
        <div class="mb-8 rounded-2xl border border-white/10 bg-zinc-900/50 p-6">
            <h2 class="mb-4 text-sm font-black uppercase tracking-widest text-zinc-400">Ваше реферальне посилання</h2>
            <div class="flex gap-2" x-data="{copied:false}">
                <input
                    id="ref-url"
                    type="text"
                    readonly
                    value="{{ $url }}"
                    class="flex-1 rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 font-mono text-sm text-zinc-300 focus:outline-none"
                >
                <button
                    @click="navigator.clipboard.writeText('{{ $url }}').then(()=>{copied=true;setTimeout(()=>copied=false,2000)})"
                    class="shrink-0 rounded-xl border border-white/10 bg-white/[0.06] px-4 py-2.5 text-sm font-bold text-zinc-300 transition hover:bg-emerald-400/10 hover:text-emerald-300"
                    :class="copied ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300' : ''"
                    x-text="copied ? '✓ Скопійовано' : 'Копіювати'"
                >Копіювати</button>
            </div>
            <p class="mt-3 text-xs text-zinc-500">Код: <code class="rounded bg-zinc-800 px-1.5 py-0.5 font-mono text-zinc-300">{{ $code }}</code></p>
        </div>

        {{-- How it works --}}
        <div class="mb-8 rounded-2xl border border-white/10 bg-zinc-900/50 p-6">
            <h2 class="mb-4 text-sm font-black uppercase tracking-widest text-zinc-400">Як це працює</h2>
            <ol class="space-y-3">
                @foreach([
                    ['1', 'Скопіюйте ваше реферальне посилання вище'],
                    ['2', 'Поділіться ним у соцмережах, чатах або на форумах 3D-друку'],
                    ['3', 'Друг реєструється за вашим посиланням і робить першу покупку'],
                    ['4', 'Ви отримуєте 5% від суми його покупки на баланс 3Dify'],
                ] as [$n, $text])
                    <li class="flex items-start gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-400/15 text-xs font-black text-emerald-300">{{ $n }}</span>
                        <span class="text-sm text-zinc-300">{{ $text }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Rewards history --}}
        @if($rewards->isNotEmpty())
            <div class="rounded-2xl border border-white/10 bg-zinc-900/50 p-6">
                <h2 class="mb-4 text-sm font-black uppercase tracking-widest text-zinc-400">Нарахування</h2>
                <div class="space-y-2">
                    @foreach($rewards as $r)
                        <div class="flex items-center justify-between rounded-xl bg-zinc-800/40 px-4 py-3 text-sm">
                            <div>
                                <span class="font-semibold text-white">Запрошений користувач #{{ $r->referred_id }}</span>
                                <span class="ml-2 text-xs text-zinc-500">{{ \Carbon\Carbon::parse($r->created_at)->translatedFormat('d M Y') }}</span>
                            </div>
                            <span class="{{ $r->status === 'credited' ? 'text-emerald-300' : 'text-zinc-500' }} font-bold">
                                +{{ number_format((float)$r->amount, 2) }} {{ $r->currency }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.marketplace>
