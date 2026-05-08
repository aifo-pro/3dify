<x-layouts.admin
    :title="__('Платежі')"
    :description="__('Транзакції за всіма провайдерами оплат.')"
    active="payments"
    :breadcrumbs="[['label' => __('Платежі')]]"
>
    <x-slot:actions>
        <a href="{{ route('admin.export.payments') }}" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 hover:bg-white/10">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
    </x-slot:actions>

    <x-admin.section :padded="false">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-950/40">
                    <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                        <th class="px-5 py-3">{{ __('Provider') }}</th>
                        <th class="px-5 py-3">{{ __('Provider ID') }}</th>
                        <th class="px-5 py-3">{{ __('Замовлення') }}</th>
                        <th class="px-5 py-3">{{ __('Користувач') }}</th>
                        <th class="px-5 py-3">{{ __('Статус') }}</th>
                        <th class="px-5 py-3">{{ __('Сума') }}</th>
                        <th class="px-5 py-3">{{ __('Створено') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($payments as $payment)
                        <tr class="transition hover:bg-white/[0.02]">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-lg border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-200">{{ $payment->provider }}</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-zinc-300">{{ $payment->provider_payment_id ?? '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-white">#{{ $payment->order?->number ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-zinc-400">{{ $payment->order?->user?->email ?? '—' }}</td>
                            <td class="px-5 py-3"><x-admin.status-pill :status="$payment->status" /></td>
                            <td class="px-5 py-3 font-bold text-white">{{ number_format((float) $payment->amount, 2, '.', ' ') }} <span class="text-xs font-medium text-zinc-400">{{ $payment->currency }}</span></td>
                            <td class="px-5 py-3 text-xs text-zinc-400">{{ $payment->created_at?->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-zinc-500">{{ __('Платежів поки немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.section>

    <div class="mt-6">{{ $payments->links() }}</div>
</x-layouts.admin>
