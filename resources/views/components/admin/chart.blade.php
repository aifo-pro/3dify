@props([
    'series' => [],
    'labels' => [],
    'height' => 220,
])

@php
    $w = 800;
    $h = (int) $height;
    $padX = 32;
    $padTop = 16;
    $padBottom = 26;

    $allValues = [];
    foreach ($series as $s) {
        $allValues = array_merge($allValues, array_values($s['data'] ?? []));
    }
    $maxVal = $allValues ? max($allValues) : 0;
    $maxScale = max($maxVal, 4);

    $count = $labels ? count($labels) : 0;

    $ticks = 4;
    $gridLines = [];
    for ($i = 0; $i <= $ticks; $i++) {
        $y = $padTop + (($h - $padTop - $padBottom) * $i / $ticks);
        $val = round($maxScale * (1 - $i / $ticks));
        $gridLines[] = ['y' => $y, 'val' => $val];
    }

    $colorMap = [
        'emerald' => '#34d399',
        'sky' => '#38bdf8',
        'amber' => '#fbbf24',
        'violet' => '#a78bfa',
        'rose' => '#fb7185',
    ];

    $buildPath = function (array $data) use ($w, $h, $padX, $padTop, $padBottom, $maxScale, $count) {
        if ($count < 2) return ['line' => null, 'area' => null];
        $stepX = ($w - 2 * $padX) / max($count - 1, 1);
        $coords = [];
        foreach (array_values($data) as $i => $v) {
            $x = round($padX + $i * $stepX, 2);
            $y = round($h - $padBottom - (($v / max($maxScale, 1)) * ($h - $padTop - $padBottom)), 2);
            $coords[] = [$x, $y];
        }
        $line = 'M '.implode(' L ', array_map(fn ($p) => $p[0].','.$p[1], $coords));
        $area = $line.' L '.$coords[count($coords) - 1][0].','.($h - $padBottom).' L '.$coords[0][0].','.($h - $padBottom).' Z';

        return ['line' => $line, 'area' => $area, 'coords' => $coords];
    };
@endphp

<div {{ $attributes }}>
    {{-- Legend --}}
    <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-2">
        @foreach($series as $s)
            <span class="inline-flex items-center gap-2 text-xs text-zinc-300">
                <span class="h-2 w-2 rounded-full" style="background:{{ $colorMap[$s['color'] ?? 'emerald'] ?? '#34d399' }}"></span>
                <span class="font-medium">{{ $s['label'] ?? '' }}</span>
                <span class="text-zinc-500">· {{ array_sum($s['data'] ?? []) }}</span>
            </span>
        @endforeach
    </div>

    <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="block w-full" style="height: {{ $h }}px">
        {{-- Grid --}}
        @foreach($gridLines as $g)
            <line x1="{{ $padX }}" y1="{{ $g['y'] }}" x2="{{ $w - $padX }}" y2="{{ $g['y'] }}" stroke="rgba(255,255,255,0.06)" stroke-width="1" stroke-dasharray="3,4"/>
            <text x="{{ $padX - 6 }}" y="{{ $g['y'] + 4 }}" text-anchor="end" fill="rgba(161,161,170,0.6)" font-size="10" font-family="ui-sans-serif, system-ui">{{ $g['val'] }}</text>
        @endforeach

        {{-- Series --}}
        @foreach($series as $idx => $s)
            @php
                $stroke = $colorMap[$s['color'] ?? 'emerald'] ?? '#34d399';
                $paths = $buildPath($s['data'] ?? []);
                $gradId = 'grad-'.uniqid().'-'.$idx;
            @endphp
            @if($paths['line'])
                <defs>
                    <linearGradient id="{{ $gradId }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $stroke }}" stop-opacity="0.35"/>
                        <stop offset="100%" stop-color="{{ $stroke }}" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <path d="{{ $paths['area'] }}" fill="url(#{{ $gradId }})"/>
                <path d="{{ $paths['line'] }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                @foreach($paths['coords'] as $p)
                    <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="2" fill="{{ $stroke }}" />
                @endforeach
            @endif
        @endforeach

        {{-- X labels (every Nth) --}}
        @if($count > 0)
            @php
                $stepX = ($w - 2 * $padX) / max($count - 1, 1);
                $skip = max(1, (int) ceil($count / 8));
            @endphp
            @foreach($labels as $i => $lbl)
                @if($i % $skip === 0 || $i === $count - 1)
                    <text x="{{ round($padX + $i * $stepX, 2) }}" y="{{ $h - 8 }}" text-anchor="middle" fill="rgba(161,161,170,0.6)" font-size="10" font-family="ui-sans-serif, system-ui">{{ $lbl }}</text>
                @endif
            @endforeach
        @endif
    </svg>
</div>
