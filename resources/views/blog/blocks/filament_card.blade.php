@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $name = $locale === 'en'
        ? (trim((string) ($d['name_en'] ?? '')) ?: trim((string) ($d['name_uk'] ?? '')))
        : (trim((string) ($d['name_uk'] ?? '')) ?: trim((string) ($d['name_en'] ?? '')));
    $brand    = trim((string) ($d['brand'] ?? ''));
    $material = trim((string) ($d['material'] ?? 'PLA'));
    $nozzle   = trim((string) ($d['temp_nozzle'] ?? ''));
    $bed      = trim((string) ($d['temp_bed'] ?? ''));
    $color    = trim((string) ($d['color'] ?? ''));
    $price    = trim((string) ($d['price'] ?? ''));
    $href     = trim((string) ($d['href'] ?? ''));
@endphp
@if($name !== '' || $brand !== '')
    <div class="overflow-hidden rounded-[1.75rem] border border-emerald-400/20 bg-gradient-to-br from-emerald-400/[0.10] via-zinc-900/80 to-zinc-950 shadow-lg shadow-emerald-900/20">
        <div class="flex items-center gap-3 border-b border-emerald-400/10 bg-emerald-400/[0.06] px-5 py-3">
            <span class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-400">{{ __('blog.filament_card') }}</span>
            @if($material !== '')
                <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2 py-0.5 text-[10px] font-bold text-emerald-200">{{ $material }}</span>
            @endif
        </div>
        <div class="px-5 py-5 sm:px-6">
            <p class="text-lg font-bold text-white">{{ $name }}</p>
            @if($brand !== '')
                <p class="mt-0.5 text-sm text-zinc-400">{{ $brand }}</p>
            @endif
            <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 text-sm">
                @if($nozzle !== '')
                    <div class="rounded-xl border border-white/[0.07] bg-white/[0.03] px-3 py-2">
                        <dt class="text-[10px] font-bold uppercase text-zinc-500">{{ __('blog.nozzle') }}</dt>
                        <dd class="mt-0.5 font-semibold text-white">{{ $nozzle }} °C</dd>
                    </div>
                @endif
                @if($bed !== '')
                    <div class="rounded-xl border border-white/[0.07] bg-white/[0.03] px-3 py-2">
                        <dt class="text-[10px] font-bold uppercase text-zinc-500">{{ __('blog.bed') }}</dt>
                        <dd class="mt-0.5 font-semibold text-white">{{ $bed }} °C</dd>
                    </div>
                @endif
                @if($color !== '')
                    <div class="rounded-xl border border-white/[0.07] bg-white/[0.03] px-3 py-2">
                        <dt class="text-[10px] font-bold uppercase text-zinc-500">{{ __('blog.color') }}</dt>
                        <dd class="mt-0.5 font-semibold text-white">{{ $color }}</dd>
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
