<x-layouts.admin :title="$order->number" :description="$order->title" active="custom-orders">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="grid gap-6">
            <x-admin.section :title="__('Деталі')" :description="$order->typeLabel()">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-emerald-200">{{ $order->statusLabel() }}</span>
                    <span class="text-xs text-zinc-500">{{ $order->created_at->translatedFormat('d M Y H:i') }}</span>
                </div>
                <h2 class="mt-4 text-2xl font-black text-white">{{ $order->title }}</h2>
                <p class="mt-4 whitespace-pre-line text-sm leading-7 text-zinc-300">{{ $order->description }}</p>
            </x-admin.section>

            <x-admin.section :title="__('Файли')">
                <div class="grid gap-3">
                    @forelse($order->files as $file)
                        <a href="{{ $file->url() }}" target="_blank" class="flex items-center justify-between rounded-2xl border border-white/10 bg-zinc-950/60 px-4 py-3">
                            <span>
                                <span class="block font-bold text-white">{{ $file->original_name }}</span>
                                <span class="text-xs text-zinc-500">{{ $file->purpose }} · {{ number_format($file->size / 1024, 1) }} KB</span>
                            </span>
                            <span class="text-xs font-bold text-emerald-200">{{ __('Відкрити') }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('Файлів немає') }}</p>
                    @endforelse
                </div>
            </x-admin.section>

            <x-admin.section :title="__('Чат')">
                <div class="grid gap-3">
                    @foreach($order->messages as $message)
                        <div class="rounded-2xl border border-white/10 bg-zinc-950/60 p-4">
                            <p class="text-xs text-zinc-500">{{ $message->user?->displayName() ?: __('Система') }} · {{ $message->created_at->translatedFormat('d M H:i') }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-200">{{ $message->body }}</p>
                        </div>
                    @endforeach
                </div>
            </x-admin.section>
        </div>

        <aside class="grid gap-5 self-start lg:sticky lg:top-24">
            <x-admin.section :title="__('Escrow')">
                <div class="grid gap-2 text-sm">
                    <div class="flex justify-between text-zinc-400"><span>{{ __('Ціна') }}</span><span class="font-black text-white">{{ number_format((float) $order->price, 2) }} UAH</span></div>
                    <div class="flex justify-between text-zinc-400"><span>{{ __('Комісія') }}</span><span>{{ number_format((float) $order->platform_fee_amount, 2) }} UAH</span></div>
                    <div class="flex justify-between text-zinc-400"><span>{{ __('Автор') }}</span><span>{{ number_format((float) $order->author_amount, 2) }} UAH</span></div>
                </div>
            </x-admin.section>

            <x-admin.section :title="__('Змінити статус')">
                <form method="POST" action="{{ route('admin.custom-orders.update', $order) }}" class="grid gap-3">
                    @csrf
                    @method('PATCH')
                    <x-admin.field name="status" as="select" :label="__('Статус')">
                        @foreach(\App\Models\CustomOrder::STATUSES as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ __('custom_orders.statuses.'.$status) }}</option>
                        @endforeach
                    </x-admin.field>
                    <x-admin.field name="note" as="textarea" rows="3" :label="__('Нотатка')" />
                    <button class="h-11 rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950">{{ __('Зберегти') }}</button>
                </form>
            </x-admin.section>

            @if($order->shipments->isNotEmpty())
                <x-admin.section :title="__('Доставка')">
                    <form method="POST" action="{{ route('admin.custom-orders.track', $order) }}" class="mb-3">
                        @csrf
                        <button class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-300 transition hover:bg-white/[0.08]">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                            {{ __('Оновити трекінг') }}
                        </button>
                    </form>
                    @foreach($order->shipments as $shipment)
                        <div class="rounded-2xl border border-white/10 bg-zinc-950/40 p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-white">{{ strtoupper(str_replace('_', ' ', (string) $shipment->carrier)) }}</span>
                                <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-200">{{ $shipment->status }}</span>
                            </div>
                            @if($shipment->tracking_number)
                                <p class="mt-1 font-mono text-xs text-zinc-400">{{ $shipment->tracking_number }}</p>
                            @endif
                            @if($shipment->events->isNotEmpty())
                                <div class="mt-3 grid gap-2 border-t border-white/5 pt-3">
                                    @foreach($shipment->events as $event)
                                        <div class="text-[11px]">
                                            <span class="font-bold text-zinc-300">{{ $event->status }}</span>
                                            <span class="text-zinc-500">— {{ $event->description }}</span>
                                            <span class="block text-zinc-600">{{ $event->happened_at?->translatedFormat('d M H:i') }} · {{ $event->location }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </x-admin.section>
            @endif

            <x-admin.section :title="__('Історія статусів')">
                <div class="grid gap-3">
                    @foreach($order->statusLogs as $log)
                        <div class="border-l border-white/10 pl-3">
                            <p class="text-xs font-bold text-zinc-300">{{ __('custom_orders.statuses.'.$log->to_status) }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $log->note }}</p>
                            <p class="mt-1 text-[11px] text-zinc-600">{{ $log->created_at->translatedFormat('d M H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </x-admin.section>
        </aside>
    </div>
</x-layouts.admin>
