@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-white placeholder:text-zinc-500 shadow-inner shadow-black/20 focus:border-emerald-300 focus:ring-emerald-300']) }}>
