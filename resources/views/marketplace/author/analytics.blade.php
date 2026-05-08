<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-wrap items-end justify-between gap-3">
            <div>
                <x-ui.badge>{{ __('Аналітика автора') }}</x-ui.badge>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('Статистика моделей') }}</h1>
                <p class="mt-2 text-zinc-400">{{ __('Перегляди, продажі та конверсія за останній період.') }}</p>
            </div>
            <div class="flex gap-1.5">
                @foreach([7 => '7д', 14 => '14д', 30 => '30д', 90 => '90д'] as $d => $label)
                    <a href="{{ route('author.analytics', ['days' => $d]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $days === $d ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </header>

        @php
            $deltaBadge = function ($delta) {
                if ($delta === null) return null;
                $sign = $delta > 0 ? '+' : '';
                $palette = $delta > 0 ? 'text-emerald-200 bg-emerald-300/[0.10] border-emerald-300/30'
                            : ($delta < 0 ? 'text-rose-200 bg-rose-300/[0.10] border-rose-300/30'
                                          : 'text-zinc-300 bg-white/[0.04] border-white/10');
                return ['text' => $sign.$delta.'%', 'palette' => $palette];
            };
        @endphp

        {{-- KPI cards --}}
        <div class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach([
                'views' => ['label' => __('Перегляди'), 'icon' => 'eye', 'fmt' => 'int'],
                'sales' => ['label' => __('Продажі'), 'icon' => 'bag', 'fmt' => 'int'],
                'revenue' => ['label' => __('Виторг (брутто)'), 'icon' => 'card', 'fmt' => 'eur'],
                'tips' => ['label' => __('Чайові'), 'icon' => 'heart', 'fmt' => 'eur'],
                'conversion' => ['label' => __('Конверсія'), 'icon' => 'percent', 'fmt' => 'pct'],
            ] as $k => $meta)
                @php
                    $kpi = $kpis[$k];
                    $delta = $deltaBadge($kpi['delta']);
                    $value = match($meta['fmt']) {
                        'eur' => number_format((float) $kpi['value'], 2).' €',
                        'pct' => number_format((float) $kpi['value'], 2).'%',
                        default => number_format((int) $kpi['value']),
                    };
                @endphp
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:border-white/20">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ $meta['label'] }}</p>
                    <p class="mt-2 text-2xl font-black text-white">{{ $value }}</p>
                    @if($delta)
                        <span class="mt-2 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $delta['palette'] }}">{{ $delta['text'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Chart card --}}
        @php
            $maxViews = max(1, max($series['views']));
            $maxRev = max(1, max($series['revenue']));
            $w = 1000; $h = 280; $pad = 32;
            $count = count($series['labels']);
            $stepX = $count > 1 ? ($w - $pad * 2) / ($count - 1) : 0;
            $pointsViews = '';
            $pointsRev = '';
            $barsHtml = '';
            foreach ($series['labels'] as $i => $label) {
                $x = $pad + $stepX * $i;
                $yV = $h - $pad - ($series['views'][$i] / $maxViews) * ($h - $pad * 2);
                $yR = $h - $pad - ($series['revenue'][$i] / $maxRev) * ($h - $pad * 2);
                $pointsViews .= sprintf('%.2f,%.2f ', $x, $yV);
                $pointsRev .= sprintf('%.2f,%.2f ', $x, $yR);
                if (($series['sales'][$i] ?? 0) > 0) {
                    $barH = ($series['sales'][$i] / max(1, max($series['sales']))) * ($h - $pad * 2) * 0.45;
                    $barsHtml .= sprintf('<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" rx="3" fill="rgba(251,191,36,0.45)"/>',
                        $x - 4, $h - $pad - $barH, 8, $barH);
                }
            }
        @endphp
        <div class="mb-8 rounded-3xl border border-white/10 bg-gradient-to-br from-white/[0.05] to-transparent p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-white">{{ __('Перегляди / Виторг / Продажі') }}</p>
                    <p class="text-xs text-zinc-500">{{ __('Період') }}: {{ $series['labels'][0] ?? '' }} — {{ end($series['labels']) ?: '' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px]">
                    <span class="inline-flex items-center gap-1.5 text-emerald-200"><span class="h-2 w-2 rounded-full bg-emerald-300"></span>{{ __('Перегляди') }}</span>
                    <span class="inline-flex items-center gap-1.5 text-sky-200"><span class="h-2 w-2 rounded-full bg-sky-300"></span>{{ __('Виторг') }}</span>
                    <span class="inline-flex items-center gap-1.5 text-amber-200"><span class="h-2 w-2 rounded-full bg-amber-300/70"></span>{{ __('Продажі') }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="h-72 w-full min-w-[640px]">
                    <defs>
                        <linearGradient id="aav" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="rgb(110,231,183)" stop-opacity="0.4"/>
                            <stop offset="100%" stop-color="rgb(110,231,183)" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    @for($g = 0; $g < 5; $g++)
                        <line x1="{{ $pad }}" x2="{{ $w - $pad }}" y1="{{ $pad + ($h - $pad * 2) * ($g / 4) }}" y2="{{ $pad + ($h - $pad * 2) * ($g / 4) }}" stroke="rgba(255,255,255,0.05)"/>
                    @endfor
                    {!! $barsHtml !!}
                    <polyline fill="none" stroke="rgb(110,231,183)" stroke-width="2.4" points="{{ trim($pointsViews) }}"/>
                    <polyline fill="url(#aav)" stroke="none" points="{{ $pad }},{{ $h - $pad }} {{ trim($pointsViews) }} {{ $w - $pad }},{{ $h - $pad }}"/>
                    <polyline fill="none" stroke="rgb(125,211,252)" stroke-width="2.4" stroke-dasharray="0" points="{{ trim($pointsRev) }}"/>
                </svg>
            </div>
            <div class="mt-2 flex flex-wrap gap-2 text-[10px] text-zinc-600">
                @foreach($series['labels'] as $i => $label)
                    @if($i % max(1, intdiv($count, 8)) === 0)
                        <span class="font-mono">{{ $label }}</span>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Top products table --}}
        <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-1">
            <div class="rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-6">
                <h2 class="mb-1 text-lg font-bold text-white">{{ __('Найкращі моделі') }}</h2>
                <p class="mb-5 text-xs text-zinc-500">{{ __('Сортовано за виторгом, далі за переглядами.') }}</p>

                <div class="overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/5 text-sm">
                        <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Модель') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Перегляди') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Продажі') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Виторг') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Конв.') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($top as $row)
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <a href="{{ route('products.show', $row->product) }}" class="text-sm font-semibold text-white hover:text-emerald-200">{{ $row->product->localized('title') }}</a>
                                        <p class="text-xs text-zinc-500">{{ $row->product->display_price }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-zinc-200">{{ number_format($row->views) }}</td>
                                    <td class="px-4 py-3 text-right text-zinc-200">{{ number_format($row->sales) }}</td>
                                    <td class="px-4 py-3 text-right text-zinc-200">{{ number_format($row->revenue, 2) }} €</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] font-bold text-zinc-300">{{ number_format($row->conversion, 2) }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Поки немає даних. Опублікуйте моделі — і тут з\'явиться статистика.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('author.products.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 text-sm text-zinc-300 transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.06] hover:text-white">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Усього моделей') }}</p>
                        <p class="mt-1 text-2xl font-black text-white">{{ $totalProducts }}</p>
                        <p class="text-xs text-zinc-500">{{ $publishedProducts }} {{ __('опубліковано') }}</p>
                    </a>
                    <a href="{{ route('author.payouts') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 text-sm text-zinc-300 transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.06] hover:text-white">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Виплати') }}</p>
                        <p class="mt-1 text-sm">{{ __('Перейти до балансу та запитів') }} →</p>
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketplace>
