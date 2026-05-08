@props([
    'title',
    'subtitle' => null,
    'price' => null,
    'free' => false,
    'tone' => 'emerald',
    'icon' => 'cube',
])

@php
    $tones = [
        'emerald' => 'bg-[radial-gradient(circle_at_30%_25%,rgba(52,211,153,.30),transparent_60%),linear-gradient(135deg,#0a1f1a_0%,#06141f_100%)] text-emerald-200',
        'rose' => 'bg-[radial-gradient(circle_at_30%_25%,rgba(244,114,182,.30),transparent_60%),linear-gradient(135deg,#1a0e16_0%,#1f1014_100%)] text-rose-200',
        'amber' => 'bg-[radial-gradient(circle_at_30%_25%,rgba(251,191,36,.30),transparent_60%),linear-gradient(135deg,#1f1808_0%,#1a1408_100%)] text-amber-200',
        'sky' => 'bg-[radial-gradient(circle_at_30%_25%,rgba(56,189,248,.30),transparent_60%),linear-gradient(135deg,#06141f_0%,#0c2236_100%)] text-sky-200',
        'violet' => 'bg-[radial-gradient(circle_at_30%_25%,rgba(167,139,250,.30),transparent_60%),linear-gradient(135deg,#0f0a1f_0%,#1a1238_100%)] text-violet-200',
        'lime' => 'bg-[radial-gradient(circle_at_30%_25%,rgba(132,204,22,.30),transparent_60%),linear-gradient(135deg,#0d1a08_0%,#101a0c_100%)] text-lime-200',
    ];

    $toneCls = $tones[$tone] ?? $tones['emerald'];

    $svgs = [
        'dragon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16"><path d="M2 12c0-3 2-5 5-5h2l3-3v6l3-3 3 3v3l-3 3v3l-3-3-3 3v-3H7c-3 0-5-2-5-5z"/><circle cx="6.5" cy="11" r="1" fill="currentColor"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16"><rect x="7" y="3" width="10" height="18" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>',
        'organizer' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'hook' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16"><path d="M12 3v6"/><path d="M12 9c-3 0-5 2-5 5 0 4 5 7 5 7s5-3 5-7c0-3-2-5-5-5z"/></svg>',
        'cube' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
    ];
    $iconSvg = $svgs[$icon] ?? $svgs['cube'];
@endphp

<div class="group relative block overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20 transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:bg-white/[0.06]">
    <div class="relative aspect-[4/3] overflow-hidden {{ $toneCls }}">
        {{-- Grid floor --}}
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-[linear-gradient(transparent,rgba(0,0,0,.4)),repeating-linear-gradient(90deg,rgba(255,255,255,.05)_0,rgba(255,255,255,.05)_1px,transparent_1px,transparent_22px),repeating-linear-gradient(0deg,rgba(255,255,255,.05)_0,rgba(255,255,255,.05)_1px,transparent_1px,transparent_22px)] [mask-image:linear-gradient(transparent,#000_25%)]"></div>

        <div class="absolute inset-0 grid place-items-center transition duration-500 group-hover:scale-110">
            {!! $iconSvg !!}
        </div>

        <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full border border-white/10 bg-zinc-950/70 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-200 backdrop-blur">
            <svg class="h-2.5 w-2.5 text-amber-300" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 14.39 8.26 21 9 16 13.74 17.18 20.52 12 17.27 6.82 20.52 8 13.74 3 9 9.61 8.26z"/></svg>
            Demo
        </span>

        @if($free)
            <span class="absolute right-3 top-3 inline-flex items-center rounded-full bg-emerald-400 px-2.5 py-0.5 text-[11px] font-black text-zinc-950 shadow-lg shadow-emerald-500/30">FREE</span>
        @elseif($price)
            <span class="absolute right-3 top-3 inline-flex items-center rounded-full border border-white/10 bg-zinc-950/80 px-2.5 py-0.5 text-[11px] font-black text-white backdrop-blur">{{ $price }}</span>
        @endif
    </div>

    <div class="p-5">
        <h3 class="line-clamp-2 text-lg font-semibold leading-snug text-white group-hover:text-emerald-100">{{ $title }}</h3>
        <p class="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-zinc-400">{{ $subtitle ?? __('Готова до друку 3D-модель з перевіреними файлами.') }}</p>
        <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/10 pt-4 text-xs text-zinc-500">
            <span class="truncate">3Dify Studio</span>
            <span class="inline-flex items-center gap-1 truncate text-zinc-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                {{ __('demo preview') }}
            </span>
        </div>
    </div>
</div>
