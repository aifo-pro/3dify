<x-layouts.marketplace seo-title="Рейтинг авторів · 3Dify">
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        <div class="mb-10">
            <h1 class="text-4xl font-black text-white">🏆 Рейтинг авторів</h1>
            <p class="mt-3 text-zinc-400">Найкращі автори маркетплейсу 3Dify за завантаженнями, кількістю моделей і підписниками.</p>
        </div>

        <div class="grid gap-8 lg:grid-cols-3">
            @foreach([
                ['title' => 'Топ по завантаженнях', 'items' => $topByDownloads, 'key' => 'total_downloads', 'label' => 'завантажень'],
                ['title' => 'Топ по моделях', 'items' => $topByProducts, 'key' => 'published_count', 'label' => 'моделей'],
                ['title' => 'Топ по підписниках', 'items' => $topByFollowers, 'key' => 'followers_count', 'label' => 'підписників'],
            ] as $board)
                <div class="rounded-2xl border border-white/[0.08] bg-zinc-900/50 p-5">
                    <h2 class="mb-4 text-sm font-black uppercase tracking-widest text-zinc-400">{{ $board['title'] }}</h2>
                    <ol class="space-y-2">
                        @foreach($board['items']->take(10) as $i => $user)
                            <li class="flex items-center gap-3 rounded-xl {{ $i < 3 ? 'bg-gradient-to-r from-amber-400/[0.08] to-transparent' : '' }} px-3 py-2.5">
                                <span class="w-5 shrink-0 text-center font-mono text-xs font-bold {{ $i === 0 ? 'text-amber-300' : ($i === 1 ? 'text-zinc-300' : ($i === 2 ? 'text-amber-600' : 'text-zinc-600')) }}">
                                    {{ $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ($i + 1))) }}
                                </span>
                                @if($user->avatarUrl())
                                    <img src="{{ $user->avatarUrl() }}" class="h-7 w-7 shrink-0 rounded-full object-cover">
                                @else
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-zinc-700 text-xs font-black text-zinc-300">{{ mb_strtoupper(mb_substr($user->displayName(), 0, 1)) }}</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $user->profileUrl() }}" class="block truncate text-sm font-semibold text-white hover:text-emerald-300">{{ $user->displayName() }}</a>
                                </div>
                                <span class="shrink-0 text-xs font-bold text-zinc-400">
                                    {{ number_format((int) ($user->{$board['key']} ?? 0)) }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.marketplace>
