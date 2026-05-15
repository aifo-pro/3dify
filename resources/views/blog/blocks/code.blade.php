@php
    $d = $block->data ?? [];
    $lang = trim((string) ($d['language'] ?? 'plaintext'));
    $code = trim((string) ($d['code'] ?? ''));
    $caption = trim((string) ($d['caption'] ?? ''));
@endphp
@if($code !== '')
    <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-950 shadow-lg shadow-black/30">
        <div class="flex items-center justify-between border-b border-white/[0.06] bg-white/[0.03] px-4 py-2.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">{{ $lang }}</span>
            @if($caption !== '')
                <span class="text-xs text-zinc-500">{{ $caption }}</span>
            @endif
            <button
                type="button"
                onclick="navigator.clipboard.writeText(this.closest('[data-code-block]').querySelector('code').textContent)"
                class="rounded-lg border border-white/10 px-2 py-1 text-[10px] font-bold uppercase text-zinc-400 transition hover:text-emerald-200"
            >Copy</button>
        </div>
        <div data-code-block class="overflow-x-auto p-5">
            <pre class="text-sm leading-relaxed"><code class="language-{{ $lang }} text-emerald-100">{{ $code }}</code></pre>
        </div>
    </div>
@endif
