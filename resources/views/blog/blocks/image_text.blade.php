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
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $body = $locale === 'en'
        ? (trim((string) ($d['text_en'] ?? '')) ?: trim((string) ($d['text_uk'] ?? '')))
        : (trim((string) ($d['text_uk'] ?? '')) ?: trim((string) ($d['text_en'] ?? '')));
    $pos = ($d['image_position'] ?? 'left') === 'right' ? 'right' : 'left';
    $imgFirst = $pos === 'left';
@endphp
@if($src || $title !== '' || $body !== '')
    <div class="grid gap-7 rounded-[1.75rem] border border-white/[0.07] bg-gradient-to-br from-white/[0.05] to-white/[0.02] p-7 sm:gap-8 sm:p-9 lg:grid-cols-2 lg:items-center">
        @if($src)
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-zinc-950 {{ $imgFirst ? '' : 'lg:order-2' }}">
                <img src="{{ $src }}" alt="{{ $title }}" loading="lazy" width="800" height="520" class="h-full w-full object-cover">
            </div>
        @endif
        <div class="min-w-0 space-y-4 {{ $src && ! $imgFirst ? 'lg:order-1' : '' }}">
            @if($title !== '')
                <h3 class="text-xl font-bold tracking-tight text-white sm:text-2xl">{{ $title }}</h3>
            @endif
            @if($body !== '')
                <div class="prose prose-lg prose-invert prose-emerald max-w-none text-zinc-200 leading-[1.82] prose-p:leading-[1.82] prose-a:text-emerald-300">
                    {!! $body !!}
                </div>
            @endif
        </div>
    </div>
@endif
