<x-layouts.admin title="Челенджі" breadcrumb-current="Челенджі">
    @if(session('status'))<div class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>@endif
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.challenges.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300">+ Новий челендж</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-white/10">
        <table class="min-w-full divide-y divide-white/5 text-sm">
            <thead class="bg-white/[0.03] text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                <tr><th class="px-4 py-3 text-left">Назва</th><th class="px-4 py-3">Учасники</th><th class="px-4 py-3">Статус</th><th class="px-4 py-3">Дедлайн</th><th class="px-4 py-3 text-right">Дії</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($challenges as $ch)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-3 font-semibold text-white">{{ $ch->localized('title') }}</td>
                        <td class="px-4 py-3 text-center text-zinc-300">{{ $ch->entries_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($ch->is_active)<span class="rounded-full bg-emerald-400/15 px-2 py-0.5 text-xs font-bold text-emerald-400">Активний</span>
                            @else<span class="rounded-full bg-zinc-700/40 px-2 py-0.5 text-xs font-bold text-zinc-500">Неактивний</span>@endif
                        </td>
                        <td class="px-4 py-3 text-zinc-400">{{ $ch->ends_at?->format('d.m.Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.challenges.edit', $ch) }}" class="rounded-lg border border-white/10 bg-white/[0.04] px-3 py-1.5 text-xs font-bold text-zinc-300 hover:bg-white/[0.08]">Редагувати</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">Челенджів немає.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($challenges->hasPages())<div class="mt-4">{{ $challenges->links() }}</div>@endif
</x-layouts.admin>
