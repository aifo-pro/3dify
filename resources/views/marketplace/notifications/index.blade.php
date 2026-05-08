@php
    $iconMap = [
        'user-plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>',
        'message-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
        'shopping-bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'star' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    ];
@endphp

<x-layouts.marketplace>
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-ui.badge>{{ __('Сповіщення') }}</x-ui.badge>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('Інбокс') }}</h1>
                <p class="mt-2 text-sm text-zinc-400">{{ __('Підписки, продажі, коментарі та відгуки на ваші моделі.') }}</p>
            </div>
            @if(auth()->user()->unreadNotifications->count())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-white/15 bg-white/[0.05] px-4 text-xs font-bold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.10]">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ __('Позначити все прочитаним') }}
                    </button>
                </form>
            @endif
        </header>

        @if(session('status'))
            <div class="mb-5 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif

        @if($items->isNotEmpty())
            <ul class="grid gap-3">
                @foreach($items as $note)
                    @php
                        $data = $note->data ?? [];
                        $icon = $iconMap[$data['icon'] ?? 'message-circle'] ?? $iconMap['message-circle'];
                        $unread = ! $note->read_at;
                    @endphp
                    <li class="group rounded-2xl border {{ $unread ? 'border-emerald-300/30 bg-emerald-300/[0.04]' : 'border-white/10 bg-white/[0.03]' }} p-4 transition hover:bg-white/[0.06]">
                        <a href="{{ route('notifications.read', $note->id) }}" class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-white/10 bg-zinc-950/60 text-emerald-200">
                                <span class="grid h-4 w-4 place-items-center">{!! $icon !!}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-bold text-white">{{ $data['title'] ?? __('Сповіщення') }}</p>
                                    @if($unread)
                                        <span class="inline-flex items-center rounded-full bg-emerald-300/20 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-100">{{ __('нове') }}</span>
                                    @endif
                                    <span class="ml-auto text-[11px] text-zinc-500">{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1 text-sm leading-5 text-zinc-300">{{ $data['message'] ?? '' }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $items->links() }}</div>
        @else
            <div class="grid place-items-center rounded-3xl border border-dashed border-white/10 bg-white/[0.02] px-6 py-16 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-300/[0.10] text-emerald-200">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-white">{{ __('Поки немає сповіщень') }}</h2>
                <p class="mt-1 max-w-md text-sm text-zinc-400">{{ __('Тут будуть зʼявлятись підписки, продажі, коментарі та відгуки на ваші моделі.') }}</p>
            </div>
        @endif
    </section>
</x-layouts.marketplace>
