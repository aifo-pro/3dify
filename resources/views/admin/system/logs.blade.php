@php
    $fmt = function (int $bytes): string {
        $u = ['B','KB','MB','GB']; $i = 0;
        while ($bytes > 1024 && $i < 3) { $bytes /= 1024; $i++; }
        return number_format($bytes, $i ? 1 : 0).' '.$u[$i];
    };
    $highlight = function ($line) {
        if (preg_match('/ERROR|EMERGENCY|CRITICAL/i', $line)) return 'text-rose-300';
        if (preg_match('/WARNING/i', $line)) return 'text-amber-300';
        if (preg_match('/INFO/i', $line)) return 'text-sky-300';
        if (preg_match('/DEBUG/i', $line)) return 'text-zinc-500';
        return 'text-zinc-400';
    };
@endphp
<x-layouts.admin
    :title="__('Логи Laravel')"
    breadcrumb-current="{{ __('Логи') }}"
    active="system"
>
    <div class="grid gap-4 lg:grid-cols-[260px_1fr]">
        <aside class="rounded-2xl border border-white/10 bg-white/[0.04] p-3">
            <p class="mb-2 px-2 text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Файли') }}</p>
            @if(empty($logFiles))
                <p class="px-2 py-4 text-xs text-zinc-500">{{ __('Файлів не знайдено.') }}</p>
            @else
                <ul class="space-y-1">
                    @foreach($logFiles as $f)
                        @php $active = $f['name'] === $selected; @endphp
                        <li>
                            <a href="{{ route('admin.system.logs', ['file' => $f['name'], 'lines' => $lines]) }}" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-xs transition {{ $active ? 'bg-emerald-300/[0.12] text-emerald-100' : 'text-zinc-300 hover:bg-white/[0.06]' }}">
                                <span class="truncate font-mono">{{ $f['name'] }}</span>
                                <span class="text-[10px] text-zinc-500">{{ $fmt($f['size']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </aside>

        <section class="min-w-0 rounded-2xl border border-white/10 bg-white/[0.04]">
            <div class="flex flex-wrap items-center gap-2 border-b border-white/5 p-3">
                <p class="font-mono text-xs text-zinc-200">{{ $selected }}</p>
                <form method="GET" class="ml-auto flex items-center gap-2">
                    <input type="hidden" name="file" value="{{ $selected }}">
                    <label class="text-[11px] text-zinc-500">{{ __('останніх рядків') }}</label>
                    <select name="lines" onchange="this.form.submit()" class="h-8 rounded-lg border border-white/10 bg-zinc-950/60 px-2 text-xs text-white">
                        @foreach([100, 200, 500, 1000, 2000] as $n)
                            <option value="{{ $n }}" @selected($lines === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="max-h-[70vh] overflow-y-auto bg-zinc-950/60 p-3 font-mono text-[11px] leading-5">@if(empty($tail))<span class="text-zinc-500">{{ __('Файл порожній або недоступний.') }}</span>@else
@foreach(preg_split('/\n/', $tail) as $line)<div class="{{ $highlight($line) }} whitespace-pre-wrap break-all">{{ $line === '' ? ' ' : $line }}</div>
@endforeach
@endif</div>
        </section>
    </div>
</x-layouts.admin>
