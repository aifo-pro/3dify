@props([
    /** Raw status key, e.g. 'published', 'paid', 'pending'. Looked up in catalog below. */
    'status' => 'neutral',
    /** Optional explicit type to disambiguate (product/order/payout/...). */
    'type' => null,
    /** Visual size: 'sm' (default), 'xs', 'md'. */
    'size' => 'sm',
    /** Override label text (otherwise translated label is rendered). */
    'label' => null,
    /** Show the leading icon (default true). Set false for ultra-compact. */
    'icon' => true,
    /** Hide pulse animation on transient states like pending/processing. */
    'pulse' => true,
])

@php
    $key = (string) ($status ?? 'neutral');

    /**
     * Centralised status catalogue.
     * Each entry: ['label' => __('...'), 'tone' => 'emerald|amber|sky|rose|violet|zinc|orange', 'icon' => 'check|clock|x|...', 'transient' => bool]
     */
    $catalog = [
        // -------- Products / generic content --------
        'published'   => ['label' => __('Опубліковано'),  'tone' => 'emerald', 'icon' => 'check'],
        'draft'       => ['label' => __('Чернетка'),       'tone' => 'zinc',    'icon' => 'edit'],
        'pending'     => ['label' => __('На модерації'),   'tone' => 'amber',   'icon' => 'clock', 'transient' => true],
        'rejected'    => ['label' => __('Відхилено'),      'tone' => 'rose',    'icon' => 'x'],
        'archived'    => ['label' => __('В архіві'),       'tone' => 'zinc',    'icon' => 'archive'],
        'hidden'      => ['label' => __('Сховано'),        'tone' => 'zinc',    'icon' => 'eye-off'],

        // -------- Orders --------
        'created'     => ['label' => __('Створено'),       'tone' => 'sky',     'icon' => 'plus'],
        'paid'        => ['label' => __('Оплачено'),       'tone' => 'emerald', 'icon' => 'check'],
        'cancelled'   => ['label' => __('Скасовано'),      'tone' => 'zinc',    'icon' => 'x'],
        'refunded'    => ['label' => __('Повернено'),      'tone' => 'orange',  'icon' => 'rotate'],

        // -------- Payouts --------
        'approved'    => ['label' => __('Підтверджено'),   'tone' => 'sky',     'icon' => 'check'],
        'processing'  => ['label' => __('В обробці'),      'tone' => 'sky',     'icon' => 'spinner', 'transient' => true],

        // -------- Refunds / reports --------
        'reviewing'   => ['label' => __('Перевірка'),      'tone' => 'amber',   'icon' => 'eye',     'transient' => true],
        'reviewed'    => ['label' => __('Переглянуто'),    'tone' => 'sky',     'icon' => 'check'],
        'resolved'    => ['label' => __('Вирішено'),       'tone' => 'emerald', 'icon' => 'check'],
        'dismissed'   => ['label' => __('Відхилено'),      'tone' => 'zinc',    'icon' => 'x'],
        'actioned'    => ['label' => __('Вжито заходів'),  'tone' => 'rose',    'icon' => 'flag'],

        // -------- Users --------
        'active'      => ['label' => __('Активний'),       'tone' => 'emerald', 'icon' => 'check'],
        'suspended'   => ['label' => __('Заблокований'),   'tone' => 'rose',    'icon' => 'lock'],
        'unverified'  => ['label' => __('Не підтверджено'),'tone' => 'amber',   'icon' => 'mail'],
        'verified'    => ['label' => __('Підтверджено'),   'tone' => 'emerald', 'icon' => 'shield'],

        // -------- Tips --------
        'unsubscribed'=> ['label' => __('Відписався'),     'tone' => 'zinc',    'icon' => 'x'],

        // -------- Generic fallbacks --------
        'success'     => ['label' => __('Успіх'),          'tone' => 'emerald', 'icon' => 'check'],
        'warning'     => ['label' => __('Увага'),          'tone' => 'amber',   'icon' => 'alert'],
        'error'       => ['label' => __('Помилка'),        'tone' => 'rose',    'icon' => 'x'],
        'failed'      => ['label' => __('Помилка'),        'tone' => 'rose',    'icon' => 'x'],
        'info'        => ['label' => __('Інфо'),           'tone' => 'sky',     'icon' => 'info'],
        'neutral'     => ['label' => '—',                  'tone' => 'zinc',    'icon' => null],
    ];

    $labelOverrides = [
        'paid' => __('Оплачено'),
        'refunded' => __('Повернуто'),
        'approved' => __('Підтверджено'),
        'pending' => __('Очікує'),
        'rejected' => __('Відхилено'),
    ];

    $entry = $catalog[$key] ?? ['label' => Str::headline($key), 'tone' => 'zinc', 'icon' => null];
    if (isset($labelOverrides[$key])) {
        $entry['label'] = $labelOverrides[$key];
    }

    $tones = [
        'emerald' => 'border-emerald-300/30 bg-emerald-300/[0.10] text-emerald-100 shadow-[inset_0_0_0_1px_rgba(16,185,129,0.05)]',
        'sky'     => 'border-sky-300/30 bg-sky-300/[0.10] text-sky-100 shadow-[inset_0_0_0_1px_rgba(56,189,248,0.05)]',
        'amber'   => 'border-amber-300/30 bg-amber-300/[0.10] text-amber-100 shadow-[inset_0_0_0_1px_rgba(245,158,11,0.05)]',
        'rose'    => 'border-rose-300/30 bg-rose-300/[0.10] text-rose-100 shadow-[inset_0_0_0_1px_rgba(244,63,94,0.05)]',
        'violet'  => 'border-violet-300/30 bg-violet-300/[0.10] text-violet-100 shadow-[inset_0_0_0_1px_rgba(167,139,250,0.05)]',
        'orange'  => 'border-orange-300/30 bg-orange-300/[0.10] text-orange-100 shadow-[inset_0_0_0_1px_rgba(251,146,60,0.05)]',
        'zinc'    => 'border-white/10 bg-white/[0.06] text-zinc-300',
    ];

    $dotTones = [
        'emerald' => 'bg-emerald-300',
        'sky'     => 'bg-sky-300',
        'amber'   => 'bg-amber-300',
        'rose'    => 'bg-rose-300',
        'violet'  => 'bg-violet-300',
        'orange'  => 'bg-orange-300',
        'zinc'    => 'bg-zinc-400',
    ];

    $sizes = [
        'xs' => 'h-5 px-2 text-[10px] gap-1',
        'sm' => 'h-6 px-2.5 text-[11px] gap-1.5',
        'md' => 'h-7 px-3 text-xs gap-1.5',
    ];
    $iconSizes = [
        'xs' => 'h-2.5 w-2.5',
        'sm' => 'h-3 w-3',
        'md' => 'h-3.5 w-3.5',
    ];
    $dotSizes = [
        'xs' => 'h-1 w-1',
        'sm' => 'h-1.5 w-1.5',
        'md' => 'h-2 w-2',
    ];

    $tone = $tones[$entry['tone']] ?? $tones['zinc'];
    $dot = $dotTones[$entry['tone']] ?? $dotTones['zinc'];
    $sizeCls = $sizes[$size] ?? $sizes['sm'];
    $iconCls = $iconSizes[$size] ?? $iconSizes['sm'];
    $dotCls = $dotSizes[$size] ?? $dotSizes['sm'];

    $isTransient = $entry['transient'] ?? false;
    $shouldPulse = $pulse && $isTransient;

    $iconKey = $icon ? ($entry['icon'] ?? null) : null;

    $iconSvg = match ($iconKey) {
        'check'   => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x'       => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'clock'   => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'edit'    => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',
        'archive' => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>',
        'eye-off' => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
        'plus'    => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'rotate'  => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>',
        'spinner' => '<svg class="'.$iconCls.' animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>',
        'eye'     => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'flag'    => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
        'lock'    => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'mail'    => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'shield'  => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'alert'   => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'info'    => '<svg class="'.$iconCls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        default   => null,
    };

    $displayLabel = $label ?? $entry['label'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border font-bold uppercase tracking-wider whitespace-nowrap '.$sizeCls.' '.$tone]) }}>
    @if($iconSvg)
        {!! $iconSvg !!}
    @else
        <span class="relative inline-flex items-center justify-center {{ $dotCls }}">
            <span class="absolute inset-0 rounded-full {{ $dot }} opacity-90"></span>
            @if($shouldPulse)
                <span class="absolute inset-0 rounded-full {{ $dot }} opacity-60 animate-ping"></span>
            @endif
        </span>
    @endif
    <span>{{ $displayLabel }}</span>
</span>
