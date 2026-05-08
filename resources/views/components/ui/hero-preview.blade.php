{{--
    A static "marketplace preview" mockup for the home hero.
    Pure HTML/CSS — no real 3D, no broken placeholder.
    Composition:
      - rounded outer card with browser-like chrome
      - hero "featured model" tile with faux-3D shape
      - 3 thumbnails (Phone Stand / Desk Organizer / Wall Hook)
      - floating chip badges around the card
--}}

<div class="relative">
    {{-- Soft ambient glow --}}
    <div class="pointer-events-none absolute -inset-12 -z-10">
        <div class="absolute left-0 top-10 h-72 w-72 rounded-full bg-emerald-400/25 blur-[110px]"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-sky-400/20 blur-[120px]"></div>
        <div class="absolute right-12 top-0 h-48 w-48 rounded-full bg-violet-400/20 blur-[100px]"></div>
    </div>

    {{-- Outer card frame --}}
    <div class="relative rounded-[28px] border border-white/10 bg-gradient-to-br from-zinc-900/80 via-zinc-950/90 to-black/80 p-4 shadow-2xl shadow-black/50 backdrop-blur-xl sm:p-5">
        {{-- Browser chrome --}}
        <div class="mb-4 flex items-center justify-between gap-3 border-b border-white/[0.07] pb-3">
            <div class="flex items-center gap-1.5">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400/60"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400/60"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/70"></span>
            </div>
            <div class="flex h-7 max-w-[260px] flex-1 items-center gap-2 rounded-lg border border-white/10 bg-zinc-950/80 px-3">
                <svg class="h-3 w-3 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span class="truncate font-mono text-[11px] text-zinc-500">3dify.com<span class="text-emerald-200">/models/mythic-dragon</span></span>
            </div>
            <div class="hidden items-center gap-1 rounded-md border border-amber-300/30 bg-amber-300/[0.10] px-2 py-1 sm:flex">
                <svg class="h-2.5 w-2.5 text-amber-300" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 14.39 8.26 21 9 16 13.74 17.18 20.52 12 17.27 6.82 20.52 8 13.74 3 9 9.61 8.26z"/></svg>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-200">{{ __('Preview') }}</span>
            </div>
        </div>

        {{-- Featured hero tile --}}
        <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-950/60">
            <div class="relative aspect-[16/10] overflow-hidden bg-[radial-gradient(circle_at_30%_30%,rgba(52,211,153,.25),transparent_60%),radial-gradient(circle_at_70%_70%,rgba(56,189,248,.20),transparent_60%),linear-gradient(135deg,#0a1f1a_0%,#0a141f_100%)]">
                {{-- Fake grid floor --}}
                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-[linear-gradient(transparent,rgba(0,0,0,.45)),repeating-linear-gradient(90deg,rgba(255,255,255,.04)_0,rgba(255,255,255,.04)_1px,transparent_1px,transparent_24px),repeating-linear-gradient(0deg,rgba(255,255,255,.04)_0,rgba(255,255,255,.04)_1px,transparent_1px,transparent_24px)] [mask-image:linear-gradient(transparent,#000_25%)]"></div>

                {{-- Stacked geometric "model" --}}
                <div class="absolute inset-0 grid place-items-center">
                    <div class="relative h-44 w-44 sm:h-52 sm:w-52">
                        <div class="absolute inset-0 rotate-45 rounded-[28%] bg-gradient-to-br from-emerald-300/80 via-emerald-400/60 to-sky-400/40 shadow-2xl shadow-emerald-500/40 ring-1 ring-white/20"></div>
                        <div class="absolute inset-3 rotate-45 rounded-[28%] bg-gradient-to-br from-white/10 to-transparent backdrop-blur"></div>
                        <div class="absolute inset-8 grid rotate-45 place-items-center rounded-[28%] border border-white/30 bg-zinc-950/40 backdrop-blur">
                            <span class="-rotate-45 text-2xl font-black tracking-tight text-white drop-shadow">3D</span>
                        </div>
                        <span class="absolute -right-2 top-1/3 grid h-9 w-9 place-items-center rounded-full border border-emerald-300/40 bg-emerald-300/20 text-emerald-100 shadow-lg shadow-emerald-500/30">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </div>
                </div>

                {{-- Top corner badges --}}
                <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-zinc-950/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-200 backdrop-blur">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 14.39 8.26 21 9 16 13.74 17.18 20.52 12 17.27 6.82 20.52 8 13.74 3 9 9.61 8.26z"/></svg>
                    {{ __('Premium') }}
                </span>
                <span class="absolute right-3 top-3 inline-flex items-center rounded-full bg-emerald-400 px-2.5 py-1 text-[11px] font-black text-zinc-950 shadow-lg shadow-emerald-500/30">€8.50</span>

                {{-- Bottom rotation handle (decorative) --}}
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full border border-white/10 bg-zinc-950/70 px-3 py-1 backdrop-blur">
                    <div class="flex items-center gap-2 text-[10px] text-zinc-400">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                        <span class="font-mono">{{ __('drag · zoom · rotate') }}</span>
                    </div>
                </div>
            </div>

            {{-- Card meta --}}
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-white">{{ __('Mythic Dragon') }} · 32mm</p>
                    <p class="truncate text-[11px] text-zinc-500">{{ __('by') }} {{ __('Studio Helios') }} · STL · GLB · OBJ</p>
                </div>
                <div class="shrink-0 text-right">
                    <div class="flex items-center justify-end gap-0.5 text-amber-300">
                        @for($i=0; $i<5; $i++)
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 14.39 8.26 21 9 16 13.74 17.18 20.52 12 17.27 6.82 20.52 8 13.74 3 9 9.61 8.26z"/></svg>
                        @endfor
                    </div>
                    <p class="mt-0.5 text-[10px] text-zinc-500">128 {{ __('downloads') }}</p>
                </div>
            </div>
        </div>

        {{-- 3 thumbnails --}}
        <div class="mt-3 grid grid-cols-3 gap-2.5 sm:gap-3">
            {{-- Phone Stand --}}
            <div class="group relative aspect-square overflow-hidden rounded-xl border border-white/10 bg-[radial-gradient(circle_at_30%_30%,rgba(244,114,182,.25),transparent_60%),linear-gradient(135deg,#1a0e16_0%,#1f1014_100%)]">
                <div class="absolute inset-0 grid place-items-center">
                    <div class="h-12 w-9 rounded-md border border-white/20 bg-gradient-to-br from-rose-300/60 to-pink-400/40 shadow-lg sm:h-14 sm:w-10"></div>
                </div>
                <span class="absolute right-1.5 top-1.5 rounded-full bg-emerald-400 px-1.5 py-0.5 text-[9px] font-black text-zinc-950">{{ __('FREE') }}</span>
                <p class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/80 to-transparent p-2 text-[10px] font-bold text-white">{{ __('Phone Stand') }}</p>
            </div>
            {{-- Desk Organizer --}}
            <div class="group relative aspect-square overflow-hidden rounded-xl border border-white/10 bg-[radial-gradient(circle_at_30%_30%,rgba(167,139,250,.30),transparent_60%),linear-gradient(135deg,#0f0a1f_0%,#1a1238_100%)]">
                <div class="absolute inset-0 grid place-items-center">
                    <div class="grid grid-cols-2 gap-1">
                        <div class="h-5 w-5 rounded-sm bg-gradient-to-br from-violet-300/70 to-indigo-400/50 sm:h-6 sm:w-6"></div>
                        <div class="h-5 w-5 rounded-sm bg-gradient-to-br from-violet-300/50 to-indigo-400/30 sm:h-6 sm:w-6"></div>
                        <div class="h-5 w-5 rounded-sm bg-gradient-to-br from-violet-300/50 to-indigo-400/30 sm:h-6 sm:w-6"></div>
                        <div class="h-5 w-5 rounded-sm bg-gradient-to-br from-violet-300/70 to-indigo-400/50 sm:h-6 sm:w-6"></div>
                    </div>
                </div>
                <span class="absolute right-1.5 top-1.5 rounded-full bg-zinc-950/80 px-1.5 py-0.5 text-[9px] font-bold text-violet-100 backdrop-blur">€2.50</span>
                <p class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/80 to-transparent p-2 text-[10px] font-bold text-white">{{ __('Desk Organizer') }}</p>
            </div>
            {{-- Wall Hook --}}
            <div class="group relative aspect-square overflow-hidden rounded-xl border border-white/10 bg-[radial-gradient(circle_at_30%_30%,rgba(56,189,248,.30),transparent_60%),linear-gradient(135deg,#06141f_0%,#0c2236_100%)]">
                <div class="absolute inset-0 grid place-items-center">
                    <svg class="h-12 w-12 text-sky-300/80 sm:h-14 sm:w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6"/><path d="M12 8c-3 0-5 2-5 5 0 4 5 7 5 7s5-3 5-7c0-3-2-5-5-5z"/></svg>
                </div>
                <span class="absolute right-1.5 top-1.5 rounded-full bg-zinc-950/80 px-1.5 py-0.5 text-[9px] font-bold text-sky-100 backdrop-blur">€1.50</span>
                <p class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/80 to-transparent p-2 text-[10px] font-bold text-white">{{ __('Wall Hook') }}</p>
            </div>
        </div>
    </div>

    {{-- Floating chip badges --}}
    <div class="absolute -left-3 top-12 hidden items-center gap-2 rounded-full border border-white/10 bg-zinc-950/85 px-3 py-2 shadow-xl shadow-black/40 backdrop-blur sm:flex">
        <span class="grid h-5 w-5 place-items-center rounded-md bg-emerald-300/15 text-emerald-200">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </span>
        <div class="leading-tight">
            <p class="text-[10px] font-bold text-white">{{ __('STL ready') }}</p>
            <p class="text-[9px] text-zinc-500">{{ __('slicer-friendly') }}</p>
        </div>
    </div>

    <div class="absolute -right-3 top-32 flex items-center gap-2 rounded-full border border-white/10 bg-zinc-950/85 px-3 py-2 shadow-xl shadow-black/40 backdrop-blur">
        <span class="grid h-5 w-5 place-items-center rounded-md bg-sky-300/15 text-sky-200">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7"/></svg>
        </span>
        <div class="leading-tight">
            <p class="text-[10px] font-bold text-white">{{ __('GLB preview') }}</p>
            <p class="text-[9px] text-zinc-500">{{ __('in-browser') }}</p>
        </div>
    </div>

    <div class="absolute -bottom-3 right-8 flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/[0.12] px-3 py-2 shadow-xl shadow-emerald-500/20 backdrop-blur">
        <span class="grid h-5 w-5 place-items-center rounded-md bg-emerald-400/30 text-emerald-50">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
        <div class="leading-tight">
            <p class="text-[10px] font-bold text-emerald-50">{{ __('Secure download') }}</p>
            <p class="text-[9px] text-emerald-200/80">{{ __('paid-only access') }}</p>
        </div>
    </div>
</div>
