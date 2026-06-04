<x-layouts.admin
    :title="__('kyc.admin.title')"
    :description="__('kyc.admin.description')"
    active="kyc"
>
    <form method="GET" action="{{ route('admin.kyc.index') }}" class="mb-6 grid gap-3 rounded-3xl border border-white/10 bg-white/[0.04] p-4 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
        <input name="q" value="{{ $search }}" placeholder="{{ __('kyc.admin.search') }}" class="h-11 rounded-2xl border border-white/10 bg-zinc-950/60 px-4 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
        <select name="status" class="h-11 rounded-2xl border border-white/10 bg-zinc-950/60 px-4 text-sm text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            <option value="all" @selected($status === 'all')>{{ __('kyc.admin.all') }}</option>
            @foreach(\App\Models\KycVerification::STATUSES as $item)
                <option value="{{ $item }}" @selected($status === $item)>{{ __('kyc.status.'.$item) }}</option>
            @endforeach
        </select>
        <button class="h-11 rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 hover:bg-emerald-300">{{ __('kyc.admin.filter') }}</button>
    </form>

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach(['all' => __('kyc.admin.all')] + collect(\App\Models\KycVerification::STATUSES)->mapWithKeys(fn ($s) => [$s => __('kyc.status.'.$s)])->all() as $key => $label)
            <a href="{{ route('admin.kyc.index', ['status' => $key]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $status === $key ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.section :title="__('kyc.admin.table_title')" :description="__('kyc.admin.table_desc')">
        @if($verifications->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-950/40">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                            <th class="px-5 py-3">{{ __('kyc.admin.user') }}</th>
                            <th class="px-5 py-3">{{ __('kyc.status_label') }}</th>
                            <th class="px-5 py-3">{{ __('kyc.admin.session') }}</th>
                            <th class="px-5 py-3">{{ __('kyc.admin.decision') }}</th>
                            <th class="px-5 py-3">{{ __('kyc.admin.dates') }}</th>
                            <th class="px-5 py-3">{{ __('kyc.admin.payload') }}</th>
                            <th class="px-5 py-3">{{ __('kyc.admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($verifications as $verification)
                            <tr class="align-top transition hover:bg-white/[0.02]">
                                <td class="px-5 py-3">
                                    <a href="{{ $verification->user?->profileUrl() ?: '#' }}" class="font-bold text-white hover:text-emerald-200">{{ $verification->user?->displayName() ?: '—' }}</a>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $verification->user?->email }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <x-ui.status :status="$verification->status" />
                                    @if($verification->rejection_reason)
                                        <p class="mt-2 max-w-xs text-xs leading-5 text-rose-200">{{ $verification->rejection_reason }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <p class="max-w-xs break-all font-mono text-xs text-zinc-300">{{ $verification->provider_session_id ?: '—' }}</p>
                                    <p class="mt-1 max-w-xs break-all font-mono text-[11px] text-zinc-600">{{ $verification->provider_applicant_id }}</p>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-300">{{ $verification->decision ?: '—' }}</td>
                                <td class="px-5 py-3 text-xs text-zinc-400">
                                    <p>{{ __('kyc.admin.created') }}: {{ $verification->created_at?->format('d.m.Y H:i') }}</p>
                                    <p>{{ __('kyc.admin.approved') }}: {{ $verification->approved_at?->format('d.m.Y H:i') ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <details class="max-w-sm">
                                        <summary class="cursor-pointer text-xs font-bold text-emerald-200">{{ __('kyc.admin.show_payload') }}</summary>
                                        <pre class="mt-2 max-h-52 overflow-auto rounded-2xl border border-white/10 bg-black/30 p-3 text-[11px] leading-5 text-zinc-300">{{ json_encode($verification->webhook_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-col gap-2">
                                        @if($verification->provider_session_id)
                                            <form method="POST" action="{{ route('admin.kyc.sync', $verification) }}">
                                                @csrf
                                                <button class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-300 transition hover:bg-white/[0.08] hover:text-white">
                                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                                                    {{ __('kyc.admin.sync_btn') }}
                                                </button>
                                            </form>
                                        @endif
                                        @if($verification->status !== \App\Models\KycVerification::STATUS_APPROVED)
                                            <form method="POST" action="{{ route('admin.kyc.status', $verification) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button class="h-8 w-full rounded-lg bg-emerald-400/15 px-3 text-xs font-bold text-emerald-300 transition hover:bg-emerald-400/25">{{ __('kyc.admin.approve_btn') }}</button>
                                            </form>
                                        @endif
                                        @if($verification->status !== \App\Models\KycVerification::STATUS_REJECTED)
                                            <form method="POST" action="{{ route('admin.kyc.status', $verification) }}" onsubmit="return confirm('?')">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="h-8 w-full rounded-lg bg-rose-400/10 px-3 text-xs font-bold text-rose-300 transition hover:bg-rose-400/20">{{ __('kyc.admin.reject_btn') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $verifications->links() }}</div>
        @else
            <p class="py-8 text-center text-sm text-zinc-500">{{ __('kyc.admin.empty') }}</p>
        @endif
    </x-admin.section>
</x-layouts.admin>
