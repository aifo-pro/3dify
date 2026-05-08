@php
    $labels = array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d.m'), array_keys($sales));
@endphp
<x-layouts.admin
    :title="__('Аналітика сайту')"
    :description="__('Витяг бізнес-метрик за обраний період.')"
    breadcrumb-current="{{ __('Аналітика') }}"
    active="analytics"
>
    <div class="mb-5 flex flex-wrap items-center gap-2">
        @foreach([7 => __('7 днів'), 30 => __('30 днів'), 90 => __('90 днів'), 365 => __('Рік')] as $r => $lbl)
            <a href="{{ route('admin.analytics', ['range' => $r]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $range === $r ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">{{ $lbl }}</a>
        @endforeach
    </div>

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('GMV (paid)')" :value="number_format($kpis['gmv'], 2)" tone="emerald" />
        <x-admin.kpi-card :label="__('Замовлень')" :value="$kpis['orders']" />
        <x-admin.kpi-card :label="__('Середній чек')" :value="number_format($kpis['aov'], 2)" />
        <x-admin.kpi-card :label="__('Тіпи (paid)')" :value="number_format($kpis['tips'], 2)" tone="amber" />
    </div>
    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('Нових юзерів')" :value="$kpis['new_users']" />
        <x-admin.kpi-card :label="__('Нових авторів')" :value="$kpis['new_authors']" />
        <x-admin.kpi-card :label="__('Нових моделей')" :value="$kpis['new_products']" />
        <x-admin.kpi-card :label="__('Підписників email')" :value="$kpis['subscribers']" tone="sky" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-admin.section :title="__('Продажі за день')">
            <x-admin.chart
                :labels="$labels"
                :series="[['label' => __('Сума, грн'), 'color' => 'emerald', 'data' => $sales]]"
                :height="220"
            />
        </x-admin.section>

        <x-admin.section :title="__('Реєстрації та публікації')">
            <x-admin.chart
                :labels="$labels"
                :series="[
                    ['label' => __('Нові юзери'), 'color' => 'sky', 'data' => $signups],
                    ['label' => __('Нові моделі'), 'color' => 'violet', 'data' => $publishes],
                ]"
                :height="220"
            />
        </x-admin.section>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-admin.section :title="__('Топ-10 продажів')">
            @if($topProducts->isEmpty())
                <p class="py-8 text-center text-xs text-zinc-500">{{ __('Поки порожньо.') }}</p>
            @else
                <ul class="divide-y divide-white/5">
                    @foreach($topProducts as $i => $p)
                        <li class="flex items-center gap-3 py-2.5 text-sm">
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-emerald-300/[0.10] text-xs font-black text-emerald-100">{{ $i + 1 }}</span>
                            <a href="{{ route('products.show', $p) }}" target="_blank" class="min-w-0 flex-1 truncate font-semibold text-white hover:text-emerald-200">{{ $p->localized('title') }}</a>
                            <span class="rounded-full border border-emerald-300/20 bg-emerald-300/[0.06] px-2.5 py-0.5 text-[10px] font-bold text-emerald-100">{{ $p->sales }} {{ __('продажів') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.section>

        <x-admin.section :title="__('Топ-10 авторів за публікаціями')">
            @if($topAuthors->isEmpty())
                <p class="py-8 text-center text-xs text-zinc-500">{{ __('Поки порожньо.') }}</p>
            @else
                <ul class="divide-y divide-white/5">
                    @foreach($topAuthors as $i => $a)
                        <li class="flex items-center gap-3 py-2.5 text-sm">
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-sky-300/[0.10] text-xs font-black text-sky-100">{{ $i + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate font-semibold text-white">{{ $a->name }}</span>
                            <span class="rounded-full border border-sky-300/20 bg-sky-300/[0.06] px-2.5 py-0.5 text-[10px] font-bold text-sky-100">{{ $a->products_in_period }} {{ __('моделей') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.section>
    </div>
</x-layouts.admin>
