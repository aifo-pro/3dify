@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $headers = is_array($d['headers'] ?? null) ? $d['headers'] : [];
    $rows = is_array($d['rows'] ?? null) ? $d['rows'] : [];
@endphp
@if($title !== '' || $headers !== [] || $rows !== [])
    <div class="overflow-hidden rounded-[1.75rem] border border-white/[0.08] bg-zinc-950/60 shadow-lg shadow-black/20">
        @if($title !== '')
            <div class="border-b border-white/10 px-6 py-4">
                <h3 class="text-xl font-bold tracking-tight text-white">{{ $title }}</h3>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-left text-base text-zinc-200">
                @if($headers !== [])
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.04]">
                            @foreach($headers as $h)
                                <th class="whitespace-nowrap px-4 py-3.5 text-[11px] font-bold uppercase tracking-wider text-emerald-200/90">{{ is_string($h) ? strip_tags($h) : '' }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach($rows as $row)
                        @if(is_array($row))
                            <tr class="border-b border-white/[0.06] last:border-0">
                                @foreach($row as $cell)
                                    <td class="px-4 py-3.5 align-top leading-relaxed text-zinc-200">{!! is_string($cell) ? $cell : '' !!}</td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
