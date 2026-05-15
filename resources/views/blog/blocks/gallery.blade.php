@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $style = $d['style'] ?? 'grid';
    $images = is_array($d['images'] ?? null)
        ? array_filter($d['images'], fn ($i) => is_array($i) && trim((string) ($i['path'] ?? '')) !== '')
        : [];
    $images = array_values($images);
@endphp
@if($images !== [])
    <figure class="space-y-3">
        @if($title !== '')
            <figcaption class="text-sm font-semibold text-zinc-400">{{ $title }}</figcaption>
        @endif
        <div @class([
            'gap-2',
            'columns-2 sm:columns-3' => $style === 'masonry',
            'grid grid-cols-2 sm:grid-cols-3' => $style !== 'masonry',
        ])>
            @foreach($images as $img)
                @php
                    $src = Storage::disk('public')->url(ltrim($img['path'], '/'));
                    $src = str_starts_with($src, 'http') ? $src : url($src);
                    $alt = $locale === 'en'
                        ? (trim((string) ($img['alt_en'] ?? '')) ?: trim((string) ($img['alt_uk'] ?? '')))
                        : (trim((string) ($img['alt_uk'] ?? '')) ?: trim((string) ($img['alt_en'] ?? '')));
                @endphp
                <div @class([
                    'overflow-hidden rounded-2xl border border-white/10 bg-zinc-950',
                    'mb-2 break-inside-avoid' => $style === 'masonry',
                ])>
                    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy"
                         class="w-full object-cover transition hover:scale-[1.02] {{ $style !== 'masonry' ? 'aspect-square' : '' }}">
                </div>
            @endforeach
        </div>
    </figure>
@endif
