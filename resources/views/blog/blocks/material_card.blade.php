@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $name  = $locale === 'en'
        ? (trim((string) ($d['name_en'] ?? '')) ?: trim((string) ($d['name_uk'] ?? '')))
        : (trim((string) ($d['name_uk'] ?? '')) ?: trim((string) ($d['name_en'] ?? '')));
    $brand = trim((string) ($d['brand'] ?? ''));
    $type  = trim((string) ($d['type'] ?? ''));
    $href  = trim((string) ($d['href'] ?? ''));
    $key   = $locale === 'en' ? 'items_en' : 'items_uk';
    $pros  = is_array($d[$key] ?? null) ? $d[$key] : [];
    $pros  = array_values(array_filter($pros, fn ($i) => is_string($i) && trim($i) !== ''));
@endphp
@if($name !== '' || $pros !== [])
    <div class="overflow-hidden rounded-[1.75rem] border border-white/[0.08] bg-gradient-to-br from-white/[0.04] to-zinc-950 shadow-lg shadow-black/25">
        <div class="border-b border-white/[0.06] bg-white/[0.04] px-5 py-3">
            <p class="text-lg font-bold text-white">{{ $name }}</p>
            @if($brand !== '' || $type !== '')
                <p class="mt-0.5 text-sm text-zinc-400">{{ implode(' · ', array_filter([$brand, $type])) }}</p>
            @endif
        </div>
        @if($pros !== [])
            <ul class="space-y-2 px-5 py-4">
                @foreach($pros as $pro)
                    <li class="flex gap-3 text-sm leading-relaxed text-zinc-200">
                        <span class="mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-emerald-400" aria-hidden="true">
                            <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>
                        <span>{{ $pro }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($href !== '')
            <div class="border-t border-white/[0.06] px-5 pb-5">
                <a href="{{ $href }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-400 px-4 py-2 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">
                    {{ __('blog.learn_more') }} →
                </a>
            </div>
        @endif
    </div>
@endif
