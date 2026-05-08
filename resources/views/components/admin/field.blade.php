@props([
    'label' => null,
    'helper' => null,
    'error' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'as' => 'input',
    'rows' => 3,
    'options' => [],
    'selected' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'autofocus' => false,
])

@php
    $base = 'w-full rounded-xl border bg-zinc-950/70 px-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40 disabled:opacity-50';
    $borderCls = $error ? 'border-rose-300/40 focus:border-rose-300' : 'border-white/10';
    $inputCls = $base.' '.$borderCls.' h-10';
    $textareaCls = $base.' '.$borderCls.' py-2 leading-6';
    $selectCls = $base.' '.$borderCls.' h-10 pr-8';
@endphp

<label class="grid gap-1.5">
    @if($label)
        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">
            {{ $label }}
            @if($required)<span class="text-rose-300">*</span>@endif
        </span>
    @endif

    @if($as === 'textarea')
        <textarea
            @if($name) name="{{ $name }}" @endif
            rows="{{ $rows }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($autofocus) autofocus @endif
            class="{{ $textareaCls }}"
            {{ $attributes }}
        >{{ $value ?? $slot }}</textarea>
    @elseif($as === 'select')
        <div class="relative">
            <select
                @if($name) name="{{ $name }}" @endif
                @if($required) required @endif
                class="{{ $selectCls }} appearance-none w-full"
                {{ $attributes }}
            >{{ $slot }}</select>
            <svg class="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.24 4.38a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </div>
    @else
        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" @endif
            @if($value !== null) value="{{ $value }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($autofocus) autofocus @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            class="{{ $inputCls }}"
            {{ $attributes }}
        >
    @endif

    @if($error)
        <span class="text-xs text-rose-300">{{ $error }}</span>
    @elseif($helper)
        <span class="text-xs leading-5 text-zinc-500">{{ $helper }}</span>
    @endif
</label>
