@props(['label' => null, 'error' => null])

<label class="grid gap-2 text-sm font-medium text-zinc-200">
    @if($label)
        <span>{{ $label }}</span>
    @endif
    <input {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-white placeholder:text-zinc-500 shadow-inner shadow-black/20 transition focus:border-emerald-300 focus:ring-emerald-300']) }}>
    @if($error)
        <span class="text-xs text-red-300">{{ $error }}</span>
    @endif
</label>
