<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/[0.08] px-5 py-3 text-sm font-bold text-white transition hover:bg-white/[0.12] focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
