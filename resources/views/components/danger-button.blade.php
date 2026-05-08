<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl border border-red-400/40 bg-red-500/15 px-5 py-3 text-sm font-bold text-red-100 transition hover:bg-red-500/25 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 focus:ring-offset-zinc-950']) }}>
    {{ $slot }}
</button>
