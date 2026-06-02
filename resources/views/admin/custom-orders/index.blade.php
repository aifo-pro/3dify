<x-layouts.admin :title="__('custom_orders.admin_title')" :description="__('Індивідуальні заявки, escrow, чати, доставка та арбітраж.')" active="custom-orders">
    <form method="GET" class="mb-5 grid gap-3 rounded-3xl border border-white/10 bg-white/[0.04] p-4 sm:grid-cols-[1fr_220px_auto]">
        <input name="q" value="{{ request('q') }}" placeholder="{{ __('Пошук за номером або назвою') }}" class="h-11 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
        <select name="status" class="h-11 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white focus:border-emerald-300">
            <option value="">{{ __('Усі статуси') }}</option>
            @foreach(\App\Models\CustomOrder::STATUSES as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('custom_orders.statuses.'.$status) }}</option>
            @endforeach
        </select>
        <button class="h-11 rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950">{{ __('Фільтр') }}</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/[0.03] text-left text-[11px] font-black uppercase tracking-[0.16em] text-zinc-500">
                    <tr>
                        <th class="px-5 py-4">{{ __('Замовлення') }}</th>
                        <th class="px-5 py-4">{{ __('Користувачі') }}</th>
                        <th class="px-5 py-4">{{ __('Сума') }}</th>
                        <th class="px-5 py-4">{{ __('Статус') }}</th>
                        <th class="px-5 py-4">{{ __('Дата') }}</th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($orders as $order)
                        <tr class="hover:bg-white/[0.03]">
                            <td class="px-5 py-4">
                                <p class="font-mono text-xs text-emerald-200">{{ $order->number }}</p>
                                <p class="mt-1 max-w-sm truncate font-bold text-white">{{ $order->title }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $order->typeLabel() }}</p>
                            </td>
                            <td class="px-5 py-4 text-zinc-300">
                                <p>{{ __('Покупець') }}: {{ $order->buyer?->displayName() }}</p>
                                <p class="mt-1">{{ __('Автор') }}: {{ $order->author?->displayName() ?: '—' }}</p>
                            </td>
                            <td class="px-5 py-4 font-black text-white">{{ $order->price ? number_format((float) $order->price, 2).' UAH' : '—' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-emerald-200">{{ $order->statusLabel() }}</span></td>
                            <td class="px-5 py-4 text-zinc-500">{{ $order->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.custom-orders.show', $order) }}" class="font-bold text-emerald-200 hover:text-white">{{ __('Відкрити') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-zinc-500">{{ __('custom_orders.no_orders') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.admin>
