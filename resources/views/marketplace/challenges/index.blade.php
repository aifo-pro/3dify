<x-layouts.marketplace seo-title="Челенджі · 3Dify">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mb-10">
            <span class="inline-flex items-center rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-black uppercase tracking-widest text-amber-300">Спільнота</span>
            <h1 class="mt-4 text-4xl font-black text-white sm:text-5xl">Челенджі друку</h1>
            <p class="mt-3 max-w-2xl text-zinc-400">Надрукуйте, сфотографуйте і виграйте. Беріть участь у конкурсах 3D-друку.</p>
        </div>

        @if($challenges->isEmpty())
            <x-ui.empty-state title="Активних челенджів немає" description="Незабаром з'являться нові конкурси. Стежте за оновленнями!" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($challenges as $ch)
                    <a href="{{ route('challenges.show', $ch) }}" class="group overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-900/50 transition hover:border-amber-400/25">
                        <div class="aspect-video overflow-hidden bg-zinc-950">
                            @if($ch->cover_path)
                                <img src="{{ Storage::disk('public')->url($ch->cover_path) }}" alt="{{ $ch->localized('title') }}"
                                     loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="grid h-full w-full place-items-center text-4xl">🏆</div>
                            @endif
                        </div>
                        <div class="p-5">
                            @if($ch->isOpen())
                                <span class="inline-flex items-center rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-xs font-bold text-emerald-400">● Активний</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-700/50 px-2.5 py-0.5 text-xs font-bold text-zinc-500">Завершено</span>
                            @endif
                            <h2 class="mt-3 text-lg font-black text-white group-hover:text-amber-200">{{ $ch->localized('title') }}</h2>
                            <div class="mt-3 flex items-center justify-between text-xs text-zinc-500">
                                <span>{{ $ch->entries_count }} учасників</span>
                                @if($ch->ends_at)
                                    <span>до {{ $ch->ends_at->translatedFormat('d M Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.marketplace>
