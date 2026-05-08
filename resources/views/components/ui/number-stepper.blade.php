@props([
    'name' => null,
    'value' => 0,
    'min' => null,
    'max' => null,
    'step' => 1,
    'size' => 'md',
    'precision' => null,
])

@php
    $sizes = [
        'sm' => ['wrap' => 'h-9', 'btn' => 'w-8 text-base', 'input' => 'text-sm'],
        'md' => ['wrap' => 'h-10', 'btn' => 'w-9 text-lg', 'input' => 'text-sm'],
        'lg' => ['wrap' => 'h-12', 'btn' => 'w-11 text-xl', 'input' => 'text-base'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
    $stepFloat = (float) $step;
    $precisionAttr = $precision !== null ? (int) $precision : (str_contains((string) $step, '.') ? strlen(substr(strrchr((string) $step, '.'), 1)) : 0);
    $minAttr = $min !== null ? (float) $min : null;
    $maxAttr = $max !== null ? (float) $max : null;
    $alpine = sprintf(
        "{ value: %s, step: %s, precision: %d, min: %s, max: %s, format(v) { return Number.isFinite(v) ? Number(v).toFixed(this.precision) : ''; }, clamp(v) { let n = Number(v); if (!Number.isFinite(n)) n = this.min ?? 0; if (this.min !== null && n < this.min) n = this.min; if (this.max !== null && n > this.max) n = this.max; return n; }, sync() { this.value = this.format(this.clamp(this.value)); }, inc() { this.value = this.format(this.clamp((Number(this.value) || 0) + this.step)); }, dec() { this.value = this.format(this.clamp((Number(this.value) || 0) - this.step)); } }",
        json_encode((string) $value),
        json_encode($stepFloat),
        $precisionAttr,
        $minAttr === null ? 'null' : json_encode($minAttr),
        $maxAttr === null ? 'null' : json_encode($maxAttr),
    );
@endphp

<div
    x-data="{{ $alpine }}"
    x-init="value = format(clamp(value))"
    {{ $attributes->only(['class'])->merge(['class' => 'inline-flex items-stretch overflow-hidden rounded-xl border border-white/10 bg-zinc-950/60 focus-within:border-emerald-300 focus-within:ring-1 focus-within:ring-emerald-300/40 '.$s['wrap']]) }}
>
    <button
        type="button"
        @click="dec()"
        :disabled="min !== null && Number(value) <= min"
        class="grid shrink-0 place-items-center text-zinc-300 transition hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:text-zinc-600 disabled:hover:bg-transparent {{ $s['btn'] }}"
        aria-label="{{ __('Зменшити') }}"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>

    <input
        type="number"
        @if($name) name="{{ $name }}" @endif
        x-model.number="value"
        @blur="sync()"
        @keydown.enter.prevent="sync()"
        @if($min !== null) min="{{ $min }}" @endif
        @if($max !== null) max="{{ $max }}" @endif
        step="{{ $step }}"
        inputmode="decimal"
        {{ $attributes->except(['class'])->merge(['class' => 'min-w-0 flex-1 bg-transparent px-2 text-center font-mono font-semibold tabular-nums text-white placeholder:text-zinc-500 focus:outline-none '.$s['input']]) }}
    >

    <button
        type="button"
        @click="inc()"
        :disabled="max !== null && Number(value) >= max"
        class="grid shrink-0 place-items-center border-l border-white/10 text-zinc-300 transition hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:text-zinc-600 disabled:hover:bg-transparent {{ $s['btn'] }}"
        aria-label="{{ __('Збільшити') }}"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
</div>
