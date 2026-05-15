@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $name   = $locale === 'en'
        ? (trim((string) ($d['name_en'] ?? '')) ?: trim((string) ($d['name_uk'] ?? '')))
        : (trim((string) ($d['name_uk'] ?? '')) ?: trim((string) ($d['name_en'] ?? '')));
    $brand  = trim((string) ($d['brand'] ?? ''));
    $volume = trim((string) ($d['build_volume'] ?? ''));
    $tech   = trim((string) ($d['tech'] ?? 'FDM'));
    $price  = trim((string) ($d['price'] ?? ''));
    $href   = trim((string) ($d['href'] ?? ''));
@endphp
@if($name !== '' || $brand !== '')
    <div class="overflow-hidden rounded-[1.75rem] border border-white/[0.08] bg-gradient-to-br from-white/[0.05] to-zinc-950 shadow-lg shadow-black/25">
        <div class="flex items-center gap-3 border-b border-white/[0.06] bg-white/[0.04] px-5 py-3">
            <span class="text-[10px] font-black uppercase tracking-[0.18em] text-zinc-400">{{ __('blog.printer_card') }}</span>
            @if($tech !== '')
                <span class="rounded-full border border-white/15 bg-white/[0.05] px-2 py-0.5 text-[10px] font-bold text-zinc-300">{{ $tech }}</span>
            @endif
        </div>
        <div class="px-5 py-5 sm:px-6">
            <p class="text-lg font-bold text-white">{{ $name }}</p>
            @if($brand !== '')
                <p class="mt-0.5 text-sm text-zinc-400">{{ $brand }}</p>
            @endif
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                @if($volume !== '')
                    <div class="rounded-xl border border-white/[0.07] bg-white/[0.03] px-3 py-2">
                        <dt class="text-[10px] font-bold uppercase text-zinc-500">{{ __('blog.build_volume') }}</dt>
                        <dd class="mt-0.5 font-semibold text-white">{{ $volume }}</dd>
                    </div>
                @endif
                @if($price !== '')
                    <div class="rounded-xl border border-white/[0.07] bg-white/[0.03] px-3 py-2">
                        <dt class="text-[10px] font-bold uppercase text-zinc-500">{{ __('blog.price') }}</dt>
                        <dd class="mt-0.5 font-semibold text-emerald-200">{{ $price }}</dd>
                    </div>
                @endif
            </dl>
            @if($href !== '')
                <a href="{{ $href }}" target="_blank" rel="noopener" class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">
                    {{ __('blog.buy_now') }} →
                </a>
            @endif
        </div>
    </div>
@endif
