<x-layouts.marketplace :seo-title="$challenge->localized('title') . ' · Челендж · 3Dify'">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        {{-- Header --}}
        <div class="mb-10 grid gap-8 lg:grid-cols-2 lg:items-center">
            @if($challenge->cover_path)
                <div class="overflow-hidden rounded-2xl aspect-video">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($challenge->cover_path) }}" class="h-full w-full object-cover">
                </div>
            @endif
            <div>
                @if($challenge->isOpen())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-400">● Активний</span>
                @else
                    <span class="inline-flex rounded-full bg-zinc-700/40 px-3 py-1 text-xs font-bold text-zinc-500">Завершено</span>
                @endif
                <h1 class="mt-4 text-3xl font-black text-white">{{ $challenge->localized('title') }}</h1>
                @if($challenge->localized('description'))
                    <p class="mt-3 text-zinc-400 leading-relaxed">{{ $challenge->localized('description') }}</p>
                @endif
                @if($challenge->prize_description)
                    <div class="mt-4 rounded-2xl border border-amber-400/25 bg-amber-400/[0.07] p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-amber-300">Приз</p>
                        <p class="mt-1 text-white">{{ $challenge->prize_description }}</p>
                    </div>
                @endif
                @if($challenge->ends_at)
                    <p class="mt-4 text-sm text-zinc-500">Дедлайн: <strong class="text-white">{{ $challenge->ends_at->translatedFormat('d M Y, H:i') }}</strong></p>
                @endif

                @auth
                    @if($challenge->isOpen() && ! $userEntry)
                        <div class="mt-6 rounded-2xl border border-white/10 bg-zinc-900/50 p-5">
                            <p class="mb-4 text-sm font-bold text-white">Взяти участь</p>
                            <form method="POST" action="{{ route('challenges.enter', $challenge) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <input type="file" name="image" accept="image/*" required class="w-full text-sm text-zinc-400 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-400/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-300">
                                <textarea name="description" rows="2" placeholder="Розкажіть про ваш друк..." class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none"></textarea>
                                <button class="w-full rounded-xl bg-emerald-400 py-2.5 text-sm font-black text-zinc-950 hover:bg-emerald-300">Завантажити роботу</button>
                            </form>
                        </div>
                    @elseif($userEntry)
                        <div class="mt-6 rounded-2xl border border-emerald-400/20 bg-emerald-400/[0.06] p-4">
                            <p class="text-sm font-bold text-emerald-300">✓ Ви вже берете участь!</p>
                            <p class="mt-1 text-xs text-zinc-400">Статус: {{ $userEntry->status === 'approved' ? 'Схвалено' : 'На перевірці' }}</p>
                        </div>
                    @endif
                @else
                    @if($challenge->isOpen())
                        <a href="{{ route('login') }}" class="mt-6 inline-flex items-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 hover:bg-emerald-300">Увійти і взяти участь</a>
                    @endif
                @endauth
            </div>
        </div>

        {{-- Entries --}}
        <h2 class="mb-6 text-xl font-black text-white">Роботи учасників <span class="text-zinc-500 text-base font-normal">({{ $entries->total() }})</span></h2>

        @if($entries->isEmpty())
            <p class="text-zinc-500">Поки немає схвалених робіт. Будьте першим!</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($entries as $entry)
                    @php $imgUrl = $entry->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($entry->image_path) : null; @endphp
                    <div class="overflow-hidden rounded-2xl border border-white/[0.07] bg-zinc-900/50"
                         x-data="{voted: {{ $entry->hasVotedBy(auth()->user()) ? 'true' : 'false' }}, votes: {{ $entry->votes }}}">
                        @if($imgUrl)
                            <img src="{{ $imgUrl }}" loading="lazy" class="aspect-square w-full object-cover">
                        @endif
                        <div class="p-4">
                            @if($entry->description)
                                <p class="mb-2 line-clamp-2 text-xs text-zinc-400">{{ $entry->description }}</p>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-zinc-500">{{ $entry->user?->displayName() }}</span>
                                @auth
                                    <button
                                        @click="fetch('{{ route('challenges.vote', $entry) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}}).then(r=>r.json()).then(d=>{voted=d.voted;votes=d.votes})"
                                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold transition"
                                        :class="voted ? 'bg-amber-400/15 text-amber-300' : 'bg-white/[0.05] text-zinc-400 hover:bg-amber-400/10 hover:text-amber-300'"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        <span x-text="votes"></span>
                                    </button>
                                @else
                                    <span class="flex items-center gap-1 text-xs text-zinc-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        {{ $entry->votes }}
                                    </span>
                                @endauth
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($entries->hasPages())
                <div class="mt-8">{{ $entries->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.marketplace>
