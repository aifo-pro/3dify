@php
    $user = auth()->user();
    $isBuyer = $user->id === $order->buyer_id;
    $isAuthor = $user->id === $order->author_id;
    $isStaff = $user->canModerate();
    $canOffer = $isAuthor || $isStaff;
@endphp

<x-layouts.marketplace>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm font-semibold text-emerald-100">{{ session('status') }}</div>
        @endif

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
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Покупець') }}</p>
                        <p class="mt-2 font-black text-white">{{ $order->buyer?->displayName() }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $order->buyer?->email }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Автор') }}</p>
                        <p class="mt-2 font-black text-white">{{ $order->author?->displayName() ?: '—' }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $order->author?->email ?: __('Ще не призначено') }}</p>
                    </div>
                </div>

                @if($order->files->isNotEmpty())
                    <div class="mt-6 rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                        <h2 class="text-xl font-black text-white">{{ __('custom_orders.files') }}</h2>
                        <div class="mt-4 grid gap-3">
                            @foreach($order->files as $file)
                                <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-zinc-950/60 px-4 py-3 transition hover:border-emerald-300/30">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-bold text-white">{{ $file->original_name }}</span>
                                        <span class="text-xs text-zinc-500">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }} · {{ number_format($file->size / 1024, 1) }} KB</span>
                                    </span>
                                    <span class="text-xs font-bold text-emerald-200">{{ __('Відкрити') }}</span>
                                </a>
                            @endforeach
                        </div>
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
                                        <span class="font-bold text-zinc-300">{{ $message->user?->displayName() ?: __('Система') }}</span>
                                        <span>{{ $message->created_at->translatedFormat('d M H:i') }}</span>
                                    </div>
                                    @if($message->body)
                                        <p class="whitespace-pre-line text-sm leading-6 text-zinc-200">{{ $message->body }}</p>
                                    @endif
                                    @if($message->files->isNotEmpty())
                                        <div class="mt-3 grid gap-2">
                                            @foreach($message->files as $file)
                                                <a href="{{ $file->url() }}" target="_blank" class="rounded-xl border border-white/10 bg-black/20 px-3 py-2 text-xs font-semibold text-emerald-200">{{ $file->original_name }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('custom-orders.messages.store', $order) }}" enctype="multipart/form-data" class="mt-5 rounded-2xl border border-white/10 bg-zinc-950/60 p-4">
                        @csrf
                        <textarea name="body" rows="4" class="w-full rounded-2xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40" placeholder="{{ __('Напишіть повідомлення...') }}"></textarea>
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
                        <div class="flex justify-between"><span>{{ __('Комісія') }}</span><span>{{ number_format((float) $order->platform_fee_amount, 2) }} UAH</span></div>
                        <div class="flex justify-between"><span>{{ __('Автор отримає') }}</span><span>{{ number_format((float) $order->author_amount, 2) }} UAH</span></div>
                    </div>
                </div>

                @if($canOffer && ! in_array($order->status, [\App\Models\CustomOrder::STATUS_COMPLETED, \App\Models\CustomOrder::STATUS_CANCELLED, \App\Models\CustomOrder::STATUS_REFUNDED], true))
                    <form method="POST" action="{{ route('custom-orders.offer', $order) }}" class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        @csrf
                        <h3 class="font-black text-white">{{ __('custom_orders.offer') }}</h3>
                        <div class="mt-4 grid gap-3">
                            <x-admin.field name="price" type="number" step="0.01" min="1" :label="__('Ціна, UAH')" :value="old('price', $order->price)" required />
                            <x-admin.field name="delivery_days" type="number" min="1" :label="__('Днів на виконання')" :value="old('delivery_days', $order->delivery_days)" />
                            <x-admin.field name="offer_description" as="textarea" rows="4" :label="__('Опис робіт')" :value="old('offer_description', $order->offer_description)" required />
                            <x-admin.field name="offer_terms" as="textarea" rows="3" :label="__('Умови')" :value="old('offer_terms', $order->offer_terms)" />
                            @for($i = 0; $i < 3; $i++)
                                <x-admin.field name="milestones[]" :label="$i === 0 ? __('Етапи') : null" :value="old('milestones.'.$i, $order->milestones[$i]->title ?? null)" placeholder="{{ __('Етап роботи') }}" />
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

                @if($isAuthor && in_array($order->status, [\App\Models\CustomOrder::STATUS_IN_PROGRESS, \App\Models\CustomOrder::STATUS_PAID], true))
                    <form method="POST" action="{{ route('custom-orders.ship', $order) }}" class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                        @csrf
                        <h3 class="font-black text-white">{{ __('custom_orders.ship') }}</h3>
                        <div class="mt-4 grid gap-3">
                            <x-admin.field name="carrier" :label="__('Служба')" required />
                            <x-admin.field name="tracking_number" :label="__('ТТН / трек номер')" required />
                        </div>
                        <button class="mt-4 h-11 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('custom_orders.ship') }}</button>
                    </form>
                @endif

                @if($isBuyer && in_array($order->status, [\App\Models\CustomOrder::STATUS_SHIPPED, \App\Models\CustomOrder::STATUS_DELIVERED], true))
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
                            <x-admin.field name="reason" :label="__('Причина')" required />
                            <x-admin.field name="description" as="textarea" rows="4" :label="__('Опис проблеми')" required />
                            <button class="h-10 rounded-xl bg-rose-300 text-sm font-black text-zinc-950">{{ __('custom_orders.open_dispute') }}</button>
                        </form>
                    </details>
                @endif

                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5">
                    <h3 class="font-black text-white">{{ __('Історія') }}</h3>
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
