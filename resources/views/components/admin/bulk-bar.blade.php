@props([
    'action' => '',
    'actions' => [], // [['value' => 'suspend', 'label' => '...'], ...]
])

<div x-data="bulkSelect()" x-init="init()" data-bulk-bar>
    <div x-show="selected.length > 0" x-cloak class="sticky top-[var(--admin-topbar-h,80px)] z-30 mb-3 flex flex-wrap items-center gap-2 rounded-2xl border border-emerald-300/40 bg-emerald-300/[0.10] px-4 py-2.5 backdrop-blur">
        <span class="text-xs font-bold text-emerald-100"><span x-text="selected.length"></span> {{ __('обрано') }}</span>
        <form method="POST" action="{{ $action }}" class="flex items-center gap-2" @submit="if (! confirm(@js(__('Виконати дію?')))) { $event.preventDefault(); }">
            @csrf
            <template x-for="id in selected"><input type="hidden" name="ids[]" :value="id"></template>
            <select name="action" required class="h-8 rounded-lg border border-white/10 bg-zinc-950/60 px-2 text-xs text-white">
                <option value="">{{ __('Виберіть дію') }}</option>
                @foreach($actions as $a)
                    <option value="{{ $a['value'] }}">{{ $a['label'] }}</option>
                @endforeach
            </select>
            <button class="h-8 rounded-lg bg-emerald-400 px-3 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Застосувати') }}</button>
        </form>
        <button type="button" @click="clear()" class="ml-auto text-xs font-bold text-emerald-200 hover:text-emerald-100">{{ __('Скинути') }}</button>
    </div>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkSelect', () => ({
                selected: [],
                init() {
                    this.$root.parentElement.addEventListener('change', (e) => {
                        const cb = e.target;
                        if (! cb.matches('input.bulk-row[type="checkbox"]')) return;
                        const id = cb.value;
                        if (cb.checked) {
                            if (! this.selected.includes(id)) this.selected.push(id);
                        } else {
                            this.selected = this.selected.filter(s => s !== id);
                        }
                    });
                    const all = this.$root.parentElement.querySelector('input.bulk-all[type="checkbox"]');
                    if (all) {
                        all.addEventListener('change', () => {
                            const rows = this.$root.parentElement.querySelectorAll('input.bulk-row[type="checkbox"]');
                            rows.forEach(r => { r.checked = all.checked; r.dispatchEvent(new Event('change', { bubbles: true })); });
                        });
                    }
                },
                clear() {
                    this.selected = [];
                    this.$root.parentElement.querySelectorAll('input.bulk-row, input.bulk-all').forEach(r => r.checked = false);
                },
            }));
        });
    </script>
@endonce
