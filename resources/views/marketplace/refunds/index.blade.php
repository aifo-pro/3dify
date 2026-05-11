@php
    $money = fn ($amount, $currency = 'UAH') => number_format((float) $amount, 2, '.', ' ') . ' ' . ($currency ?: 'UAH');
    $statusMeta = [
        'pending' => ['label' => __('Очікує перевірки'), 'class' => 'border-amber-300/30 bg-amber-300/[0.08] text-amber-100'],
        'approved' => ['label' => __('Підтверджено'), 'class' => 'border-emerald-300/30 bg-emerald-300/[0.08] text-emerald-100'],
        'refunded' => ['label' => __('Повернуто на баланс'), 'class' => 'border-emerald-300/30 bg-emerald-300/[0.08] text-emerald-100'],
        'rejected' => ['label' => __('Відхилено'), 'class' => 'border-rose-300/30 bg-rose-300/[0.08] text-rose-100'],
    ];
@endphp

<x-layouts.marketplace :seo-title="__('Повернення коштів') . ' · 3Dify'">
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30 sm:p-8">
            <x-ui.badge>{{ __('Підтримка') }}</x-ui.badge>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ __('Повернення коштів') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">
                {{ __('Якщо файл пошкоджений або модель не відповідає опису, створіть заявку. Після підтвердження кошти зараховуються на ваш баланс 3Dify, а доступ до файлів цього замовлення закривається.') }}
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('balance.index') }}" class="inline-flex h-10 items-center rounded-xl border border-emerald-300/20 bg-emerald-300/[0.08] px-4 text-xs font-black text-emerald-100 hover:bg-emerald-300/[0.14]">{{ __('Відкрити баланс') }}</a>
                <a href="{{ route('dashboard') }}" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-white/[0.05] px-4 text-xs font-bold text-zinc-200 hover:bg-white/[0.09]">{{ __('Мої покупки') }}</a>
            </div>
        </header>

        @if(session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div>
                <h2 class="mb-3 text-sm font-black uppercase tracking-[0.18em] text-zinc-500">{{ __('Мої заявки') }}</h2>
                <div class="space-y-4">
                    @forelse($requests as $r)
                        @php
                            $meta = $statusMeta[$r->status] ?? $statusMeta['pending'];
                            $order = $r->order;
                            $credit = $r->balanceTransactions->firstWhere('type', 'credit');
                        @endphp
                        <article class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-xl shadow-black/20">
                            <div class="flex flex-col gap-4 border-b border-white/10 p-5 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-mono text-sm font-black text-white">{{ $order->number ?? __('Замовлення №').$r->order_id }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $r->created_at->translatedFormat('d M Y · H:i') }}</p>
                                </div>
                                <span class="inline-flex h-8 items-center rounded-full border px-3 text-[11px] font-black uppercase tracking-wide {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                            </div>

                            <div class="grid gap-5 p-5">
                                @if($order)
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach($order->items as $item)
                                            @if($item->product)
                                                <a href="{{ route('products.show', $item->product) }}" class="rounded-2xl border border-white/10 bg-zinc-950/45 p-4 transition hover:border-emerald-300/30">
                                                    <p class="line-clamp-1 text-sm font-black text-white">{{ $item->product->localized('title') }}</p>
                                                    <p class="mt-1 text-xs text-zinc-500">{{ $money($item->price, $item->currency) }}</p>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Причина') }}</p>
                                        <p class="mt-2 text-sm font-bold text-zinc-100">{{ __($reasons[$r->reason] ?? $r->reason) }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Сума') }}</p>
                                        <p class="mt-2 text-sm font-bold text-zinc-100">{{ $order ? $money($order->items->sum('price'), $order->currency) : '—' }}</p>
                                    </div>
                                </div>

                                @if($r->message)
                                    <div class="rounded-2xl border border-white/10 bg-zinc-950/40 p-4">
                                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Ваш опис') }}</p>
                                        <p class="mt-2 text-sm leading-6 text-zinc-300">{{ $r->message }}</p>
                                    </div>
                                @endif

                                @if($r->admin_notes || $credit)
                                    <div class="rounded-2xl border border-emerald-300/20 bg-emerald-500/[0.06] p-4">
                                        @if($credit)
                                            <p class="text-sm font-black text-emerald-100">{{ __('На баланс зараховано') }}: {{ $money($credit->amount, $credit->currency) }}</p>
                                            <p class="mt-1 text-xs text-emerald-200/75">{{ __('Доступ до файлів замовлення після повернення закрито.') }}</p>
                                        @endif
                                        @if($r->admin_notes)
                                            <p class="mt-3 text-xs leading-5 text-emerald-100/90"><strong>{{ __('Коментар підтримки') }}:</strong> {{ $r->admin_notes }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <x-ui.empty-state :title="__('Заявок ще немає')" :description="__('Повернення зʼявляться тут після створення заявки.')" />
                    @endforelse
                </div>

                <div class="mt-8">{{ $requests->links() }}</div>
            </div>

            <aside class="self-start rounded-[2rem] border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/25 lg:sticky lg:top-28">
                <h2 class="text-xl font-black text-white">{{ __('Подати нову заявку') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('Оберіть оплачене замовлення, причину та коротко опишіть проблему. Підтримка бачить історію доступу: завантаження, відкриття у slicer та друк.') }}</p>

                @php $userOrders = auth()->user()->orders()->where('status', 'paid')->latest()->limit(20)->get(); @endphp
                @if($userOrders->isEmpty())
                    <p class="mt-5 rounded-2xl border border-white/10 bg-white/[0.04] p-4 text-sm text-zinc-400">{{ __('У вас немає оплачених замовлень для повернення.') }}</p>
                @else
                    <form method="POST" action="" class="mt-5 grid gap-3" id="refund-form">
                        @csrf
                        <select name="order_select" id="order_select" class="h-12 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white" required>
                            <option value="">{{ __('Оберіть замовлення') }}</option>
                            @foreach($userOrders as $o)
                                <option value="{{ $o->id }}">{{ $o->number }} · {{ $money($o->total, $o->currency) }} · {{ $o->created_at->format('d.m.Y') }}</option>
                            @endforeach
                        </select>
                        <select name="reason" class="h-12 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white" required>
                            <option value="">{{ __('Причина') }}</option>
                            @foreach($reasons as $key => $label)
                                <option value="{{ $key }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <textarea name="message" rows="5" maxlength="2000" placeholder="{{ __('Деталі проблеми') }}" class="rounded-2xl border border-white/10 bg-zinc-950/70 px-4 py-3 text-sm text-white placeholder:text-zinc-500"></textarea>
                        <button class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300">{{ __('Надіслати заявку') }}</button>
                        @error('order')<p class="text-xs text-rose-300">{{ $message }}</p>@enderror
                    </form>
                    <script>
                        document.getElementById('refund-form').addEventListener('submit', function () {
                            const orderId = document.getElementById('order_select').value;
                            if (orderId) this.action = '/orders/' + orderId + '/refund';
                        });
                    </script>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.marketplace>
