<x-layouts.marketplace>
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8">
            <x-ui.badge>{{ __('Виплати') }}</x-ui.badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('Баланс автора') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-zinc-400">
                {{ __('Платформа утримує :pct% комісії з кожної покупки. Мінімальна сума виплати — :min грн. Заявки обробляються вручну адміністратором.', ['pct' => $commission, 'min' => number_format($minimum, 2)]) }}
            </p>
        </header>

        @if(session('status'))
            <div class="mb-5 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif
        @error('amount')
            <div class="mb-5 rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">{{ $message }}</div>
        @enderror

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-emerald-300/30 bg-gradient-to-br from-emerald-300/[0.10] via-emerald-300/[0.04] to-transparent p-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Доступно') }}</p>
                <p class="mt-2 text-3xl font-black text-white">{{ number_format($available, 2) }} <span class="text-sm font-bold text-emerald-200">грн</span></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Зарезервовано') }}</p>
                <p class="mt-2 text-3xl font-black text-white">{{ number_format($reserved, 2) }} <span class="text-sm font-bold text-zinc-400">грн</span></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Загалом зароблено') }}</p>
                <p class="mt-2 text-3xl font-black text-white">{{ number_format($totalEarnings, 2) }} <span class="text-sm font-bold text-zinc-400">грн</span></p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Продажів') }}</p>
                <p class="mt-2 text-3xl font-black text-white">{{ $salesCount }}</p>
            </div>
        </div>

        {{-- Payout request form --}}
        <article class="mt-8 rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
            <header class="mb-5 flex items-center gap-3 border-b border-white/5 pb-4">
                <span class="grid h-10 w-10 place-items-center rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.10] text-emerald-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                </span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Запит виплати') }}</p>
                    <h2 class="text-lg font-bold text-white">{{ __('Створити заявку') }}</h2>
                </div>
            </header>

            @if($available < $minimum)
                <div class="rounded-2xl border border-amber-300/25 bg-amber-300/[0.06] px-4 py-3 text-sm text-amber-100">
                    {{ __('Накопичіть мінімум :min грн, щоб подати заявку. Поточний доступний баланс: :avail грн.', ['min' => number_format($minimum, 2), 'avail' => number_format($available, 2)]) }}
                </div>
            @else
                <form method="POST" action="{{ route('author.payouts.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <label class="grid gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Сума, грн') }} <span class="text-rose-300">*</span></span>
                        <input type="number" name="amount" step="0.01" min="{{ $minimum }}" max="{{ $available }}" value="{{ old('amount', $available) }}" required class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                        <span class="text-[11px] text-zinc-500">{{ __('Мінімум :min, доступно :avail', ['min' => $minimum, 'avail' => number_format($available, 2)]) }}</span>
                    </label>
                    <label class="grid gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Метод') }} <span class="text-rose-300">*</span></span>
                        <select name="method" required class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                            @foreach($methods as $key => $label)
                                <option value="{{ $key }}" @selected(old('method') === $key)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1.5 sm:col-span-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Реквізити / деталі') }} <span class="text-rose-300">*</span></span>
                        <textarea name="details" rows="3" required maxlength="2000" placeholder="{{ __('IBAN, PayPal email, USDT TRC20 address тощо.') }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">{{ old('details') }}</textarea>
                        <span class="text-[11px] text-zinc-500">{{ __('Дані бачитиме лише адміністратор. Зберігаються зашифровано в базі.') }}</span>
                    </label>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            {{ __('Подати заявку') }}
                        </button>
                    </div>
                </form>
            @endif
        </article>

        {{-- History --}}
        <article class="mt-8 rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
            <header class="mb-5 flex items-center justify-between gap-3 border-b border-white/5 pb-4">
                <h2 class="text-lg font-bold text-white">{{ __('Історія заявок') }}</h2>
                <span class="text-xs text-zinc-500">{{ $history->total() }} {{ __('усього') }}</span>
            </header>

            @if($history->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                            <tr>
                                <th class="pb-3">{{ __('Дата') }}</th>
                                <th class="pb-3">{{ __('Сума') }}</th>
                                <th class="pb-3">{{ __('Метод') }}</th>
                                <th class="pb-3">{{ __('Статус') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($history as $p)
                                <tr class="text-sm">
                                    <td class="py-3 text-xs text-zinc-400">{{ $p->requested_at?->format('d.m.Y H:i') }}</td>
                                    <td class="py-3 font-bold text-white">{{ number_format((float) $p->amount, 2) }} <span class="text-xs text-zinc-500">{{ $p->currency }}</span></td>
                                    <td class="py-3 text-xs text-zinc-300">{{ __(\App\Models\Payout::METHODS[$p->method] ?? $p->method) }}</td>
                                    <td class="py-3">
                                        <x-ui.status :status="$p->status" />
                                        @if($p->admin_notes)
                                            <p class="mt-1 text-[11px] text-zinc-500">{{ $p->admin_notes }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-5">{{ $history->links() }}</div>
            @else
                <p class="text-sm text-zinc-500">{{ __('Заявок поки немає.') }}</p>
            @endif
        </article>
    </section>
</x-layouts.marketplace>
