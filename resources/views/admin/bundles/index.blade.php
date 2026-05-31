<x-layouts.admin title="Бандли" description="Пакети кількох моделей зі знижкою." breadcrumb-current="Бандли">
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-zinc-400">Всього бандлів: <strong class="text-white">{{ $bundles->total() }}</strong></p>
        <a href="{{ route('admin.bundles.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 transition hover:bg-emerald-300">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Новий бандл
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-white/10">
        <table class="min-w-full divide-y divide-white/5 text-sm">
            <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Назва</th>
                    <th class="px-4 py-3">Моделей</th>
                    <th class="px-4 py-3">Ціна</th>
                    <th class="px-4 py-3">Знижка</th>
                    <th class="px-4 py-3">Статус</th>
                    <th class="px-4 py-3 text-right">Дії</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($bundles as $bundle)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-white">{{ $bundle->localized('title') }}</p>
                            <p class="text-xs text-zinc-500">/bundles/{{ $bundle->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-300">{{ $bundle->items_count }}</td>
                        <td class="px-4 py-3 font-mono text-white">{{ number_format((float)$bundle->price, 2) }} UAH</td>
                        <td class="px-4 py-3">
                            @if($bundle->discount_percent > 0)
                                <span class="rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-bold text-red-400">-{{ $bundle->discount_percent }}%</span>
                            @else
                                <span class="text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($bundle->is_active)
                                <span class="rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-xs font-bold text-emerald-400">Активний</span>
                            @else
                                <span class="rounded-full bg-zinc-700/50 px-2.5 py-0.5 text-xs font-bold text-zinc-500">Чернетка</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.bundles.edit', $bundle) }}" class="rounded-lg border border-white/10 bg-white/[0.04] px-3 py-1.5 text-xs font-bold text-zinc-300 hover:bg-white/[0.08]">Редагувати</a>
                                <form method="POST" action="{{ route('admin.bundles.destroy', $bundle) }}" onsubmit="return confirm('Видалити бандл?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-rose-400/20 bg-rose-400/[0.06] px-3 py-1.5 text-xs font-bold text-rose-400 hover:bg-rose-400/[0.12]">Видалити</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">Бандлів ще немає. <a href="{{ route('admin.bundles.create') }}" class="text-emerald-400">Створити перший</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bundles->hasPages())
        <div class="mt-6">{{ $bundles->links() }}</div>
    @endif
</x-layouts.admin>
