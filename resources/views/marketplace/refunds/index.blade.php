<x-layouts.marketplace>
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8">
            <x-ui.badge>{{ __('Підтримка') }}</x-ui.badge>
            <h1 class="mt-3 text-3xl font-black text-white sm:text-4xl">{{ __('Повернення коштів') }}</h1>
            <p class="mt-2 text-zinc-400">{{ __('Якщо ви помилково придбали модель або файли пошкоджені — заповніть заявку. Розглядаємо до 3 робочих днів.') }}</p>
        </header>

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif

        <h2 class="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Мої заявки') }}</h2>

        <div class="space-y-3">
            @forelse($requests as $r)
                <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $r->order->number ?? __('Замовлення №').$r->order_id }}</p>
                            <p class="text-xs text-zinc-500">{{ $r->created_at->translatedFormat('d M Y · H:i') }}</p>
                        </div>
                        <x-ui.status :status="$r->status" size="md" />
                    </div>
                    <p class="mt-3 text-xs uppercase tracking-[0.12em] text-zinc-500">{{ __('Причина') }}</p>
                    <p class="mt-1 text-sm text-zinc-200">{{ __($reasons[$r->reason] ?? $r->reason) }}</p>
                    @if($r->message)
                        <p class="mt-2 rounded-xl border border-white/10 bg-zinc-950/40 p-3 text-sm text-zinc-300">{{ $r->message }}</p>
                    @endif
                    @if($r->admin_notes)
                        <p class="mt-2 rounded-xl border border-emerald-300/20 bg-emerald-500/[0.06] p-3 text-xs text-emerald-200"><strong>{{ __('Відповідь модератора') }}:</strong> {{ $r->admin_notes }}</p>
                    @endif
                </article>
            @empty
                <x-ui.empty-state :title="__('Немає заявок')" :description="__('Ви ще не подавали заяв на повернення.')" />
            @endforelse
        </div>

        <div class="mt-8">{{ $requests->links() }}</div>

        <hr class="my-10 border-white/5">

        <h2 class="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Подати нову заявку') }}</h2>
        <p class="mb-4 text-xs text-zinc-500">{{ __('Введіть номер замовлення, виберіть причину та опишіть проблему.') }}</p>

        @php $userOrders = auth()->user()->orders()->where('status', 'paid')->latest()->limit(20)->get(); @endphp
        @if($userOrders->isEmpty())
            <p class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 text-sm text-zinc-400">{{ __('У вас немає оплачених замовлень для повернення.') }}</p>
        @else
            <form method="POST" action="" class="grid gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-5" id="refund-form">
                @csrf
                <select name="order_select" id="order_select" class="h-11 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white" required>
                    <option value="">{{ __('Оберіть замовлення') }}</option>
                    @foreach($userOrders as $o)
                        <option value="{{ $o->id }}">{{ $o->number }} · {{ number_format((float) $o->total, 2) }} {{ $o->currency }} · {{ $o->created_at->format('d.m.Y') }}</option>
                    @endforeach
                </select>
                <select name="reason" class="h-11 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white" required>
                    <option value="">{{ __('Причина') }}</option>
                    @foreach($reasons as $key => $label)
                        <option value="{{ $key }}">{{ __($label) }}</option>
                    @endforeach
                </select>
                <textarea name="message" rows="4" maxlength="2000" placeholder="{{ __('Деталі проблеми (опціонально)') }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white placeholder:text-zinc-500"></textarea>
                <button class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Надіслати заявку') }}</button>
                @error('order')<p class="text-xs text-rose-300">{{ $message }}</p>@enderror
            </form>
            <script>
                document.getElementById('refund-form').addEventListener('submit', function (e) {
                    var orderId = document.getElementById('order_select').value;
                    if (orderId) {
                        this.action = '/orders/' + orderId + '/refund';
                    }
                });
            </script>
        @endif
    </section>
</x-layouts.marketplace>
