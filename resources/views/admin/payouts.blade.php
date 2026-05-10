<x-layouts.admin
    :title="__('Виплати авторам')"
    :description="__('Черга заявок на виплату гонорарів. Перевіряйте реквізити перед позначенням «paid».')"
    breadcrumb-current="{{ __('Виплати') }}"
>
    <x-slot:actions>
        <a href="{{ route('admin.export.payouts') }}" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 hover:bg-white/10">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
    </x-slot:actions>

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-amber-300/30 bg-amber-300/[0.05] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-300">{{ __('Очікують') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $counts['pending'] }}</p>
            <p class="mt-1 text-xs text-amber-200/80">{{ number_format($totals['pending'], 2) }} UAH</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Затверджено') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $counts['approved'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.05] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Виплачено') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $counts['paid'] }}</p>
            <p class="mt-1 text-xs text-emerald-200/80">{{ number_format($totals['paid'], 2) }} UAH</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Відхилено') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $counts['rejected'] }}</p>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach(['all' => __('Усі'), 'pending' => __('Очікують'), 'approved' => __('Затверджені'), 'paid' => __('Виплачені'), 'rejected' => __('Відхилені')] as $key => $label)
            <a href="{{ route('admin.payouts', ['status' => $key]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $status === $key ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.section :title="__('Заявки')" :description="__('Найновіші зверху.')">
        @if($payouts->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-950/40">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                            <th class="px-5 py-3">{{ __('Автор') }}</th>
                            <th class="px-5 py-3">{{ __('Сума') }}</th>
                            <th class="px-5 py-3">{{ __('Метод') }}</th>
                            <th class="px-5 py-3">{{ __('Реквізити') }}</th>
                            <th class="px-5 py-3">{{ __('Створено') }}</th>
                            <th class="px-5 py-3">{{ __('Статус') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('Дії') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($payouts as $p)
                            <tr class="align-top transition hover:bg-white/[0.02]">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-300/20 text-xs font-black text-emerald-100">{{ mb_strtoupper(mb_substr($p->author->name, 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <a href="{{ $p->author->profileUrl() }}" class="block truncate text-sm font-semibold text-white hover:text-emerald-200">{{ $p->author->name }}</a>
                                            <p class="truncate text-xs text-zinc-500">{{ $p->author->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 font-bold text-white">{{ number_format((float) $p->amount, 2) }} {{ $p->currency }}</td>
                                <td class="px-5 py-3 text-xs text-zinc-300">{{ __(\App\Models\Payout::METHODS[$p->method] ?? $p->method) }}</td>
                                <td class="px-5 py-3">
                                    <pre class="max-w-xs whitespace-pre-wrap break-words font-mono text-[11px] leading-5 text-zinc-300">{{ $p->details }}</pre>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-400">{{ $p->requested_at?->format('d.m.Y H:i') }}</td>
                                <td class="px-5 py-3">
                                    <x-ui.status :status="$p->status" />
                                </td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('admin.payouts.update', $p) }}" class="flex items-center justify-end gap-1.5">
                                        @csrf @method('PATCH')
                                        <select name="status" class="h-8 rounded-lg border border-white/10 bg-zinc-950/60 px-2 text-xs text-white">
                                            @foreach(\App\Models\Payout::STATUSES as $s)
                                                <option value="{{ $s }}" @selected($p->status === $s)>{{ __($s) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="admin_notes" value="{{ $p->admin_notes }}" placeholder="{{ __('Нотатки') }}" class="h-8 w-32 rounded-lg border border-white/10 bg-zinc-950/60 px-2 text-xs text-white placeholder:text-zinc-500">
                                        <button class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-400 text-zinc-950 hover:bg-emerald-300" title="{{ __('Зберегти') }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $payouts->links() }}</div>
        @else
            <p class="py-8 text-center text-sm text-zinc-500">{{ __('Заявок не знайдено.') }}</p>
        @endif
    </x-admin.section>
</x-layouts.admin>
