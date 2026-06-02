@php
    $user = auth()->user();
    $isBuyer = $user->id === $order->buyer_id;
    $isAuthor = $user->id === $order->author_id;
    $isStaff = $user->canModerate();
    $canOffer = $isAuthor || $isStaff;
    $isModelOrder = $order->isModelCreation();
    $isPrintOrder = $order->isPrintService();
    $briefFiles = $order->files->where('purpose', 'brief');
    $resultFiles = $order->files->where('purpose', 'result');
@endphp

<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="min-w-0">
                <div class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-emerald-200">{{ $order->statusLabel() }}</span>
                        <span class="text-xs font-semibold text-zinc-500">{{ $order->number }}</span>
                        <span class="text-xs text-zinc-600">·</span>
                        <span class="text-xs text-zinc-400">{{ $order->typeLabel() }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $order->title }}</h1>
                    <p class="mt-4 whitespace-pre-line text-sm leading-7 text-zinc-300">{{ $order->description }}</p>

                    @if($isPrintOrder)
                        <div class="mt-6 grid gap-3 rounded-3xl border border-white/10 bg-zinc-950/50 p-4 text-sm md:grid-cols-2">
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.quantity') }}</span><span class="font-bold text-white">{{ $order->quantity ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.dimensions') }}</span><span class="font-bold text-white">{{ $order->dimensions ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.material') }}</span><span class="font-bold text-white">{{ $order->material ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.color') }}</span><span class="font-bold text-white">{{ $order->color ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4 md:col-span-2"><span class="text-zinc-500">{{ __('custom_orders.form.delivery_service') }}</span><span class="font-bold text-white">{{ $order->delivery_service ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4 md:col-span-2"><span class="text-zinc-500">{{ __('custom_orders.form.delivery_address') }}</span><span class="font-bold text-white">{{ $order->delivery_address ?: '—' }}</span></div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('custom_orders.buyer') }}</p>
                        <p class="mt-2 font-black text-white">{{ $order->buyer?->displayName() }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $order->buyer?->email }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('custom_orders.form.author') }}</p>
                        <p class="mt-2 font-black text-white">{{ $order->author?->displayName() ?: '—' }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $order->author?->email ?: __('custom_orders.not_assigned') }}</p>
                    </div>
                </div>

                @if($briefFiles->isNotEmpty())
                    <div class="mt-6 rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                        <h2 class="text-xl font-black text-white">{{ __('custom_orders.references') }}</h2>
                        <div class="mt-4 grid gap-3">
                            @foreach($briefFiles as $file)
                                <a href="{{ route('custom-orders.files.download', [$order, $file]) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/60 px-4 py-3 transition hover:border-emerald-300/30">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-bold text-white">{{ $file->original_name }}</span>
                                        <span class="text-xs text-zinc-500">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }} · {{ number_format($file->size / 1024, 1) }} KB</span>
                                    </span>
                                    <span class="text-xs font-bold text-emerald-200">{{ __('custom_orders.download') }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($isModelOrder)
                    <div class="mt-6 rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.06] p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-white">{{ __('custom_orders.result.ready_files') }}</h2>
                                <p class="mt-1 text-sm text-zinc-400">{{ __('custom_orders.result.ready_files_hint') }}</p>
                            </div>
                            <span class="rounded-full border border-emerald-300/25 bg-zinc-950/50 px-3 py-1 text-xs font-black text-emerald-200">{{ $resultFiles->count() }}</span>
                        </div>

                        @if($resultFiles->isNotEmpty())
                            <div class="mt-5 grid gap-3">
                                @foreach($resultFiles as $file)
                                    <a href="{{ route('custom-orders.files.download', [$order, $file]) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 py-3 transition hover:border-emerald-300/40">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-white">{{ $file->original_name }}</span>
                                            <span class="text-xs text-zinc-500">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }} · {{ number_format($file->size / 1024, 1) }} KB</span>
                                        </span>
                                        <span class="rounded-xl bg-emerald-400 px-3 py-2 text-xs font-black text-zinc-950">{{ __('custom_orders.download') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-white/10 bg-zinc-950/50 p-5 text-sm text-zinc-400">{{ __('custom_orders.result.no_files_yet') }}</div>
                        @endif

                        @if($isBuyer && $order->status === \App\Models\CustomOrder::STATUS_DELIVERED && $resultFiles->isNotEmpty())
                            <form method="POST" action="{{ route('custom-orders.complete', $order) }}" class="mt-5">
                                @csrf
                                <button class="h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20">{{ __('custom_orders.complete') }}</button>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="mt-6 rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('custom_orders.chat') }}</h2>
                    <div class="mt-5 grid max-h-[520px] gap-4 overflow-y-auto pr-1">
                        @foreach($order->messages as $message)
                            @php $own = $message->user_id === $user->id; @endphp
                            <div class="flex {{ $own ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[82%] rounded-2xl border px-4 py-3 {{ $own ? 'border-emerald-300/25 bg-emerald-300/[0.10]' : 'border-white/10 bg-zinc-950/70' }}">
                                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500">
                                        <span class="font-bold text-zinc-300">{{ $message->user?->displayName() ?: __('custom_orders.system') }}</span>
                                        <span>{{ $message->created_at->translatedFormat('d M H:i') }}</span>
                                    </div>
                                    @if($message->body)
                                        <p class="whitespace-pre-line text-sm leading-6 text-zinc-200">{{ $message->body }}</p>
                                    @endif
                                    @if($message->files->isNotEmpty())
                                        <div class="mt-3 grid gap-2">
                                            @foreach($message->files as $file)
                                                <a href="{{ route('custom-orders.files.download', [$order, $file]) }}" class="rounded-xl border border-white/10 bg-black/20 px-3 py-2 text-xs font-semibold text-emerald-200">{{ $file->original_name }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('custom-orders.messages.store', $order) }}" enctype="multipart/form-data" class="mt-5 rounded-2xl border border-white/10 bg-zinc-950/60 p-4">
                        @csrf
                        <textarea name="body" rows="4" class="w-full rounded-2xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40" placeholder="{{ __('custom_orders.write_message') }}"></textarea>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <input type="file" name="files[]" multiple class="text-xs text-zinc-400 file:mr-3 file:rounded-xl file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                            <button class="h-10 rounded-xl bg-emerald-400 px-5 text-sm font-black text-zinc-950">{{ __('custom_orders.send') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <aside class="grid gap-5 self-start lg:sticky lg:top-28">
                <div class="rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.07] p-5">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-200">{{ __('Escrow') }}</p>
                    <p class="mt-2 text-3xl font-black text-white">{{ $order->price ? number_format((float) $order->price, 2).' UAH' : '—' }}</p>
                    <div class="mt-4 grid gap-2 text-sm text-zinc-300">
                        <div class="flex justify-between"><span>{{ __('custom_orders.platform_fee') }}</span><span>{{ number_format((float) $order->platform_fee_amount, 2) }} UAH</span></div>
                        <div class="flex justify-between"><span>{{ __('custom_orders.author_gets') }}</span><span>{{ number_format((float) $order->author_amount, 2) }} UAH</span></div>
                    </div>
                </div>

                @if($canOffer && ! in_array($order->status, [\App\Models\CustomOrder::STATUS_COMPLETED, \App\Models\CustomOrder::STATUS_CANCELLED, \App\Models\CustomOrder::STATUS_REFUNDED], true))
                    <form method="POST" action="{{ route('custom-orders.offer', $order) }}" class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        @csrf
                        <h3 class="font-black text-white">{{ __('custom_orders.offer') }}</h3>
                        <div class="mt-4 grid gap-3">
                            <x-admin.field name="price" type="number" step="0.01" min="1" :label="__('custom_orders.price_uah')" :value="old('price', $order->price)" required />
                            <x-admin.field name="delivery_days" type="number" min="1" :label="__('custom_orders.delivery_days')" :value="old('delivery_days', $order->delivery_days)" />
                            <x-admin.field name="offer_description" as="textarea" rows="4" :label="__('custom_orders.work_description')" :value="old('offer_description', $order->offer_description)" required />
                            <x-admin.field name="offer_terms" as="textarea" rows="3" :label="__('custom_orders.terms')" :value="old('offer_terms', $order->offer_terms)" />
                            @for($i = 0; $i < 3; $i++)
                                <x-admin.field name="milestones[]" :label="$i === 0 ? __('custom_orders.milestones') : null" :value="old('milestones.'.$i, $order->milestones[$i]->title ?? null)" :placeholder="__('custom_orders.milestone_placeholder')" />
                            @endfor
                        </div>
                        <button class="mt-4 h-11 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('custom_orders.save_offer') }}</button>
                    </form>
                @endif

                @if($isBuyer && $order->status === \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT)
                    <form method="POST" action="{{ route('custom-orders.accept', $order) }}">
                        @csrf
                        <button class="h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('custom_orders.accept_offer') }}</button>
                    </form>
                @endif

                @if($isBuyer && $order->canBePaid())
                    <form method="POST" action="{{ route('custom-orders.demo-pay', $order) }}">
                        @csrf
                        <button class="h-12 w-full rounded-2xl bg-amber-300 text-sm font-black text-zinc-950">{{ __('custom_orders.mark_paid') }}</button>
                    </form>
                @endif

                @if($isAuthor && $isModelOrder && in_array($order->status, [\App\Models\CustomOrder::STATUS_IN_PROGRESS, \App\Models\CustomOrder::STATUS_PAID, \App\Models\CustomOrder::STATUS_DELIVERED], true))
                    <form method="POST" action="{{ route('custom-orders.result', $order) }}" enctype="multipart/form-data" class="rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.06] p-5">
                        @csrf
                        <h3 class="font-black text-white">{{ __('custom_orders.result.upload_title') }}</h3>
                        <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('custom_orders.result.upload_hint') }}</p>
                        <div class="mt-4 grid gap-3">
                            <x-admin.field name="result_comment" as="textarea" rows="3" :label="__('custom_orders.result.comment')" :value="old('result_comment')" />
                            <input type="file" name="result_files[]" multiple required class="block w-full rounded-2xl border border-white/10 bg-zinc-950/70 px-4 py-4 text-sm text-zinc-300 file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400 file:px-4 file:py-2 file:text-sm file:font-black file:text-zinc-950">
                        </div>
                        <button class="mt-4 h-11 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('custom_orders.result.send') }}</button>
                    </form>
                @endif

                @if($isAuthor && $isPrintOrder && in_array($order->status, [\App\Models\CustomOrder::STATUS_IN_PROGRESS, \App\Models\CustomOrder::STATUS_PAID], true))
                    <form method="POST" action="{{ route('custom-orders.ship', $order) }}" class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        @csrf
                        <h3 class="font-black text-white">{{ __('custom_orders.ship') }}</h3>
                        <div class="mt-4 grid gap-3">
                            <x-admin.field name="carrier" :label="__('custom_orders.carrier')" required />
                            <x-admin.field name="tracking_number" :label="__('custom_orders.tracking_number')" required />
                        </div>
                        <button class="mt-4 h-11 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('custom_orders.ship') }}</button>
                    </form>
                @endif

                @if($isBuyer && $isPrintOrder && $order->status === \App\Models\CustomOrder::STATUS_DELIVERED)
                    <form method="POST" action="{{ route('custom-orders.complete', $order) }}">
                        @csrf
                        <button class="h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('custom_orders.complete') }}</button>
                    </form>
                @endif

                @if(! in_array($order->status, [\App\Models\CustomOrder::STATUS_COMPLETED, \App\Models\CustomOrder::STATUS_DISPUTED, \App\Models\CustomOrder::STATUS_REFUNDED], true))
                    <details class="rounded-3xl border border-rose-300/20 bg-rose-300/[0.05] p-5">
                        <summary class="cursor-pointer text-sm font-black text-rose-100">{{ __('custom_orders.open_dispute') }}</summary>
                        <form method="POST" action="{{ route('custom-orders.dispute', $order) }}" class="mt-4 grid gap-3">
                            @csrf
                            <x-admin.field name="reason" :label="__('custom_orders.reason')" required />
                            <x-admin.field name="description" as="textarea" rows="4" :label="__('custom_orders.problem_description')" required />
                            <button class="h-10 rounded-xl bg-rose-300 text-sm font-black text-zinc-950">{{ __('custom_orders.open_dispute') }}</button>
                        </form>
                    </details>
                @endif

                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                    <h3 class="font-black text-white">{{ __('custom_orders.history') }}</h3>
                    <div class="mt-4 grid gap-3">
                        @foreach($order->statusLogs as $log)
                            <div class="border-l border-white/10 pl-3">
                                <p class="text-xs font-bold text-zinc-300">{{ __('custom_orders.statuses.'.$log->to_status) }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $log->note }}</p>
                                <p class="mt-1 text-[11px] text-zinc-600">{{ $log->created_at->translatedFormat('d M H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.marketplace>
