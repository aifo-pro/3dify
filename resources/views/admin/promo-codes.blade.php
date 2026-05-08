<x-layouts.admin
    :title="__('Промокоди')"
    :description="__('Знижки на товари. Створюйте відсоткові або фіксовані купони з обмеженням використань і термінами дії.')"
    breadcrumb-current="{{ __('Промокоди') }}"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Усього') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.05] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Активних') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $stats['active'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Активацій') }}</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $stats['used'] }}</p>
        </div>
    </div>

    <x-admin.section :title="__('Створити промокод')">
        <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="grid gap-4 lg:grid-cols-4">
            @csrf
            <x-admin.field name="code" :label="__('Код')" required placeholder="SUMMER25" />
            <x-admin.field name="type" type="select" :label="__('Тип')">
                <option value="percent">{{ __('Відсоток (%)') }}</option>
                <option value="fixed">{{ __('Фіксована сума') }}</option>
            </x-admin.field>
            <x-admin.field name="value" type="number" step="0.01" min="0" :label="__('Значення')" required placeholder="10" />
            <x-admin.field name="usage_limit" type="number" min="1" :label="__('Ліміт використань')" placeholder="—" />
            <x-admin.field name="min_order_amount" type="number" step="0.01" min="0" :label="__('Мін. сума замовлення')" placeholder="—" />
            <x-admin.field name="starts_at" type="datetime-local" :label="__('Діє з')" />
            <x-admin.field name="expires_at" type="datetime-local" :label="__('Діє до')" />
            <x-admin.field name="description" :label="__('Опис')" placeholder="{{ __('Літня знижка') }}" class="lg:col-span-2" />
            <label class="flex items-center gap-2 self-end text-sm text-zinc-300">
                <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400">
                {{ __('Активний') }}
            </label>
            <div class="lg:col-span-4">
                <button class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Створити') }}</button>
            </div>
        </form>
    </x-admin.section>

    <x-admin.section :title="__('Усі промокоди')">
        <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Шукати за кодом…') }}" class="h-10 flex-1 min-w-[220px] rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
            <select name="status" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                <option value="">{{ __('Усі статуси') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Активні') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Неактивні') }}</option>
            </select>
            <button class="inline-flex h-10 items-center rounded-xl bg-white/[0.06] px-4 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('Фільтр') }}</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-white/10">
            <table class="min-w-full divide-y divide-white/5 text-sm">
                <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Код') }}</th>
                        <th class="px-4 py-3">{{ __('Тип / значення') }}</th>
                        <th class="px-4 py-3">{{ __('Використано') }}</th>
                        <th class="px-4 py-3">{{ __('Діє') }}</th>
                        <th class="px-4 py-3">{{ __('Статус') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Дії') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($codes as $promo)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-mono font-black text-white">{{ $promo->code }}</p>
                                @if($promo->description)<p class="mt-0.5 text-xs text-zinc-500">{{ $promo->description }}</p>@endif
                            </td>
                            <td class="px-4 py-3 align-top text-zinc-300">
                                {{ $promo->type === 'percent' ? number_format((float) $promo->value, 0).'%' : number_format((float) $promo->value, 2).' EUR' }}
                                @if($promo->min_order_amount)<p class="text-xs text-zinc-500">{{ __('мін.') }} {{ number_format((float) $promo->min_order_amount, 2) }}</p>@endif
                            </td>
                            <td class="px-4 py-3 align-top text-zinc-300">
                                {{ $promo->used_count }}{{ $promo->usage_limit ? ' / '.$promo->usage_limit : '' }}
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-400">
                                @if($promo->starts_at)<p>{{ __('з') }} {{ $promo->starts_at->format('Y-m-d H:i') }}</p>@endif
                                @if($promo->expires_at)<p class="{{ $promo->expires_at->isPast() ? 'text-rose-300' : '' }}">{{ __('до') }} {{ $promo->expires_at->format('Y-m-d H:i') }}</p>@else<p class="text-zinc-500">{{ __('без обмежень') }}</p>@endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if($promo->is_active && $promo->isUsable())
                                    <span class="inline-flex rounded-full border border-emerald-300/40 bg-emerald-300/[0.10] px-2.5 py-0.5 text-[11px] font-bold text-emerald-100">{{ __('активний') }}</span>
                                @else
                                    <span class="inline-flex rounded-full border border-zinc-500/30 bg-white/[0.04] px-2.5 py-0.5 text-[11px] font-bold text-zinc-400">{{ __('неактивний') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                <form method="POST" action="{{ route('admin.promo-codes.update', $promo) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="code" value="{{ $promo->code }}">
                                    <input type="hidden" name="type" value="{{ $promo->type }}">
                                    <input type="hidden" name="value" value="{{ $promo->value }}">
                                    <input type="hidden" name="is_active" value="{{ $promo->is_active ? 0 : 1 }}">
                                    <button class="rounded-lg border border-white/10 bg-white/[0.04] px-2.5 py-1 text-xs text-white hover:bg-white/[0.10]">{{ $promo->is_active ? __('Вимкнути') : __('Увімкнути') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.promo-codes.destroy', $promo) }}" class="inline" onsubmit="return confirm('{{ __('Видалити промокод?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-rose-300/30 bg-rose-300/[0.06] px-2.5 py-1 text-xs text-rose-200 hover:bg-rose-300/[0.12]">{{ __('Видалити') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Промокодів ще немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $codes->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
