<x-layouts.admin
    :title="__('Повернення коштів')"
    :description="__('Заявки користувачів на повернення. Після підтвердження кошти зараховуються покупцю на баланс 3Dify, а доступ до файлів закривається.')"
    breadcrumb-current="{{ __('Refunds') }}"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach(['' => __('Усі'), 'pending' => __('Очікують'), 'approved' => __('Старі підтверджені'), 'rejected' => __('Відхилені'), 'refunded' => __('Повернуто')] as $key => $label)
            <a href="{{ route('admin.refunds', $key ? ['status' => $key] : []) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $status === $key ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key === '' ? 'all' : $key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.section :title="__('Заявки')" :description="__('Нові заявки зверху. Статус “Повернуто” одразу блокує скачування та додає суму на баланс покупця.')">
        <div class="overflow-hidden rounded-2xl border border-white/10">
            <table class="min-w-full divide-y divide-white/5 text-sm">
                <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Замовлення') }}</th>
                        <th class="px-4 py-3">{{ __('Користувач') }}</th>
                        <th class="px-4 py-3">{{ __('Причина') }}</th>
                        <th class="px-4 py-3">{{ __('Статус') }}</th>
                        <th class="px-4 py-3">{{ __('Дата') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Дії') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($requests as $r)
                        @php
                            $order = $r->order;
                            $amount = (float) ($order?->items?->sum('price') ?: $order?->total ?: 0);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-mono text-xs text-white">{{ $order?->number }}</p>
                                <p class="text-xs text-zinc-500">{{ number_format($amount, 2) }} {{ $order?->currency ?? 'UAH' }}</p>
                                @foreach($order?->items ?? [] as $item)
                                    <p class="mt-0.5 max-w-[260px] truncate text-xs text-zinc-400">— {{ $item->product?->localized('title') ?? $item->product?->title }}</p>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 align-top text-zinc-200">
                                {{ $r->user->name ?? '—' }}
                                <p class="text-xs text-zinc-500">{{ $r->user->email ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <p class="text-zinc-200">{{ __($reasons[$r->reason] ?? $r->reason) }}</p>
                                @if($r->message)
                                    <p class="mt-1 max-w-md text-xs text-zinc-500 line-clamp-3">{{ $r->message }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <x-ui.status :status="$r->status" :label="$r->status === 'approved' ? __('Потрібно завершити') : null" />
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-400">{{ $r->created_at->translatedFormat('d M Y · H:i') }}</td>
                            <td class="px-4 py-3 align-top">
                                <form method="POST" action="{{ route('admin.refunds.update', $r) }}" class="grid gap-1.5 sm:w-72">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="h-8 rounded-lg border border-white/10 bg-zinc-950/60 px-2 text-xs text-white">
                                        <option value="pending" @selected($r->status === 'pending')>{{ __('Очікує') }}</option>
                                        <option value="refunded" @selected(in_array($r->status, ['approved', 'refunded'], true))>{{ __('Повернути на баланс') }}</option>
                                        <option value="rejected" @selected($r->status === 'rejected')>{{ __('Відхилити') }}</option>
                                    </select>
                                    <input name="admin_notes" value="{{ $r->admin_notes }}" placeholder="{{ __('Нотатка для користувача') }}" class="h-8 rounded-lg border border-white/10 bg-zinc-950/60 px-2 text-xs text-white">
                                    <button class="h-8 rounded-lg bg-emerald-400 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Зберегти') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Заявок немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $requests->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
