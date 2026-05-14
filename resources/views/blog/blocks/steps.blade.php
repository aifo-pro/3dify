@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $steps = is_array($d['steps'] ?? null) ? $d['steps'] : [];
@endphp
@if($title !== '' || $steps !== [])
    <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-6 sm:p-8">
        @if($title !== '')
            <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
        @endif
        @if($steps !== [])
            <ol class="mt-6 space-y-5">
                @foreach($steps as $si => $st)
                    @if(is_array($st))
                        @php
                            $stTitle = $locale === 'en'
                                ? (trim((string) ($st['title_en'] ?? '')) ?: trim((string) ($st['title_uk'] ?? '')))
                                : (trim((string) ($st['title_uk'] ?? '')) ?: trim((string) ($st['title_en'] ?? '')));
                            $stText = $locale === 'en'
                                ? (trim((string) ($st['text_en'] ?? '')) ?: trim((string) ($st['text_uk'] ?? '')))
                                : (trim((string) ($st['text_uk'] ?? '')) ?: trim((string) ($st['text_en'] ?? '')));
                        @endphp
                        @if($stTitle !== '' || $stText !== '')
                            <li class="flex gap-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-emerald-400/35 bg-emerald-400/15 text-sm font-black text-emerald-200">{{ $si + 1 }}</span>
                                <div class="min-w-0 flex-1 space-y-2 border-b border-white/[0.06] pb-5 last:border-0 last:pb-0">
                                    @if($stTitle !== '')
                                        <p class="text-base font-bold text-white">{{ $stTitle }}</p>
                                    @endif
                                    @if($stText !== '')
                                        <div class="prose prose-invert prose-sm prose-emerald max-w-none text-zinc-300 prose-p:my-1">{!! $stText !!}</div>
                                    @endif
                                </div>
                            </li>
                        @endif
                    @endif
                @endforeach
            </ol>
        @endif
    </div>
@endif
