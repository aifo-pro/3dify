@php
    use Illuminate\Support\Facades\Storage;
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $path = trim((string) ($d['path'] ?? ''));
    if ($path === '') {
        $src = null;
    } else {
        $u = Storage::disk('public')->url($path);
        $src = str_starts_with($u, 'http') ? $u : url($u);
    }
    $alt = $locale === 'en'
        ? (trim((string) ($d['alt_en'] ?? '')) ?: trim((string) ($d['alt_uk'] ?? '')))
        : (trim((string) ($d['alt_uk'] ?? '')) ?: trim((string) ($d['alt_en'] ?? '')));
    $cap = $locale === 'en'
        ? (trim((string) ($d['caption_en'] ?? '')) ?: trim((string) ($d['caption_uk'] ?? '')))
        : (trim((string) ($d['caption_uk'] ?? '')) ?: trim((string) ($d['caption_en'] ?? '')));
@endphp
@if($src)
    <figure class="overflow-hidden rounded-3xl border border-white/10 bg-zinc-950 shadow-xl shadow-black/25">
        <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" width="1200" height="675" class="h-auto w-full object-cover">
        @if($cap !== '')
            <figcaption class="border-t border-white/10 bg-white/[0.03] px-5 py-3 text-sm text-zinc-400">{{ $cap }}</figcaption>
        @endif
    </figure>
@endif
