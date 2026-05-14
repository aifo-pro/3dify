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
    <div class="overflow-hidden rounded-3xl border border-white/10 bg-zinc-950/60 shadow-lg shadow-black/20">
        @if($title !== '')
            <div class="border-b border-white/10 px-5 py-4">
                <h3 class="text-lg font-bold text-white">{{ $title }}</h3>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-left text-sm text-zinc-300">
                @if($headers !== [])
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.04]">
                            @foreach($headers as $h)
                                <th class="whitespace-nowrap px-4 py-3 text-xs font-bold uppercase tracking-wider text-emerald-200/90">{{ is_string($h) ? strip_tags($h) : '' }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach($rows as $row)
                        @if(is_array($row))
                            <tr class="border-b border-white/[0.06] last:border-0">
                                @foreach($row as $cell)
                                    <td class="px-4 py-3 align-top text-zinc-300">{!! is_string($cell) ? $cell : '' !!}</td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
