@props([
    'license' => null,        // App\Models\License | array (snapshot) | null
    'snapshot' => null,       // optional pre-baked array (e.g. from order_items.license_snapshot)
    'size' => 'md',           // sm | md | lg
    'tooltip' => true,
    'showIcon' => true,
])

@php
    /**
     * Resolve the data we need to render. We support either an Eloquent License
     * instance (preferred) or a raw array (used for purchased-license snapshots).
     */
    $data = null;

    if ($snapshot && is_array($snapshot)) {
        $data = $snapshot;
    } elseif ($license instanceof \App\Models\License) {
        $data = [
            'label' => $license->badgeLabel(),
            'description' => (string) $license->localized('description'),
            'badge_color' => $license->badgeColor(),
            'icon_slug' => $license->iconSlug(),
        ];
    } elseif (is_array($license)) {
        $data = $license;
    }

    if (! $data) {
        return;
    }

    $label = $data['label'] ?? ($data['badge_label'] ?? '');
    if ($label === '' || $label === null) {
        $name = $data['name'] ?? null;
        $label = is_array($name) ? ($name[app()->getLocale()] ?? $name['uk'] ?? $name['en'] ?? '') : (string) $name;
    }
    $description = (string) ($data['description_text'] ?? (
        is_array($data['description'] ?? null)
            ? ($data['description'][app()->getLocale()] ?? $data['description']['uk'] ?? $data['description']['en'] ?? '')
            : ($data['description'] ?? '')
    ));
    $color = $data['badge_color'] ?? 'emerald';
    $iconSlug = $data['icon_slug'] ?? 'shield';

    $palette = [
        'emerald'  => 'border-emerald-300/30 bg-emerald-300/[0.10] text-emerald-100',
        'sky'      => 'border-sky-300/30 bg-sky-300/[0.10] text-sky-100',
        'violet'   => 'border-violet-300/30 bg-violet-300/[0.10] text-violet-100',
        'amber'    => 'border-amber-300/30 bg-amber-300/[0.10] text-amber-100',
        'rose'     => 'border-rose-300/30 bg-rose-300/[0.10] text-rose-100',
        'fuchsia'  => 'border-fuchsia-300/30 bg-fuchsia-300/[0.10] text-fuchsia-100',
        'zinc'     => 'border-zinc-300/30 bg-zinc-300/[0.06] text-zinc-200',
        'cyan'     => 'border-cyan-300/30 bg-cyan-300/[0.10] text-cyan-100',
        'lime'     => 'border-lime-300/30 bg-lime-300/[0.10] text-lime-100',
    ];
    $tone = $palette[$color] ?? $palette['emerald'];

    $sizes = [
        'sm' => 'h-6 px-2 text-[10px]',
        'md' => 'h-7 px-2.5 text-[11px]',
        'lg' => 'h-8 px-3 text-xs',
    ];
    $sizeCls = $sizes[$size] ?? $sizes['md'];

    $iconSize = $size === 'lg' ? 'h-3.5 w-3.5' : ($size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5');
@endphp

<span
    {{ $attributes->merge([
        'class' => "group/license relative inline-flex items-center gap-1.5 rounded-full border font-bold uppercase tracking-wider {$tone} {$sizeCls}",
    ]) }}
>
    @if($showIcon)
        <x-license-icons :name="$iconSlug" :class="$iconSize" />
    @endif
    <span class="whitespace-nowrap">{{ $label }}</span>

    @if($tooltip && $description !== '')
        <span class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 w-64 -translate-x-1/2 rounded-xl border border-white/10 bg-zinc-950/95 p-3 text-[11px] font-normal normal-case leading-5 text-zinc-200 opacity-0 shadow-2xl shadow-black/40 backdrop-blur transition-opacity duration-150 group-hover/license:opacity-100">
            {{ $description }}
            <span class="absolute -top-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 border-l border-t border-white/10 bg-zinc-950"></span>
        </span>
    @endif
</span>
