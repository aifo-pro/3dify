<x-layouts.admin
    :title="__('Модерація')"
    :description="__('Єдиний центр для всіх черг модерації: моделі, скарги, рев\'ю, коментарі, фото друку та повернення.')"
    breadcrumb-current="{{ __('Модерація') }}"
    active="moderation"
>
    @php
        $cards = [
            ['key' => 'products', 'label' => __('Моделі на модерації'), 'desc' => __('Нові публікації, що чекають перевірки.'), 'href' => route('admin.products', ['status' => 'pending']), 'icon' => 'box', 'palette' => 'amber'],
            ['key' => 'reports', 'label' => __('Скарги'), 'desc' => __('Користувацькі поскаржилися на моделі.'), 'href' => route('admin.moderation.reports'), 'icon' => 'flag', 'palette' => 'rose'],
            ['key' => 'reviews', 'label' => __('Рев\'ю'), 'desc' => __('Невірні чи спірні оцінки.'), 'href' => route('admin.moderation.reviews'), 'icon' => 'star', 'palette' => 'violet'],
            ['key' => 'comments', 'label' => __('Коментарі'), 'desc' => __('Чекають на затвердження або скаржилися.'), 'href' => route('admin.moderation.comments'), 'icon' => 'message', 'palette' => 'sky'],
            ['key' => 'makes', 'label' => __('Фото друку'), 'desc' => __('Користувацькі надсилання фото.'), 'href' => route('admin.moderation.makes'), 'icon' => 'camera', 'palette' => 'emerald'],
            ['key' => 'refunds', 'label' => __('Повернення'), 'desc' => __('Запити на refund.'), 'href' => route('admin.refunds'), 'icon' => 'shield', 'palette' => 'orange'],
        ];

        $palette = [
            'amber' => 'border-amber-300/30 bg-amber-300/[0.06] text-amber-100',
            'rose' => 'border-rose-300/30 bg-rose-300/[0.06] text-rose-100',
            'violet' => 'border-violet-300/30 bg-violet-300/[0.06] text-violet-100',
            'sky' => 'border-sky-300/30 bg-sky-300/[0.06] text-sky-100',
            'emerald' => 'border-emerald-300/30 bg-emerald-300/[0.06] text-emerald-100',
            'orange' => 'border-orange-300/30 bg-orange-300/[0.06] text-orange-100',
        ];

        $totalPending = array_sum($counts);
    @endphp

    <div class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-emerald-300/30 bg-gradient-to-br from-emerald-300/[0.08] to-transparent p-5">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Чергове навантаження') }}</p>
            <p class="mt-1 text-3xl font-black text-white">{{ $totalPending }} <span class="text-sm font-medium text-zinc-400">{{ __('очікує дій') }}</span></p>
        </div>
        <a href="#queues" class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Перейти до черг') }}</a>
    </div>

    <div id="queues" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($cards as $card)
            @php $count = $counts[$card['key']] ?? 0; @endphp
            <a href="{{ $card['href'] }}" class="group rounded-2xl border bg-white/[0.03] p-5 transition hover:-translate-y-0.5 {{ $palette[$card['palette']] }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] opacity-70">{{ $card['label'] }}</p>
                        <p class="mt-2 text-4xl font-black text-white">{{ $count }}</p>
                    </div>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/10">
                        @switch($card['icon'])
                            @case('box')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>@break
                            @case('flag')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>@break
                            @case('star')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@break
                            @case('message')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>@break
                            @case('camera')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>@break
                            @case('shield')<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>@break
                        @endswitch
                    </span>
                </div>
                <p class="mt-3 text-xs leading-5 opacity-80">{{ $card['desc'] }}</p>
                <p class="mt-3 inline-flex items-center gap-1 text-xs font-bold opacity-90 group-hover:opacity-100">{{ __('Відкрити') }} →</p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        @foreach([
            'reports' => __('Останні скарги'),
            'reviews' => __('Останні рев\'ю'),
            'comments' => __('Останні коментарі'),
            'makes' => __('Останні фото друку'),
        ] as $type => $title)
            <x-admin.section :title="$title">
                @if($recent[$type]->isEmpty())
                    <p class="py-6 text-center text-xs text-zinc-500">{{ __('Поки порожньо.') }}</p>
                @else
                    <ul class="divide-y divide-white/5">
                        @foreach($recent[$type] as $item)
                            <li class="flex items-center gap-3 py-2.5 text-sm">
                                @if($type === 'reports')
                                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-rose-300/[0.10] text-rose-200 text-[9px] font-bold uppercase">{{ Str::substr($item->reason, 0, 3) }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-zinc-200">{{ $item->product?->localized('title') ?? '—' }}</p>
                                        <p class="truncate text-[11px] text-zinc-500">{{ $item->user?->name ?? __('гість') }} · {{ $item->created_at->translatedFormat('d M H:i') }}</p>
                                    </div>
                                @elseif($type === 'reviews')
                                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-amber-300/[0.10] text-amber-200 text-xs font-bold">{{ $item->rating }}★</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-zinc-200">{{ $item->product?->localized('title') ?? '—' }}</p>
                                        <p class="truncate text-[11px] text-zinc-500">{{ $item->user?->name ?? '' }} · {{ $item->created_at->translatedFormat('d M H:i') }}</p>
                                    </div>
                                @elseif($type === 'comments')
                                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-sky-300/[0.10] text-sky-200">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-zinc-200">{{ Str::limit($item->body, 80) }}</p>
                                        <p class="truncate text-[11px] text-zinc-500">{{ $item->user?->name }} · {{ $item->product?->localized('title') ?? '' }}</p>
                                    </div>
                                @elseif($type === 'makes')
                                    @if($item->image_path && \Storage::disk('public')->exists($item->image_path))
                                        <img src="{{ \Storage::disk('public')->url($item->image_path) }}" alt="" class="h-7 w-7 rounded-lg object-cover">
                                    @else
                                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-emerald-300/[0.10] text-emerald-200">📷</span>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-zinc-200">{{ $item->product?->localized('title') ?? '—' }}</p>
                                        <p class="truncate text-[11px] text-zinc-500">{{ $item->user?->name }} · {{ $item->created_at->translatedFormat('d M H:i') }}</p>
                                    </div>
                                @endif
                                <x-ui.status :status="$item->status" size="xs" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.section>
        @endforeach
    </div>
</x-layouts.admin>
