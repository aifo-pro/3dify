<x-layouts.marketplace :seo-title="__('author_promo.title') . ' · 3Dify'">
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        <header class="mb-8">
            <x-ui.badge>{{ __('author_promo.badge') }}</x-ui.badge>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('author_promo.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('author_promo.subtitle') }}</p>
        </header>

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/25 bg-emerald-300/[0.08] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-300/25 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">
                <ul class="grid gap-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Info note about who funds the discount --}}
        <div class="mb-6 rounded-2xl border border-amber-300/20 bg-amber-300/[0.06] p-4 text-sm leading-6 text-amber-100/90">
            {{ __('author_promo.funding_note') }}
        </div>

        {{-- Create form --}}
        <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
            <h2 class="text-lg font-black text-white">{{ __('author_promo.create_title') }}</h2>
            <form method="POST" action="{{ route('author.promo-codes.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div class="lg:col-span-1">
                    <label class="mb-1.5 block text-xs font-bold text-zinc-400">{{ __('author_promo.code') }}</label>
                    <input name="code" value="{{ old('code') }}" required placeholder="SUMMER20"
                        class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 font-mono text-sm uppercase text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-zinc-400">{{ __('author_promo.percent') }}</label>
                    <input name="value" type="number" min="1" max="90" value="{{ old('value') }}" required placeholder="20"
                        class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-zinc-400">{{ __('author_promo.usage_limit') }}</label>
                    <input name="usage_limit" type="number" min="1" value="{{ old('usage_limit') }}" placeholder="∞"
                        class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-zinc-400">{{ __('author_promo.expires_at') }}</label>
                    <input name="expires_at" type="date" value="{{ old('expires_at') }}"
                        class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <button class="h-11 rounded-xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">{{ __('author_promo.create_btn') }}</button>
                </div>
            </form>
        </div>

        {{-- List --}}
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-black text-white">{{ __('author_promo.your_codes') }}</h2>

            @if($codes->isEmpty())
                <div class="rounded-2xl border border-white/10 bg-zinc-900/40 px-6 py-12 text-center text-sm text-zinc-500">{{ __('author_promo.empty') }}</div>
            @else
                <div class="overflow-hidden rounded-2xl border border-white/10">
                    <table class="min-w-full divide-y divide-white/5 text-sm">
                        <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('author_promo.code') }}</th>
                                <th class="px-4 py-3">{{ __('author_promo.discount') }}</th>
                                <th class="px-4 py-3">{{ __('author_promo.used') }}</th>
                                <th class="px-4 py-3">{{ __('author_promo.expires_at') }}</th>
                                <th class="px-4 py-3">{{ __('author_promo.status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('author_promo.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($codes as $promo)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-3 font-mono font-black text-white">{{ $promo->code }}</td>
                                    <td class="px-4 py-3 text-zinc-300">−{{ (int) $promo->value }}%</td>
                                    <td class="px-4 py-3 text-zinc-300">{{ $promo->used_count }}{{ $promo->usage_limit ? ' / '.$promo->usage_limit : '' }}</td>
                                    <td class="px-4 py-3 text-zinc-400">{{ $promo->expires_at?->format('d.m.Y') ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('author.promo-codes.toggle', $promoCode = $promo) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button class="rounded-full px-2.5 py-0.5 text-xs font-bold transition {{ $promo->is_active ? 'bg-emerald-400/15 text-emerald-400 hover:bg-emerald-400/25' : 'bg-zinc-700/40 text-zinc-500 hover:bg-zinc-700/60' }}">
                                                {{ $promo->is_active ? __('author_promo.active') : __('author_promo.inactive') }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('author.promo-codes.destroy', $promo) }}" onsubmit="return confirm('?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg border border-rose-400/20 bg-rose-400/[0.06] px-3 py-1.5 text-xs font-bold text-rose-400 hover:bg-rose-400/[0.12]">{{ __('author_promo.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($codes->hasPages())
                    <div class="mt-6">{{ $codes->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</x-layouts.marketplace>
