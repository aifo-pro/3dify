@props([
    'data' => [],
    'color' => 'emerald',
    'height' => 28,
    'width' => 96,
    'strokeWidth' => 1.6,
])

@php
    $points = is_array($data) ? array_values($data) : [];
    $count = count($points);

    $colors = [
        'emerald' => ['stroke' => '#34d399', 'fill' => 'rgba(52,211,153,0.18)'],
        'sky' => ['stroke' => '#38bdf8', 'fill' => 'rgba(56,189,248,0.18)'],
        'amber' => ['stroke' => '#fbbf24', 'fill' => 'rgba(251,191,36,0.18)'],
        'violet' => ['stroke' => '#a78bfa', 'fill' => 'rgba(167,139,250,0.18)'],
        'rose' => ['stroke' => '#fb7185', 'fill' => 'rgba(251,113,133,0.18)'],
        'zinc' => ['stroke' => '#a1a1aa', 'fill' => 'rgba(161,161,170,0.18)'],
    ];

    $palette = $colors[$color] ?? $colors['emerald'];
    $w = (int) $width;
    $h = (int) $height;

    if ($count < 2) {
        $linePath = null;
        $areaPath = null;
    } else {
        $max = max($points) ?: 1;
        $min = min($points);
        $range = max($max - $min, 1);
        $stepX = $w / max($count - 1, 1);

        $coords = [];
        foreach ($points as $i => $v) {
            $x = round($i * $stepX, 2);
            $y = round($h - 2 - (($v - $min) / $range) * ($h - 4), 2);
            $coords[] = [$x, $y];
        }

        $linePath = 'M '.implode(' L ', array_map(fn ($p) => $p[0].','.$p[1], $coords));
        $areaPath = $linePath.' L '.$coords[count($coords) - 1][0].','.$h.' L '.$coords[0][0].','.$h.' Z';
    }
@endphp

<svg
    {{ $attributes->merge(['class' => 'block']) }}
    viewBox="0 0 {{ $w }} {{ $h }}"
    width="{{ $w }}"
    height="{{ $h }}"
    preserveAspectRatio="none"
    aria-hidden="true"
>
    @if($linePath)
        <path d="{{ $areaPath }}" fill="{{ $palette['fill'] }}" stroke="none"/>
        <path d="{{ $linePath }}" fill="none" stroke="{{ $palette['stroke'] }}" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round"/>
    @else
        <line x1="0" y1="{{ $h / 2 }}" x2="{{ $w }}" y2="{{ $h / 2 }}" stroke="{{ $palette['stroke'] }}" stroke-width="{{ $strokeWidth }}" stroke-dasharray="2,3" stroke-linecap="round"/>
    @endif
</svg>
