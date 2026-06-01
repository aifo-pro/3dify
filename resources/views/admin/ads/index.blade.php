<x-layouts.admin title="Реклама" description="Нативні рекламні блоки в каталозі та на інших сторінках." breadcrumb-current="Реклама">

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    {{-- Summary stats --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-4">
        @php
            $total      = $ads->total();
            $active     = \App\Models\Advertisement::active()->count();
            $impressions = \App\Models\Advertisement::sum('impressions');
            $clicks      = \App\Models\Advertisement::sum('clicks');
            $ctr         = $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0;
        @endphp
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">Всього</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $total }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.05] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">Активних</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $active }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">Покази</p>
            <p class="mt-2 text-3xl font-black text-white">{{ number_format($impressions) }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">CTR</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $ctr }}%</p>
        </div>
    </div>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.ads.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 transition hover:bg-emerald-300">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Нова реклама
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-white/10">
        <table class="min-w-full divide-y divide-white/5 text-sm">
            <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Оголошення</th>
                    <th class="px-4 py-3">Тип</th>
                    <th class="px-4 py-3 text-center">Покази</th>
                    <th class="px-4 py-3 text-center">Кліки</th>
                    <th class="px-4 py-3 text-center">CTR</th>
                    <th class="px-4 py-3 text-center">Статус</th>
                    <th class="px-4 py-3 text-right">Дії</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($ads as $ad)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($ad->imageUrl())
                                    <img src="{{ $ad->imageUrl() }}" class="h-10 w-14 rounded-lg object-cover">
                                @else
                                    <div class="grid h-10 w-14 place-items-center rounded-lg bg-zinc-800 text-xs text-zinc-600">IMG</div>
                                @endif
                                <div>
                                    <p class="font-semibold text-white">{{ $ad->localized('title') }}</p>
                                    <p class="text-xs text-zinc-500 truncate max-w-[200px]">{{ $ad->target_url }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-0.5 text-xs font-bold text-zinc-300 uppercase">{{ $ad->ad_type }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-zinc-300">{{ number_format($ad->impressions) }}</td>
                        <td class="px-4 py-3 text-center text-zinc-300">{{ number_format($ad->clicks) }}</td>
                        <td class="px-4 py-3 text-center font-bold {{ $ad->ctr() >= 2 ? 'text-emerald-400' : 'text-zinc-400' }}">{{ $ad->ctr() }}%</td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.ads.toggle', $ad) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="rounded-full px-2.5 py-0.5 text-xs font-bold transition {{ $ad->is_active ? 'bg-emerald-400/15 text-emerald-400 hover:bg-emerald-400/25' : 'bg-zinc-700/40 text-zinc-500 hover:bg-zinc-700/60' }}">
                                    {{ $ad->is_active ? 'Активна' : 'Вимкнена' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.ads.edit', $ad) }}" class="rounded-lg border border-white/10 bg-white/[0.04] px-3 py-1.5 text-xs font-bold text-zinc-300 hover:bg-white/[0.08]">Редагувати</a>
                                <form method="POST" action="{{ route('admin.ads.destroy', $ad) }}" onsubmit="return confirm('Видалити?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-rose-400/20 bg-rose-400/[0.06] px-3 py-1.5 text-xs font-bold text-rose-400 hover:bg-rose-400/[0.12]">Видалити</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-zinc-500">
                            Реклами ще немає.
                            <a href="{{ route('admin.ads.create') }}" class="ml-1 text-emerald-400">Створити першу</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($ads->hasPages())
        <div class="mt-6">{{ $ads->links() }}</div>
    @endif
</x-layouts.admin>
