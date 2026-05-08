<x-layouts.admin
    :title="__('Чайові авторам')"
    :description="__('Усі тіпи від користувачів. Сума оплачених тіпів виплачується автору в payout.')"
    breadcrumb-current="{{ __('Тіпи') }}"
    active="finance"
>
    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('Усього')" :value="$totals['count']" />
        <x-admin.kpi-card :label="__('Сплачено сум')" :value="number_format($totals['paid_amount'], 2)" tone="emerald" />
        <x-admin.kpi-card :label="__('Сплачено тіпів')" :value="$totals['paid_count']" />
        <x-admin.kpi-card :label="__('Авторів отримало')" :value="$totals['authors']" />
    </div>

    <div class="my-5 flex flex-wrap gap-1.5">
        @foreach(['' => __('Усі'), 'pending' => __('Очікують'), 'paid' => __('Сплачені'), 'refunded' => __('Повернення')] as $key => $label)
            <a href="{{ route('admin.tips', $key === '' ? [] : ['status' => $key]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ ($status ?? '') === $key ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </div>

    <x-admin.section :title="__('Список')">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-950/40 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Дата') }}</th>
                        <th class="px-4 py-3">{{ __('Від') }}</th>
                        <th class="px-4 py-3">{{ __('Автор') }}</th>
                        <th class="px-4 py-3">{{ __('Модель') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Сума') }}</th>
                        <th class="px-4 py-3">{{ __('Статус') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($tips as $t)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-xs text-zinc-400">{{ $t->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-white">{{ $t->user?->name ?? __('гість') }}</p>
                                <p class="text-xs text-zinc-500">{{ $t->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-white">{{ $t->author?->name ?? '—' }}</p>
                                <p class="text-xs text-zinc-500">{{ $t->author?->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-zinc-300">
                                @if($t->product)
                                    <a href="{{ route('products.show', $t->product) }}" target="_blank" class="hover:text-emerald-200">{{ Str::limit($t->product->localized('title'), 40) }}</a>
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-200">{{ number_format($t->amount, 2) }} {{ $t->currency }}</td>
                            <td class="px-4 py-3">
                                <x-ui.status :status="$t->status" size="xs" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Тіпів немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $tips->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
