@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $steps = is_array($d['steps'] ?? null) ? $d['steps'] : [];
@endphp
@if($title !== '' || $steps !== [])
    <div class="rounded-[1.75rem] border border-white/[0.08] bg-white/[0.04] p-7 sm:p-9">
        @if($title !== '')
            <h3 class="text-xl font-bold tracking-tight text-white">{{ $title }}</h3>
        @endif
        @if($steps !== [])
            <ol class="mt-7 space-y-6">
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
                                        <div class="prose prose-invert prose-base prose-emerald max-w-none text-zinc-200 leading-[1.78] prose-p:my-1 prose-a:text-emerald-300">{!! $stText !!}</div>
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
