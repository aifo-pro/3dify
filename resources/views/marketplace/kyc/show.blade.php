<x-layouts.marketplace>
    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-[32px] border border-white/10 bg-white/[0.04] shadow-2xl shadow-black/30">
            <div class="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,rgba(52,211,153,.18),transparent_34%),linear-gradient(135deg,rgba(15,23,42,.92),rgba(9,9,11,.96))] p-6 sm:p-8">
                <x-ui.badge>{{ __('kyc.section_label') }}</x-ui.badge>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('kyc.page.title') }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-300">{{ __('kyc.page.description') }}</p>
            </div>

            <div class="grid gap-6 p-6 sm:p-8">
                @if(session('status'))
                    <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">{{ $errors->first() }}</div>
                @endif

                @php
                    $status = auth()->user()->kyc_status ?: 'not_started';
                    $approved = auth()->user()->hasApprovedKyc();
                @endphp

                <div class="rounded-3xl border {{ $approved ? 'border-emerald-300/25 bg-emerald-300/[0.06]' : 'border-white/10 bg-zinc-950/55' }} p-5">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('kyc.current_status') }}</p>
                    <h2 class="mt-2 text-2xl font-black text-white">{{ __('kyc.status.'.$status) }}</h2>
                    <div class="mt-4 grid gap-3 text-sm text-zinc-300 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <p class="text-[11px] uppercase tracking-[0.14em] text-zinc-500">{{ __('kyc.provider') }}</p>
                            <p class="mt-1 font-bold text-white">didit.me</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                            <p class="text-[11px] uppercase tracking-[0.14em] text-zinc-500">{{ __('kyc.verified_at') }}</p>
                            <p class="mt-1 font-bold text-white">{{ auth()->user()->kyc_verified_at?->format('d.m.Y H:i') ?: '—' }}</p>
                        </div>
                    </div>
                    @if($verification?->rejection_reason)
                        <div class="mt-4 rounded-2xl border border-rose-300/25 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">
                            {{ __('kyc.rejection_reason') }}: {{ $verification->rejection_reason }}
                        </div>
                    @endif
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-5">
                    <h3 class="text-lg font-black text-white">{{ __('kyc.page.why_title') }}</h3>
                    <ul class="mt-4 grid gap-3 text-sm text-zinc-300">
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-300"></span>{{ __('kyc.page.why_1') }}</li>
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-300"></span>{{ __('kyc.page.why_2') }}</li>
                        <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-300"></span>{{ __('kyc.page.why_3') }}</li>
                    </ul>
                </div>

                @unless($approved)
                    <form method="POST" action="{{ route('kyc.start') }}" class="flex justify-end">
                        @csrf
                        <button class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                            {{ in_array($status, ['rejected', 'expired', 'failed'], true) ? __('kyc.retry') : __('kyc.start') }}
                        </button>
                    </form>
                @endunless
            </div>
        </div>
    </section>
</x-layouts.marketplace>
