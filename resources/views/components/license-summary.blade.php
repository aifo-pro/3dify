@props([
    'license' => null,
    'snapshot' => null,
    'product' => null,
    'compact' => false,
])

@php
    /**
     * Normalize input into a plain `$facts` array regardless of whether we got
     * an Eloquent License, a snapshot array, or nothing at all.
     */
    $facts = null;

    if ($snapshot && is_array($snapshot)) {
        $facts = $snapshot;
    } elseif ($license instanceof \App\Models\License) {
        $facts = $license->toSnapshot();
        $facts['description_text'] = (string) $license->localized('description');
    } elseif (is_array($license)) {
        $facts = $license;
    }

    if (! $facts) {
        return;
    }

    $title = $facts['label'] ?? $facts['badge_label'] ?? '';
    if ($title === '' || $title === null) {
        $title = is_array($facts['name'] ?? null)
            ? ($facts['name'][app()->getLocale()] ?? $facts['name']['uk'] ?? $facts['name']['en'] ?? '')
            : (string) ($facts['name'] ?? '');
    }
    $desc = $facts['description_text'] ?? (is_array($facts['description'] ?? null)
        ? ($facts['description'][app()->getLocale()] ?? $facts['description']['uk'] ?? $facts['description']['en'] ?? '')
        : (string) ($facts['description'] ?? ''));

    $iconSlug = $facts['icon_slug'] ?? 'shield';

    /**
     * Each row: [icon-tone, allowed?, label, optional helper text].
     * `null` for `allowed` skips rendering the row.
     */
    $rows = [
        [
            'allowed' => true,
            'label'   => __('Особисте використання'),
            'help'    => __('Друк для себе, друзів та родини.'),
        ],
        [
            'allowed' => (bool) ($facts['allows_commercial_use'] ?? false),
            'label'   => __('Комерційне використання'),
            'help'    => ($facts['allows_commercial_use'] ?? false)
                ? __('Дозволено заробляти на надрукованих копіях.')
                : __('Заборонено використовувати модель у комерційних цілях.'),
        ],
        [
            'allowed' => (bool) ($facts['allows_selling_prints'] ?? false),
            'label'   => __('Продаж надрукованих копій'),
            'help'    => ($facts['allows_selling_prints'] ?? false)
                ? __('Можна друкувати та продавати фізичні вироби.')
                : __('Продаж надрукованих виробів заборонено.'),
        ],
        [
            'allowed' => ! (bool) ($facts['requires_attribution'] ?? false),
            'inverse' => true,
            'label'   => __('Атрибуція автора'),
            'help'    => ($facts['requires_attribution'] ?? false)
                ? __('Обовʼязково вказувати ім’я автора при показі/розповсюдженні.')
                : __('Атрибуція не вимагається.'),
        ],
        [
            'allowed' => ! (bool) ($facts['forbids_file_resale'] ?? true),
            'inverse' => true,
            'label'   => __('Перепродаж STL/OBJ файлів'),
            'help'    => __('Перепродаж самого файла моделі заборонено будь-якій іншій особі.'),
        ],
        [
            'allowed' => (bool) ($facts['allows_redistribution'] ?? false),
            'label'   => __('Перезавантаження на інші сайти'),
            'help'    => ($facts['allows_redistribution'] ?? false)
                ? __('Дозволено публікувати модель на інших платформах.')
                : __('Розповсюдження на сторонніх сайтах заборонено.'),
        ],
        [
            'allowed' => (bool) ($facts['allows_remix'] ?? true),
            'label'   => __('Ремікс / модифікація'),
            'help'    => ($facts['allows_remix'] ?? true)
                ? __('Дозволено модифікувати, поєднувати з іншими моделями.')
                : __('Зміни моделі заборонено.'),
        ],
    ];
@endphp

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-emerald-300/[0.05] via-white/[0.02] to-zinc-950/40']) }}>
    <header class="flex items-start gap-3 border-b border-white/5 bg-zinc-950/40 px-5 py-4">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-300/15 text-emerald-200">
            <x-license-icons :name="$iconSlug" class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-300">{{ __('Умови ліцензії') }}</p>
            <h3 class="mt-0.5 text-base font-bold text-white">{{ $title }}</h3>
            @if($desc !== '' && ! $compact)
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ $desc }}</p>
            @endif
        </div>
        <x-license-badge :license="$license" :snapshot="$snapshot" size="sm" :tooltip="false" />
    </header>

    <div class="divide-y divide-white/5">
        @foreach($rows as $row)
            @php
                $isAllowed = (bool) $row['allowed'];
                $iconName = $isAllowed ? 'check' : 'cross';
                $tone = $isAllowed ? 'text-emerald-300' : 'text-rose-300';
                $textTone = $isAllowed ? 'text-white' : 'text-zinc-400';
                $bgTone = $isAllowed ? 'bg-emerald-300/[0.06]' : 'bg-rose-300/[0.05]';
            @endphp
            <div class="flex items-start gap-3 px-5 py-3 transition hover:bg-white/[0.02]">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg border border-white/10 {{ $bgTone }} {{ $tone }}">
                    <x-license-icons :name="$iconName" class="h-3.5 w-3.5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold {{ $textTone }}">{{ $row['label'] }}</p>
                    @if(! $compact && ! empty($row['help']))
                        <p class="mt-0.5 text-[11px] leading-5 text-zinc-500">{{ $row['help'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($product && ($product->commercial_license_enabled ?? false))
        <footer class="border-t border-white/5 bg-zinc-950/40 px-5 py-3 text-[11px] leading-5 text-zinc-400">
            {{ __('Автор пропонує також комерційну ліцензію — оберіть її при оформленні замовлення.') }}
        </footer>
    @endif
</section>
