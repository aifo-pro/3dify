@props([
    'product',
    'selected' => 'personal', // initial selection (personal | commercial)
])

@php
    $personalLicense = $product->license;
    $commercialLicense = $product->commercialLicense ?? $product->license;
    $hasCommercial = (bool) $product->commercial_license_enabled;

    $personalPrice = $product->personalPrice();
    $commercialPrice = $product->commercialPrice();
    $currency = $product->currency ?? 'EUR';

    $personalDescription = is_array($product->commercial_license_description ?? null) ? null : null;
    $commercialDescriptionLocale = null;
    if (is_array($product->commercial_license_description ?? null)) {
        $commercialDescriptionLocale = $product->commercial_license_description[app()->getLocale()] ?? $product->commercial_license_description['uk'] ?? '';
    }
@endphp

<div
    x-data="{ choice: @js($selected) }"
    x-init="$watch('choice', v => $dispatch('license-changed', { type: v }))"
    class="grid gap-3 sm:grid-cols-2"
>
    {{-- PERSONAL --}}
    <label class="relative flex cursor-pointer flex-col rounded-3xl border bg-white/[0.04] p-5 transition"
        :class="choice === 'personal'
            ? 'border-emerald-300/50 bg-emerald-300/[0.07] shadow-lg shadow-emerald-500/10'
            : 'border-white/10 hover:border-white/20'">
        <input type="radio" name="license_type" value="personal" x-model="choice" class="sr-only" {{ $selected === 'personal' ? 'checked' : '' }}>

        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-sky-300">{{ __('Особиста') }}</p>
                <p class="mt-1 text-base font-bold text-white">{{ __('Personal license') }}</p>
            </div>
            <span x-show="choice === 'personal'" class="grid h-6 w-6 place-items-center rounded-full bg-emerald-400 text-zinc-950">
                <x-license-icons name="check" class="h-3.5 w-3.5" />
            </span>
            <span x-show="choice !== 'personal'" class="grid h-6 w-6 place-items-center rounded-full border border-white/10"></span>
        </div>

        <p class="mt-3 text-2xl font-black text-white">
            @if($personalPrice <= 0)
                {{ __('Безкоштовно') }}
            @else
                {{ number_format($personalPrice, 2) }} {{ $currency }}
            @endif
        </p>

        <p class="mt-2 text-xs leading-5 text-zinc-400">
            {{ __('Для особистого друку без подальшого продажу файла.') }}
        </p>

        @if($personalLicense)
            <div class="mt-4">
                <x-license-badge :license="$personalLicense" size="sm" :tooltip="false" />
            </div>
        @endif

        <ul class="mt-4 grid gap-1.5 text-[11px] text-zinc-400">
            <li class="flex items-center gap-2"><x-license-icons name="check" class="h-3 w-3 text-emerald-300" /> {{ __('Особисте використання') }}</li>
            <li class="flex items-center gap-2"><x-license-icons name="cross" class="h-3 w-3 text-rose-300" /> {{ __('Без продажу надрукованих копій') }}</li>
        </ul>
    </label>

    {{-- COMMERCIAL --}}
    @if($hasCommercial)
        <label class="relative flex cursor-pointer flex-col rounded-3xl border bg-white/[0.04] p-5 transition"
            :class="choice === 'commercial'
                ? 'border-emerald-300/50 bg-emerald-300/[0.07] shadow-lg shadow-emerald-500/10'
                : 'border-white/10 hover:border-white/20'">
            <input type="radio" name="license_type" value="commercial" x-model="choice" class="sr-only" {{ $selected === 'commercial' ? 'checked' : '' }}>

            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-300">{{ __('Комерційна') }}</p>
                    <p class="mt-1 text-base font-bold text-white">{{ __('Commercial license') }}</p>
                </div>
                <span x-show="choice === 'commercial'" class="grid h-6 w-6 place-items-center rounded-full bg-emerald-400 text-zinc-950">
                    <x-license-icons name="check" class="h-3.5 w-3.5" />
                </span>
                <span x-show="choice !== 'commercial'" class="grid h-6 w-6 place-items-center rounded-full border border-white/10"></span>
            </div>

            <p class="mt-3 text-2xl font-black text-white">
                {{ number_format($commercialPrice, 2) }} {{ $currency }}
            </p>

            <p class="mt-2 text-xs leading-5 text-zinc-400">
                {{ $commercialDescriptionLocale ?: __('Дозволяє продавати надруковані копії моделі та використовувати у комерційних проєктах.') }}
            </p>

            @if($commercialLicense)
                <div class="mt-4">
                    <x-license-badge :license="$commercialLicense" size="sm" :tooltip="false" />
                </div>
            @endif

            <ul class="mt-4 grid gap-1.5 text-[11px] text-zinc-400">
                <li class="flex items-center gap-2"><x-license-icons name="check" class="h-3 w-3 text-emerald-300" /> {{ __('Особисте використання') }}</li>
                <li class="flex items-center gap-2"><x-license-icons name="check" class="h-3 w-3 text-emerald-300" /> {{ __('Продаж надрукованих копій') }}</li>
                <li class="flex items-center gap-2"><x-license-icons name="cross" class="h-3 w-3 text-rose-300" /> {{ __('Без перепродажу самого файла') }}</li>
            </ul>
        </label>
    @endif
</div>
