@php
    $user = auth()->user();
    $isBuyer = $user->id === $order->buyer_id;
    $isAuthor = $user->id === $order->author_id;
    $isStaff = $user->canModerate();
    $canOffer = ($isAuthor || $isStaff) && in_array($order->status, [
        \App\Models\CustomOrder::STATUS_PENDING_REVIEW,
        \App\Models\CustomOrder::STATUS_DISCUSSING,
        \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT,
    ], true);
    $canCancel = ($isBuyer || $isAuthor || $isStaff) && in_array($order->status, [
        \App\Models\CustomOrder::STATUS_PENDING_REVIEW,
        \App\Models\CustomOrder::STATUS_DISCUSSING,
        \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT,
        \App\Models\CustomOrder::STATUS_WAITING_PAYMENT,
    ], true);
    $isModelOrder = $order->isModelCreation();
    $isPrintOrder = $order->isPrintService();
    $canSelectDelivery = $isBuyer && $isPrintOrder && in_array($order->status, [\App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT, \App\Models\CustomOrder::STATUS_WAITING_PAYMENT], true);
    $deliveryLabels = [
        'nova_poshta' => __('custom_orders.delivery.nova_poshta'),
        'ukrposhta' => __('custom_orders.delivery.ukrposhta'),
    ];
    $briefFiles = $order->files->where('purpose', 'brief');
    $resultFiles = $order->files->where('purpose', 'result');
    $chatMessages = $order->messages->map(fn ($message) => [
        'id' => $message->id,
        'own' => $message->user_id === $user->id,
        'author' => $message->user?->displayName() ?: __('custom_orders.system'),
        'body' => $message->body,
        'created_at' => $message->created_at?->translatedFormat('d M H:i'),
        'files' => $message->files->map(fn ($file) => [
            'id' => $file->id,
            'name' => $file->original_name,
            'url' => route('custom-orders.files.download', [$order, $file]),
        ])->values()->all(),
    ])->values();

    $money = fn ($value) => filled($value) ? number_format((float) $value, 2, ',', ' ').' грн' : '—';
    $currentStatus = $order->status;
    $workflowStatuses = [
        \App\Models\CustomOrder::STATUS_PENDING_REVIEW,
        \App\Models\CustomOrder::STATUS_DISCUSSING,
        \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT,
        \App\Models\CustomOrder::STATUS_WAITING_PAYMENT,
        \App\Models\CustomOrder::STATUS_PAID,
        \App\Models\CustomOrder::STATUS_IN_PROGRESS,
        \App\Models\CustomOrder::STATUS_SHIPPED,
        \App\Models\CustomOrder::STATUS_DELIVERED,
        \App\Models\CustomOrder::STATUS_COMPLETED,
    ];
    $isDisputed = $currentStatus === \App\Models\CustomOrder::STATUS_DISPUTED || filled($order->disputed_at);
    $workflowStatus = $currentStatus;

    if ($isDisputed) {
        $workflowStatus = optional($order->statusLogs->first(fn ($log) => in_array($log->to_status, $workflowStatuses, true)))->to_status
            ?: ($order->paid_at ? \App\Models\CustomOrder::STATUS_IN_PROGRESS : \App\Models\CustomOrder::STATUS_DISCUSSING);
    }
    $isTerminal = in_array($currentStatus, [
        \App\Models\CustomOrder::STATUS_COMPLETED,
        \App\Models\CustomOrder::STATUS_CANCELLED,
        \App\Models\CustomOrder::STATUS_REFUNDED,
    ], true);

    $workflowSteps = [
        ['key' => 'request', 'label' => __('custom_orders.workflow.steps.request'), 'hint' => __('custom_orders.workflow.steps.request_hint')],
        ['key' => 'terms', 'label' => __('custom_orders.workflow.steps.terms'), 'hint' => __('custom_orders.workflow.steps.terms_hint')],
        ['key' => 'payment', 'label' => __('custom_orders.workflow.steps.payment'), 'hint' => __('custom_orders.workflow.steps.payment_hint')],
        ['key' => 'work', 'label' => __('custom_orders.workflow.steps.work'), 'hint' => __('custom_orders.workflow.steps.work_hint')],
        ['key' => 'review', 'label' => __('custom_orders.workflow.steps.review'), 'hint' => __('custom_orders.workflow.steps.review_hint')],
        ['key' => 'done', 'label' => __('custom_orders.workflow.steps.done'), 'hint' => __('custom_orders.workflow.steps.done_hint')],
    ];

    $workflowIndex = match ($workflowStatus) {
        \App\Models\CustomOrder::STATUS_PENDING_REVIEW,
        \App\Models\CustomOrder::STATUS_DISCUSSING => 0,
        \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT => 1,
        \App\Models\CustomOrder::STATUS_WAITING_PAYMENT => 2,
        \App\Models\CustomOrder::STATUS_PAID,
        \App\Models\CustomOrder::STATUS_IN_PROGRESS,
        \App\Models\CustomOrder::STATUS_SHIPPED => 3,
        \App\Models\CustomOrder::STATUS_DELIVERED => 4,
        \App\Models\CustomOrder::STATUS_COMPLETED => 5,
        default => 0,
    };

    $nextAction = match (true) {
        $currentStatus === \App\Models\CustomOrder::STATUS_PENDING_REVIEW && ($isAuthor || $isStaff) => [
            'tone' => 'amber',
            'eyebrow' => __('custom_orders.workflow.next_for_author'),
            'title' => __('custom_orders.workflow.review_request_title'),
            'body' => __('custom_orders.workflow.review_request_body'),
            'href' => '#offer-form',
            'cta' => __('custom_orders.workflow.review_request_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_PENDING_REVIEW => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_author'),
            'title' => __('custom_orders.workflow.wait_author_title'),
            'body' => __('custom_orders.workflow.wait_author_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.write_details_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_DISCUSSING && ($isAuthor || $isStaff) => [
            'tone' => 'amber',
            'eyebrow' => __('custom_orders.workflow.next_for_author'),
            'title' => __('custom_orders.workflow.send_terms_title'),
            'body' => __('custom_orders.workflow.send_terms_body'),
            'href' => '#offer-form',
            'cta' => __('custom_orders.workflow.send_terms_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_DISCUSSING => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.discussion'),
            'title' => __('custom_orders.workflow.discuss_title'),
            'body' => __('custom_orders.workflow.discuss_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT && $isBuyer && $isPrintOrder && ! $order->hasDeliverySelection() => [
            'tone' => 'amber',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.delivery_title'),
            'body' => __('custom_orders.workflow.delivery_body'),
            'href' => '#delivery-step',
            'cta' => __('custom_orders.workflow.delivery_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT && $isBuyer => [
            'tone' => 'amber',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.accept_terms_title'),
            'body' => __('custom_orders.workflow.accept_terms_body'),
            'href' => '#buyer-actions',
            'cta' => __('custom_orders.workflow.accept_terms_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.wait_buyer_accept_title'),
            'body' => __('custom_orders.workflow.wait_buyer_accept_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_WAITING_PAYMENT && $isBuyer => [
            'tone' => 'amber',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.pay_title'),
            'body' => __('custom_orders.workflow.pay_body'),
            'href' => '#buyer-actions',
            'cta' => __('custom_orders.go_to_payment'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_WAITING_PAYMENT => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.wait_payment_title'),
            'body' => __('custom_orders.workflow.wait_payment_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        in_array($currentStatus, [\App\Models\CustomOrder::STATUS_PAID, \App\Models\CustomOrder::STATUS_IN_PROGRESS], true) && $isAuthor && $isModelOrder => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_author'),
            'title' => __('custom_orders.workflow.upload_result_title'),
            'body' => __('custom_orders.workflow.upload_result_body'),
            'href' => '#result-upload',
            'cta' => __('custom_orders.result.upload_title'),
        ],
        in_array($currentStatus, [\App\Models\CustomOrder::STATUS_PAID, \App\Models\CustomOrder::STATUS_IN_PROGRESS], true) && $isAuthor && $isPrintOrder => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_author'),
            'title' => __('custom_orders.workflow.ship_title'),
            'body' => __('custom_orders.workflow.ship_body'),
            'href' => '#ship-form',
            'cta' => __('custom_orders.ship'),
        ],
        in_array($currentStatus, [\App\Models\CustomOrder::STATUS_PAID, \App\Models\CustomOrder::STATUS_IN_PROGRESS, \App\Models\CustomOrder::STATUS_SHIPPED], true) => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.in_work'),
            'title' => __('custom_orders.workflow.work_progress_title'),
            'body' => __('custom_orders.workflow.work_progress_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_DELIVERED && $isBuyer => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.review_result_title'),
            'body' => __('custom_orders.workflow.review_result_body'),
            'href' => '#result-files',
            'cta' => __('custom_orders.workflow.review_result_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_DELIVERED => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.next_for_buyer'),
            'title' => __('custom_orders.workflow.wait_review_title'),
            'body' => __('custom_orders.workflow.wait_review_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_COMPLETED => [
            'tone' => 'emerald',
            'eyebrow' => __('custom_orders.workflow.completed'),
            'title' => __('custom_orders.workflow.completed_title'),
            'body' => __('custom_orders.workflow.completed_body'),
            'href' => '#result-files',
            'cta' => __('custom_orders.result.ready_files'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_CANCELLED => [
            'tone' => 'zinc',
            'eyebrow' => __('custom_orders.workflow.stopped'),
            'title' => __('custom_orders.workflow.cancelled_title'),
            'body' => __('custom_orders.workflow.cancelled_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        $currentStatus === \App\Models\CustomOrder::STATUS_DISPUTED => [
            'tone' => 'rose',
            'eyebrow' => __('custom_orders.workflow.dispute'),
            'title' => __('custom_orders.workflow.dispute_title'),
            'body' => __('custom_orders.workflow.dispute_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
        default => [
            'tone' => 'zinc',
            'eyebrow' => __('custom_orders.workflow.status'),
            'title' => __('custom_orders.workflow.default_title'),
            'body' => __('custom_orders.workflow.default_body'),
            'href' => '#order-chat',
            'cta' => __('custom_orders.workflow.open_chat_cta'),
        ],
    };

    $nextToneClasses = [
        'emerald' => 'border-emerald-300/25 bg-emerald-300/[0.07] text-emerald-100',
        'amber' => 'border-amber-300/30 bg-amber-300/[0.08] text-amber-100',
        'rose' => 'border-rose-300/30 bg-rose-300/[0.08] text-rose-100',
        'zinc' => 'border-white/10 bg-white/[0.04] text-zinc-100',
    ][$nextAction['tone']] ?? 'border-white/10 bg-white/[0.04] text-zinc-100';
@endphp

<x-layouts.marketplace>
    <section
        class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8"
        x-data="customOrderDelivery({
            carrier: @js(old('delivery_service', $order->delivery_service ?: 'nova_poshta')),
            cityName: @js(old('delivery_city', $order->delivery_city)),
            cityRef: @js(old('delivery_city_ref', $order->delivery_city_ref)),
            warehouseName: @js(old('delivery_address', $order->delivery_address)),
            warehouseRef: @js(old('delivery_warehouse_ref', $order->delivery_warehouse_ref)),
            citiesUrl: @js(route('delivery.cities')),
            warehousesUrl: @js(route('delivery.warehouses'))
        })"
    >
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="min-w-0">
                <div class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-emerald-200">{{ $isDisputed ? __('custom_orders.statuses.'.$workflowStatus) : $order->statusLabel() }}</span>
                        @if($isDisputed)
                            <span class="rounded-full border border-rose-300/30 bg-rose-300/[0.10] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-rose-100">{{ __('custom_orders.workflow.dispute') }}</span>
                        @endif
                        <span class="text-xs font-semibold text-zinc-500">{{ $order->number }}</span>
                        <span class="text-xs text-zinc-600">·</span>
                        <span class="text-xs text-zinc-400">{{ $order->typeLabel() }}</span>
                    </div>
                    <h1 class="mt-4 max-w-4xl break-words text-3xl font-black tracking-tight text-white sm:text-5xl">{{ $order->title }}</h1>
                    <p class="mt-4 max-w-5xl whitespace-pre-line break-words text-sm leading-7 text-zinc-300">{{ $order->description }}</p>

                    @if($isPrintOrder)
                        <div class="mt-6 grid gap-3 rounded-3xl border border-white/10 bg-zinc-950/50 p-4 text-sm md:grid-cols-2">
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.quantity') }}</span><span class="font-bold text-white">{{ $order->quantity ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.dimensions') }}</span><span class="font-bold text-white">{{ $order->dimensions ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.material') }}</span><span class="font-bold text-white">{{ $order->material ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-zinc-500">{{ __('custom_orders.form.color') }}</span><span class="font-bold text-white">{{ $order->color ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4 md:col-span-2"><span class="text-zinc-500">{{ __('custom_orders.form.delivery_service') }}</span><span class="font-bold text-white">{{ $deliveryLabels[$order->delivery_service] ?? ($order->delivery_service ?: '—') }}</span></div>
                            <div class="flex justify-between gap-4 md:col-span-2"><span class="text-zinc-500">{{ __('custom_orders.delivery.city') }}</span><span class="font-bold text-white">{{ $order->delivery_city ?: '—' }}</span></div>
                            <div class="flex justify-between gap-4 md:col-span-2"><span class="text-zinc-500">{{ __('custom_orders.form.delivery_address') }}</span><span class="font-bold text-white">{{ $order->delivery_address ?: '—' }}</span></div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 rounded-[2rem] border border-white/10 bg-white/[0.04] p-5 shadow-2xl shadow-black/20">
                    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-200">{{ __('custom_orders.workflow.map_title') }}</p>
                            <h2 class="mt-1 text-xl font-black text-white">{{ __('custom_orders.workflow.map_heading') }}</h2>
                        </div>
                        <p class="max-w-md text-sm leading-6 text-zinc-400">{{ __('custom_orders.workflow.map_hint') }}</p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                        @foreach($workflowSteps as $index => $step)
                            @php
                                $isStepDone = $index < $workflowIndex || ($workflowStatus === \App\Models\CustomOrder::STATUS_COMPLETED && $index === $workflowIndex);
                                $isStepCurrent = ! $isTerminal && $index === $workflowIndex;
                            @endphp
                            <div class="min-w-0 rounded-2xl border p-4 transition
                                @if($isStepDone) border-emerald-300/25 bg-emerald-300/[0.10]
                                @elseif($isStepCurrent) border-emerald-300/50 bg-emerald-300/[0.14] shadow-lg shadow-emerald-500/10
                                @else border-white/10 bg-zinc-950/50 @endif">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border text-sm font-black
                                        @if($isStepDone) border-emerald-300/30 bg-emerald-300 text-zinc-950
                                        @elseif($isStepCurrent) border-emerald-300/40 bg-zinc-950/50 text-emerald-100
                                        @else border-white/10 bg-zinc-950/60 text-zinc-500 @endif">
                                        @if($isStepDone) ✓ @else {{ $index + 1 }} @endif
                                    </div>
                                    @if($isStepCurrent)
                                        <span class="rounded-full bg-emerald-300 px-2 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-zinc-950">{{ __('custom_orders.workflow.now') }}</span>
                                    @endif
                                </div>
                                <p class="mt-3 text-sm font-black leading-5 @if($isStepDone || $isStepCurrent) text-white @else text-zinc-400 @endif">{{ $step['label'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $step['hint'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 rounded-[2rem] border p-6 shadow-2xl shadow-black/25 {{ $nextToneClasses }}">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.18em] opacity-75">{{ $nextAction['eyebrow'] }}</p>
                            <h2 class="mt-2 break-words text-2xl font-black text-white">{{ $nextAction['title'] }}</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-300">{{ $nextAction['body'] }}</p>
                        </div>
                        {{-- The chat sits directly below this card, so a "go to chat"
                             button here is redundant — only show a CTA when it points
                             to a different action (offer form, payment, delivery, etc.). --}}
                        @if($nextAction['href'] !== '#order-chat')
                            <a
                                href="{{ $nextAction['href'] }}"
                                onclick="event.preventDefault(); const target = document.querySelector(this.getAttribute('href')); target?.scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => target?.querySelector('textarea, input, button')?.focus({ preventScroll: true }), 380);"
                                class="inline-flex h-12 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white px-5 text-sm font-black text-zinc-950 transition hover:bg-emerald-200"
                            >
                                {{ $nextAction['cta'] }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Dispute panel: shows the open/resolved dispute and tells the
                     parties exactly where to submit evidence (the chat below). --}}
                @php $activeDispute = $order->disputes->firstWhere('status', 'open') ?? $order->disputes->first(); @endphp
                @if($isDisputed && $activeDispute)
                    <div class="mt-6 rounded-3xl border border-rose-300/25 bg-rose-300/[0.05] p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-rose-300/30 bg-rose-300/[0.10] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-rose-100">{{ __('custom_orders.workflow.dispute') }}</span>
                            @if($activeDispute->status === 'resolved')
                                <span class="rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-emerald-100">{{ __('custom_orders.dispute_panel.resolved') }}</span>
                            @else
                                <span class="rounded-full border border-amber-300/30 bg-amber-300/[0.10] px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-amber-100">{{ __('custom_orders.dispute_panel.under_review') }}</span>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-3 text-sm">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.14em] text-zinc-500">{{ __('custom_orders.reason') }}</p>
                                <p class="mt-1 font-bold text-white">{{ $activeDispute->reason }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.14em] text-zinc-500">{{ __('custom_orders.problem_description') }}</p>
                                <p class="mt-1 whitespace-pre-line break-words leading-6 text-zinc-300">{{ $activeDispute->description }}</p>
                            </div>
                            <p class="text-[11px] text-zinc-600">{{ __('custom_orders.dispute_panel.opened_by', ['name' => $activeDispute->opener?->displayName() ?: __('custom_orders.system')]) }} · {{ $activeDispute->created_at->translatedFormat('d M H:i') }}</p>
                        </div>

                        @if($activeDispute->status === 'resolved')
                            <div class="mt-4 rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.06] p-4 text-sm">
                                @if($activeDispute->resolution_note)
                                    <p class="text-zinc-200">{{ $activeDispute->resolution_note }}</p>
                                @endif
                                @if((float) $activeDispute->refund_amount > 0)
                                    <p class="mt-2 text-emerald-200">{{ __('custom_orders.dispute_panel.refund_issued', ['amount' => number_format((float) $activeDispute->refund_amount, 2)]) }}</p>
                                @endif
                            </div>
                        @else
                            <div class="mt-4 rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                                <p class="text-sm leading-6 text-zinc-300">{{ __('custom_orders.dispute_panel.evidence_hint') }}</p>
                                <a
                                    href="#order-chat"
                                    onclick="event.preventDefault(); const t = document.getElementById('order-chat'); t?.scrollIntoView({behavior:'smooth',block:'start'}); setTimeout(() => t?.querySelector('textarea, input')?.focus({preventScroll:true}), 380);"
                                    class="mt-3 inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-rose-300 px-5 text-sm font-black text-zinc-950 transition hover:bg-rose-200"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    {{ __('custom_orders.dispute_panel.add_evidence') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <div
                    id="order-chat"
                    class="mt-6 scroll-mt-28 rounded-3xl border border-white/10 bg-white/[0.04] p-6"
                    x-data="customOrderChat({
                        messages: @js($chatMessages),
                        fetchUrl: @js(route('custom-orders.messages.index', $order)),
                        sendUrl: @js(route('custom-orders.messages.store', $order)),
                        csrf: @js(csrf_token()),
                        placeholder: @js(__('custom_orders.chat_empty_message'))
                    })"
                    x-init="start()"
                >
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-200">{{ __('custom_orders.workflow.chat_eyebrow') }}</p>
                            <h2 class="mt-1 text-xl font-black text-white">{{ __('custom_orders.chat') }}</h2>
                        </div>
                        <p class="max-w-md text-sm leading-6 text-zinc-400">{{ __('custom_orders.workflow.chat_hint') }}</p>
                    </div>

                    <div x-ref="messages" class="mt-5 grid max-h-[440px] min-h-[220px] gap-4 overflow-y-auto pr-1">
                        <template x-for="message in messages" :key="message.id">
                            <div class="flex" :class="message.own ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[82%] break-words rounded-2xl border px-4 py-3" :class="message.own ? 'border-emerald-300/25 bg-emerald-300/[0.10]' : 'border-white/10 bg-zinc-950/70'">
                                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500">
                                        <span class="font-bold text-zinc-300" x-text="message.author"></span>
                                        <span x-text="message.created_at"></span>
                                    </div>
                                    <p x-show="message.body" class="whitespace-pre-line text-sm leading-6 text-zinc-200" x-text="message.body"></p>
                                    <div x-show="message.files && message.files.length" class="mt-3 grid gap-2">
                                        <template x-for="file in message.files" :key="file.id">
                                            <a :href="file.url" class="rounded-xl border border-white/10 bg-black/20 px-3 py-2 text-xs font-semibold text-emerald-200" x-text="file.name"></a>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <p x-show="error" x-cloak class="mt-3 rounded-2xl border border-rose-300/25 bg-rose-300/[0.08] px-4 py-3 text-sm font-semibold text-rose-100" x-text="error"></p>

                    <form method="POST" action="{{ route('custom-orders.messages.store', $order) }}" enctype="multipart/form-data" x-on:submit.prevent="send($event)" class="mt-5 rounded-2xl border border-white/10 bg-zinc-950/60 p-4">
                        @csrf
                        <textarea id="custom-order-message" name="body" rows="4" x-model="body" class="w-full rounded-2xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40" placeholder="{{ __('custom_orders.write_message') }}"></textarea>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <input type="file" name="files[]" multiple class="text-xs text-zinc-400 file:mr-3 file:rounded-xl file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                            <button class="h-10 rounded-xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 disabled:cursor-wait disabled:opacity-60" :disabled="sending">
                                <span x-show="!sending">{{ __('custom_orders.send') }}</span>
                                <span x-show="sending" x-cloak>{{ __('custom_orders.sending') }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                @if($canSelectDelivery)
                    <form id="delivery-step" method="POST" action="{{ route('custom-orders.delivery', $order) }}" class="mt-6 scroll-mt-28 rounded-3xl border border-emerald-300/25 bg-emerald-300/[0.06] p-6 shadow-2xl shadow-black/20">
                        @csrf
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-200">{{ __('custom_orders.delivery.step') }}</p>
                                <h2 class="mt-2 text-2xl font-black text-white">{{ __('custom_orders.delivery.title') }}</h2>
                                <p class="mt-1 text-sm leading-6 text-zinc-400">{{ __('custom_orders.delivery.hint') }}</p>
                            </div>
                            @if($order->hasDeliverySelection())
                                <span class="rounded-full border border-emerald-300/30 bg-zinc-950/50 px-3 py-1 text-xs font-black text-emerald-200">{{ __('custom_orders.delivery.selected') }}</span>
                            @endif
                        </div>

                        @error('delivery_address')
                            <div class="mt-4 rounded-2xl border border-rose-300/25 bg-rose-300/[0.08] px-4 py-3 text-sm font-semibold text-rose-100">{{ $message }}</div>
                        @enderror

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <x-admin.field name="delivery_service" as="select" :label="__('custom_orders.form.delivery_service')" x-model="carrier" x-on:change="resetDelivery()" required>
                                <option value="nova_poshta">{{ __('custom_orders.delivery.nova_poshta') }}</option>
                                <option value="ukrposhta">{{ __('custom_orders.delivery.ukrposhta') }}</option>
                            </x-admin.field>

                            <label class="grid gap-1.5">
                                <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('custom_orders.delivery.city') }} <span class="text-rose-300">*</span></span>
                                <input type="hidden" name="delivery_city" x-model="cityName">
                                <input type="hidden" name="delivery_city_ref" x-model="cityRef">
                                <input
                                    type="text"
                                    x-model="cityQuery"
                                    x-on:input.debounce.350ms="searchCities()"
                                    x-on:focus="cityQuery.length > 1 && searchCities()"
                                    placeholder="{{ __('custom_orders.delivery.city_placeholder') }}"
                                    class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"
                                >
                                <div x-show="cities.length" x-cloak class="max-h-56 overflow-y-auto rounded-2xl border border-white/10 bg-zinc-950/95 p-1 shadow-2xl shadow-black/30">
                                    <template x-for="city in cities" :key="city.ref">
                                        <button type="button" x-on:click="selectCity(city)" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-zinc-200 transition hover:bg-emerald-300/10 hover:text-white">
                                            <span class="font-bold" x-text="city.name"></span>
                                            <span class="ml-1 text-xs text-zinc-500" x-text="city.region"></span>
                                        </button>
                                    </template>
                                </div>
                            </label>

                            <label class="grid gap-1.5 md:col-span-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('custom_orders.form.delivery_address') }} <span class="text-rose-300">*</span></span>
                                <input type="hidden" name="delivery_address" x-model="warehouseName">
                                <input type="hidden" name="delivery_warehouse_ref" x-model="warehouseRef">
                                <input
                                    type="text"
                                    x-model="warehouseQuery"
                                    x-bind:disabled="!cityRef"
                                    x-on:input.debounce.350ms="searchWarehouses()"
                                    x-on:focus="cityRef && searchWarehouses()"
                                    placeholder="{{ __('custom_orders.delivery.warehouse_placeholder') }}"
                                    class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40 disabled:opacity-50"
                                >
                                <div x-show="warehouses.length" x-cloak class="max-h-64 overflow-y-auto rounded-2xl border border-white/10 bg-zinc-950/95 p-1 shadow-2xl shadow-black/30">
                                    <template x-for="warehouse in warehouses" :key="warehouse.ref">
                                        <button type="button" x-on:click="selectWarehouse(warehouse)" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-zinc-200 transition hover:bg-emerald-300/10 hover:text-white">
                                            <span class="font-bold" x-text="warehouse.name"></span>
                                        </button>
                                    </template>
                                </div>
                                <span x-show="loading" class="text-xs text-zinc-500">{{ __('custom_orders.delivery.loading') }}</span>
                            </label>

                            <x-admin.field name="extra_comment" as="textarea" rows="3" :label="__('custom_orders.form.extra_comment')" :value="old('extra_comment', $order->extra_comment)" class="md:col-span-2" />
                        </div>

                        <button class="mt-5 h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">
                            {{ __('custom_orders.delivery.save') }}
                        </button>
                    </form>
                @endif

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
                    <div id="result-files" class="mt-6 scroll-mt-28 rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.06] p-6">
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

            </div>

            <aside class="grid gap-5 self-start lg:sticky lg:top-28">
                <div class="rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.07] p-5">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-200">{{ __('Escrow') }}</p>
                    <p class="mt-2 text-3xl font-black text-white">{{ $money($order->price) }}</p>
                    <p class="mt-2 text-xs leading-5 text-emerald-100/80">{{ __('custom_orders.workflow.escrow_hint') }}</p>
                    <div class="mt-4 grid gap-2 text-sm text-zinc-300">
                        <div class="flex justify-between gap-4"><span>{{ __('custom_orders.workflow.buyer_paid') }}</span><span class="font-bold text-white">{{ $order->paid_at ? __('custom_orders.workflow.yes') : __('custom_orders.workflow.not_yet') }}</span></div>
                        <div class="flex justify-between gap-4"><span>{{ __('custom_orders.platform_fee') }}</span><span class="font-bold text-white">{{ $money($order->platform_fee_amount) }}</span></div>
                        <div class="flex justify-between gap-4"><span>{{ __('custom_orders.author_gets') }}</span><span class="font-bold text-white">{{ $money($order->author_amount) }}</span></div>
                        <div class="flex justify-between gap-4"><span>{{ __('custom_orders.workflow.release_condition') }}</span><span class="max-w-40 text-right font-bold text-emerald-100">{{ __('custom_orders.workflow.after_confirmation') }}</span></div>
                    </div>
                </div>

                @if($canOffer)
                    <form id="offer-form" method="POST" action="{{ route('custom-orders.offer', $order) }}" class="scroll-mt-28 rounded-3xl border border-white/10 bg-white/[0.04] p-5">
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

                <div id="buyer-actions" class="grid scroll-mt-28 gap-5">
                @if($isBuyer && $order->status === \App\Models\CustomOrder::STATUS_WAITING_BUYER_ACCEPT)
                    <form method="POST" action="{{ route('custom-orders.accept', $order) }}">
                        @csrf
                        <button
                            class="h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                            @if($isPrintOrder && ! $order->hasDeliverySelection()) disabled @endif
                        >{{ __('custom_orders.accept_offer') }}</button>
                        @if($isPrintOrder && ! $order->hasDeliverySelection())
                            <p class="mt-2 text-xs leading-5 text-amber-200">{{ __('custom_orders.delivery.required_before_accept') }}</p>
                        @endif
                    </form>
                @endif

                @if($isBuyer && $order->canBePaid())
                    <form method="POST" action="{{ route('custom-orders.pay', $order) }}">
                        @csrf
                        <button class="h-12 w-full rounded-2xl bg-amber-300 text-sm font-black text-zinc-950 shadow-lg shadow-amber-300/20">{{ __('custom_orders.go_to_payment') }}</button>
                    </form>
                @endif
                </div>

                @if($canCancel)
                    <details class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <summary class="cursor-pointer text-sm font-black text-zinc-200">{{ __('custom_orders.cancel_order') }}</summary>
                        <form method="POST" action="{{ route('custom-orders.cancel', $order) }}" class="mt-4 grid gap-3">
                            @csrf
                            <x-admin.field name="reason" as="textarea" rows="3" :label="__('custom_orders.cancel_reason')" :placeholder="__('custom_orders.cancel_reason_placeholder')" />
                            <button class="h-10 rounded-xl border border-rose-300/30 bg-rose-300/[0.10] text-sm font-black text-rose-100 transition hover:bg-rose-300/20">{{ __('custom_orders.cancel_order') }}</button>
                        </form>
                    </details>
                @endif

                @if($isAuthor && $isModelOrder && in_array($order->status, [\App\Models\CustomOrder::STATUS_IN_PROGRESS, \App\Models\CustomOrder::STATUS_PAID, \App\Models\CustomOrder::STATUS_DELIVERED], true))
                    <form id="result-upload" method="POST" action="{{ route('custom-orders.result', $order) }}" enctype="multipart/form-data" class="scroll-mt-28 rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.06] p-5">
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
                    <form id="ship-form" method="POST" action="{{ route('custom-orders.ship', $order) }}" class="scroll-mt-28 rounded-3xl border border-white/10 bg-white/[0.04] p-5">
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
