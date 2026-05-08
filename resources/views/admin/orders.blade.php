<x-layouts.admin
    :title="__('Замовлення')"
    :description="__('Замовлення покупців із сумами, статусами та посиланнями на профілі.')"
    active="orders"
    :breadcrumbs="[['label' => __('Замовлення')]]"
>
    <x-slot:actions>
        <a href="{{ route('admin.export.orders') }}" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 hover:bg-white/10">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
    </x-slot:actions>

    <x-admin.section :padded="false">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-950/40">
                    <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                        <th class="px-5 py-3">{{ __('Номер') }}</th>
                        <th class="px-5 py-3">{{ __('Покупець') }}</th>
                        <th class="px-5 py-3">{{ __('Статус') }}</th>
                        <th class="px-5 py-3">{{ __('Сума') }}</th>
                        <th class="px-5 py-3">{{ __('Створено') }}</th>
                        <th class="px-5 py-3">{{ __('Оплачено') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($orders as $order)
                        <tr class="transition hover:bg-white/[0.02]">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-white">#{{ $order->number }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-300/15 text-[10px] font-bold text-emerald-100">
                                        {{ mb_strtoupper(mb_substr($order->user?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-white">{{ $order->user?->name ?? '—' }}</p>
                                        <p class="truncate text-xs text-zinc-500">{{ $order->user?->email ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3"><x-admin.status-pill :status="$order->status" /></td>
                            <td class="px-5 py-3 font-bold text-white">{{ number_format((float) $order->total, 2, '.', ' ') }} <span class="text-xs font-medium text-zinc-400">{{ $order->currency }}</span></td>
                            <td class="px-5 py-3 text-xs text-zinc-400">{{ $order->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-3 text-xs">
                                @if($order->paid_at)
                                    <span class="text-emerald-200">{{ $order->paid_at->format('d.m.Y H:i') }}</span>
                                @else
                                    <span class="text-zinc-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-500">{{ __('Замовлень поки немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.section>

    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.admin>
