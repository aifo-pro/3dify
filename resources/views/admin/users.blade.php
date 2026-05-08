@php
    $roleLabels = [
        'user' => __('Користувач'),
        'author' => __('Автор'),
        'moderator' => __('Модератор'),
        'admin' => __('Адмін'),
    ];

    $roleTints = [
        'user' => 'border-white/10 bg-white/[0.06] text-zinc-200',
        'author' => 'border-sky-300/30 bg-sky-300/10 text-sky-100',
        'moderator' => 'border-amber-300/30 bg-amber-300/10 text-amber-100',
        'admin' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100',
    ];
@endphp

<x-layouts.admin
    :title="__('Користувачі')"
    :description="__('Профілі, ролі, безпека та активність на маркетплейсі.')"
    active="users"
    :breadcrumbs="[['label' => __('Користувачі')]]"
>
    <x-slot:actions>
        <a href="{{ route('admin.export.users') }}" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 hover:bg-white/10" title="{{ __('Експорт CSV') }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            CSV
        </a>
        <form method="GET" action="{{ route('admin.users') }}" class="flex items-center gap-2">
            @if($role)<input type="hidden" name="role" value="{{ $role }}">@endif
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            @if($sort)<input type="hidden" name="sort" value="{{ $sort }}">@endif
            <div class="relative">
                <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Імʼя, email, username…') }}" class="h-9 w-64 rounded-full border border-white/10 bg-white/[0.04] pl-9 pr-3 text-sm text-white placeholder:text-zinc-500 transition focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
            </div>
        </form>
    </x-slot:actions>

    {{-- Stat cards --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Усього') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($totalCount) }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">+{{ $newThisWeek }} {{ __('за тиждень') }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-300">{{ __('Адміни / Модератори') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ ($roleCounts['admin'] ?? 0) + ($roleCounts['moderator'] ?? 0) }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">{{ $roleCounts['admin'] ?? 0 }} admin · {{ $roleCounts['moderator'] ?? 0 }} mod</p>
        </div>
        <div class="rounded-2xl border border-sky-300/20 bg-sky-300/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-300">{{ __('Автори') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $roleCounts['author'] ?? 0 }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">{{ __('публікують моделі') }}</p>
        </div>
        <div class="rounded-2xl border border-rose-300/20 bg-rose-300/[0.04] p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-300">{{ __('Заблоковані') }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $suspendedCount }}</p>
            <p class="mt-1 text-[11px] text-zinc-500">{{ __('не можуть увійти') }}</p>
        </div>
    </div>

    {{-- Filter chips --}}
    @php $baseQuery = array_filter(['q' => $q, 'sort' => $sort]); @endphp
    <div class="mt-5 flex flex-wrap items-center gap-2">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ __('Роль') }}:</p>
        <a href="{{ route('admin.users', $baseQuery) }}" class="inline-flex h-7 items-center rounded-full border px-3 text-xs font-semibold transition {{ $role === '' ? 'border-emerald-300/30 bg-emerald-300/15 text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/10 hover:text-white' }}">{{ __('Усі') }}</a>
        @foreach(['user', 'author', 'moderator', 'admin'] as $r)
            <a href="{{ route('admin.users', array_merge($baseQuery, ['role' => $r])) }}" class="inline-flex h-7 items-center gap-1.5 rounded-full border px-3 text-xs font-semibold transition {{ $role === $r ? 'border-emerald-300/30 bg-emerald-300/15 text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/10 hover:text-white' }}">
                {{ $roleLabels[$r] }}
                <span class="rounded-full bg-zinc-950/40 px-1.5 text-[10px] font-bold text-zinc-300">{{ $roleCounts[$r] ?? 0 }}</span>
            </a>
        @endforeach

        <span class="mx-2 h-5 w-px bg-white/10"></span>

        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ __('Статус') }}:</p>
        @foreach([
            '' => __('Усі'),
            'active' => __('Активні'),
            'suspended' => __('Заблоковані'),
            'verified' => __('Email підтв.'),
            'unverified' => __('Email не підтв.'),
        ] as $key => $label)
            <a href="{{ route('admin.users', array_merge($baseQuery, $role ? ['role' => $role] : [], ['status' => $key])) }}" class="inline-flex h-7 items-center rounded-full border px-3 text-xs font-semibold transition {{ $status === $key ? 'border-emerald-300/30 bg-emerald-300/15 text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/10 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    {{-- Table --}}
    <div class="mt-5">
        <x-admin.section :padded="false">
            @php $sortOptions = ['latest' => __('Нові'), 'oldest' => __('Старі'), 'name' => __('За іменем'), 'most_products' => __('Найбільше моделей'), 'most_orders' => __('Найбільше покупок')]; @endphp
            <div class="flex items-center justify-between gap-3 border-b border-white/5 px-5 py-3">
                <p class="text-xs text-zinc-500">{{ trans_choice(':count користувач|:count користувача|:count користувачів', $users->total(), ['count' => number_format($users->total())]) }}</p>
                <form method="GET" action="{{ route('admin.users') }}" class="flex items-center gap-2 text-xs">
                    @foreach(array_filter(['q' => $q, 'role' => $role, 'status' => $status]) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <label class="text-zinc-500">{{ __('Сортування') }}:</label>
                    <div class="relative">
                        <select name="sort" onchange="this.form.submit()" class="h-8 appearance-none rounded-lg border border-white/10 bg-zinc-950/70 pl-3 pr-8 text-xs text-white focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                            @foreach($sortOptions as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <svg class="pointer-events-none absolute right-2 top-1/2 h-3 w-3 -translate-y-1/2 text-zinc-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.24 4.38a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </div>
                </form>
            </div>

            <x-admin.bulk-bar :action="route('admin.bulk.users')" :actions="[
                ['value' => 'suspend', 'label' => __('Заблокувати')],
                ['value' => 'unsuspend', 'label' => __('Розблокувати')],
                ['value' => 'verify_email', 'label' => __('Підтвердити email')],
                ['value' => 'unverify_email', 'label' => __('Зняти підтвердження email')],
                ['value' => 'delete', 'label' => __('Видалити')],
            ]" />

            <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-950/40">
                        <tr class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                            <th class="w-10 px-3 py-3 text-center"><input type="checkbox" class="bulk-all h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40"></th>
                            <th class="px-5 py-3">{{ __('Користувач') }}</th>
                            <th class="px-5 py-3">{{ __('Роль') }}</th>
                            <th class="px-5 py-3">{{ __('Статус') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Моделей') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Покупок') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Підписників') }}</th>
                            <th class="px-5 py-3">{{ __('Реєстрація') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('Дії') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($users as $user)
                            @php $isSelf = $user->id === auth()->id(); @endphp
                            <tr class="transition hover:bg-white/[0.02]">
                                <td class="px-3 py-3 text-center">
                                    @unless($isSelf)
                                        <input type="checkbox" class="bulk-row h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40" value="{{ $user->id }}">
                                    @endunless
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($user->avatar_path)
                                            <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                        @else
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-300/15 text-xs font-black text-emerald-100">
                                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate font-semibold text-white">{{ $user->name }}</p>
                                                @if($isSelf)
                                                    <span class="rounded-full bg-emerald-300/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-100">{{ __('Ви') }}</span>
                                                @endif
                                            </div>
                                            <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                                @if($user->username)
                                                    <span class="font-mono text-[10px] text-zinc-500">@{{ $user->username }}</span>
                                                @endif
                                                @if($user->email_verified_at)
                                                    <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-300/10 px-1.5 py-0.5 text-[9px] font-bold text-emerald-200" title="{{ __('Email підтверджено') }}">
                                                        <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                        verified
                                                    </span>
                                                @endif
                                                @if($user->github_id)
                                                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/10 text-[8px] font-bold text-zinc-300" title="GitHub">GH</span>
                                                @endif
                                                @if($user->telegram_id)
                                                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-white/10 text-[8px] font-bold text-zinc-300" title="Telegram">TG</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $roleTints[$user->role] ?? $roleTints['user'] }}">{{ $roleLabels[$user->role] ?? $user->role }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <x-admin.status-pill :status="$user->is_suspended ? 'suspended' : 'active'">
                                        {{ $user->is_suspended ? __('заблоковано') : __('активний') }}
                                    </x-admin.status-pill>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $user->products_count }}</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-white/[0.06] px-2.5 py-0.5 text-xs font-bold text-zinc-200">{{ $user->orders_count }}</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-emerald-300/[0.10] px-2.5 py-0.5 text-xs font-bold text-emerald-100">{{ $user->followers_count ?? 0 }}</span>
                                </td>
                                <td class="px-5 py-3 text-xs text-zinc-400">{{ $user->created_at?->format('d.m.Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" x-data @click="$dispatch('open-user', { id: {{ $user->id }} })" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100" title="{{ __('Відкрити профіль') }}">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>

                                        {{-- Quick toggle suspend --}}
                                        @unless($isSelf)
                                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="name" value="{{ $user->name }}">
                                                <input type="hidden" name="email" value="{{ $user->email }}">
                                                @if($user->username)<input type="hidden" name="username" value="{{ $user->username }}">@endif
                                                <input type="hidden" name="role" value="{{ $user->role }}">
                                                <input type="hidden" name="locale" value="{{ $user->locale ?: 'uk' }}">
                                                <input type="hidden" name="bio" value="{{ $user->bio }}">
                                                <input type="hidden" name="is_suspended" value="{{ $user->is_suspended ? '0' : '1' }}">
                                                <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] transition {{ $user->is_suspended ? 'text-emerald-200 hover:border-emerald-300/30 hover:bg-emerald-300/10' : 'text-zinc-300 hover:border-amber-300/30 hover:bg-amber-300/10 hover:text-amber-100' }}" title="{{ $user->is_suspended ? __('Розблокувати') : __('Заблокувати') }}">
                                                    @if($user->is_suspended)
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                                    @else
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                    @endif
                                                </button>
                                            </form>
                                        @endunless

                                        {{-- Quick verify --}}
                                        <form method="POST" action="{{ route('admin.users.toggle-verification', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.04] transition {{ $user->email_verified_at ? 'text-emerald-200 hover:border-emerald-300/30 hover:bg-emerald-300/10' : 'text-zinc-300 hover:border-emerald-300/30 hover:bg-emerald-300/10 hover:text-emerald-100' }}" title="{{ $user->email_verified_at ? __('Зняти підтвердження email') : __('Підтвердити email') }}">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-16 text-center">
                                    <div class="grid place-items-center">
                                        <div class="grid h-12 w-12 place-items-center rounded-full bg-white/[0.06] text-zinc-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-white">{{ ($q || $role || $status) ? __('Нічого не знайдено') : __('Користувачів немає') }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ ($q || $role || $status) ? __('Спробуйте інший запит або фільтри.') : __('Користувачі зʼявляться після першої реєстрації.') }}</p>
                                    </div>
                        </td>
                    </tr>
                        @endforelse
                </tbody>
            </table>
        </div>
        </x-admin.section>

        @if($users->hasPages())
            <div class="mt-5">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Slide-over: full user profile / edit / actions --}}
    <div
        x-data="{
            open: false,
            tab: 'profile',
            data: {
                id: null, name: '', username: '', email: '', role: 'user', locale: 'uk',
                bio: '', is_suspended: false, manual_verification: false, email_verified_at: null,
                github_id: null, telegram_id: null, telegram_username: null,
                avatar_url: null, created_at: null, products_count: 0, orders_count: 0, is_self: false,
            },
            items: @js($editable),
            openUser(id) {
                const c = this.items.find(x => x.id === id);
                if (!c) return;
                this.data = { ...c };
                this.tab = 'profile';
                this.open = true;
            },
            initial() {
                return (this.data.name || '?').trim().charAt(0).toUpperCase();
            }
        }"
        @open-user.window="openUser($event.detail.id)"
        @keydown.escape.window="open = false"
        x-cloak
    >
        <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"></div>

        <aside
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col border-l border-white/10 bg-zinc-950 shadow-2xl shadow-black/50"
        >
            {{-- Header / hero --}}
            <header class="shrink-0 border-b border-white/10 bg-gradient-to-br from-emerald-300/[0.06] to-zinc-950 px-6 py-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-4 min-w-0">
                        <template x-if="data.avatar_url">
                            <img :src="data.avatar_url" alt="" class="h-14 w-14 shrink-0 rounded-full object-cover">
                        </template>
                        <template x-if="!data.avatar_url">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-emerald-300/15 text-base font-black text-emerald-100" x-text="initial()">A</span>
                        </template>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="truncate text-lg font-bold text-white" x-text="data.name">User</h2>
                                <template x-if="data.is_self">
                                    <span class="rounded-full bg-emerald-300/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-emerald-100">{{ __('Ви') }}</span>
                                </template>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-zinc-400" x-text="data.email">user@example.com</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                <template x-if="data.username">
                                    <span class="font-mono text-zinc-500" x-text="'@' + data.username">@user</span>
                                </template>
                                <template x-if="data.email_verified_at">
                                    <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-300/10 px-1.5 py-0.5 text-[9px] font-bold text-emerald-200">
                                        <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        verified
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button @click="open = false" type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                {{-- Quick stats --}}
                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl border border-white/10 bg-zinc-950/50 p-3 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('Моделі') }}</p>
                        <p class="mt-1 text-lg font-black text-white" x-text="data.products_count">0</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-zinc-950/50 p-3 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('Покупки') }}</p>
                        <p class="mt-1 text-lg font-black text-white" x-text="data.orders_count">0</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-zinc-950/50 p-3 text-center">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{{ __('Реєстрація') }}</p>
                        <p class="mt-1 text-lg font-black text-white" x-text="data.created_at">—</p>
                    </div>
                </div>
            </header>

            {{-- Tabs --}}
            <nav class="flex shrink-0 items-center gap-1 border-b border-white/10 bg-zinc-950/60 px-3">
                @foreach([
                    ['key' => 'profile', 'label' => __('Профіль')],
                    ['key' => 'security', 'label' => __('Безпека')],
                    ['key' => 'danger', 'label' => __('Danger zone')],
                ] as $tab)
                    <button
                        type="button"
                        @click="tab = '{{ $tab['key'] }}'"
                        :class="tab === '{{ $tab['key'] }}'
                            ? 'border-emerald-300 text-emerald-100'
                            : 'border-transparent text-zinc-400 hover:text-white'"
                        class="border-b-2 px-4 py-3 text-sm font-semibold transition"
                    >{{ $tab['label'] }}</button>
                @endforeach
            </nav>

            <div class="flex-1 overflow-y-auto [scrollbar-width:thin]">
                {{-- Profile tab --}}
                <div x-show="tab === 'profile'" class="px-6 py-5">
                    <form
                        method="POST"
                        action="{{ url('/admin/users') }}/0"
                        :action="`/admin/users/${data.id}`"
                        class="grid gap-4"
                    >
                        @csrf @method('PATCH')

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-admin.field name="name" :label="__('Імʼя')" required x-model="data.name" :error="$errors->first('name')" />
                            <x-admin.field name="username" label="Username" placeholder="dgenkov" x-model="data.username" :error="$errors->first('username')" />
                        </div>
                        <x-admin.field name="email" label="Email" type="email" required x-model="data.email" :error="$errors->first('email')" />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-admin.field as="select" name="role" :label="__('Роль')" required x-model="data.role" :error="$errors->first('role')">
                                @foreach(['user' => __('Користувач'), 'author' => __('Автор'), 'moderator' => __('Модератор'), 'admin' => __('Адмін')] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </x-admin.field>
                            <x-admin.field as="select" name="locale" :label="__('Мова інтерфейсу')" x-model="data.locale">
                                <option value="uk">Українська</option>
                                <option value="en">English</option>
                            </x-admin.field>
                        </div>

                        <x-admin.field as="textarea" name="bio" :label="__('Біо')" rows="3" placeholder="{{ __('Коротке представлення автора') }}" x-model="data.bio" :error="$errors->first('bio')" />

                        <label class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-zinc-950/50 px-4 py-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-white">{{ __('Заблоковано') }}</span>
                                <span class="block text-xs text-zinc-500">{{ __('Користувач не зможе увійти та публікувати моделі.') }}</span>
                            </span>
                            <input
                                type="checkbox" name="is_suspended" value="1"
                                x-model="data.is_suspended"
                                :disabled="data.is_self"
                                class="h-5 w-5 rounded border-white/20 bg-zinc-950 text-rose-400 focus:ring-rose-300 disabled:opacity-40"
                            >
                        </label>
                        <p x-show="data.is_self" class="text-[11px] text-amber-200">{{ __('Не можна заблокувати власний акаунт.') }}</p>

                        <div class="rounded-xl border border-white/10 bg-zinc-950/50 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('Підключені акаунти') }}</p>
                            <div class="mt-3 grid gap-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-300">GitHub</span>
                                    <span class="font-mono" :class="data.github_id ? 'text-emerald-200' : 'text-zinc-500'" x-text="data.github_id || '— не підключено'"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-300">Telegram</span>
                                    <span class="font-mono" :class="data.telegram_id ? 'text-emerald-200' : 'text-zinc-500'" x-text="data.telegram_username ? '@' + data.telegram_username : (data.telegram_id || '— не підключено')"></span>
                                </div>
                            </div>
                        </div>

                        <div class="sticky bottom-0 -mx-6 -mb-5 flex items-center justify-end gap-2 border-t border-white/10 bg-zinc-950/95 px-6 py-4 backdrop-blur">
                            <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-sm font-semibold text-zinc-200 hover:bg-white/10">{{ __('Скасувати') }}</button>
                            <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти') }}</button>
                        </div>
                    </form>
                </div>

                {{-- Security tab --}}
                <div x-show="tab === 'security'" x-cloak class="px-6 py-5">
                    <div class="grid gap-4">
                        {{-- Email verification --}}
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white">{{ __('Email-підтвердження') }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        <template x-if="data.email_verified_at"><span><span x-text="data.email_verified_at"></span> · {{ __('підтверджено') }}</span></template>
                                        <template x-if="!data.email_verified_at"><span>{{ __('Адреса не підтверджена.') }}</span></template>
                                    </p>
                                </div>
                                <form method="POST" action="" :action="`/admin/users/${data.id}/toggle-verification`">
                                    @csrf
                                    <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-semibold text-white hover:bg-white/10">
                                        <span x-text="data.email_verified_at ? '{{ __('Зняти') }}' : '{{ __('Підтвердити') }}'"></span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Manual author verification --}}
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white flex items-center gap-2">
                                        {{ __('Verified Author (ручний знак)') }}
                                        <template x-if="data.manual_verification">
                                            <span class="inline-flex items-center gap-0.5 rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-2 py-0.5 text-[10px] font-bold text-emerald-200">verified</span>
                                        </template>
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('Перекриває автоматичну логіку. Зазвичай використовується для брендів та офіційних партнерів.') }}</p>
                                </div>
                                <form method="POST" action="" :action="`/admin/users/${data.id}/toggle-manual-verification`">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="inline-flex h-9 items-center rounded-lg border px-3 text-xs font-semibold transition" :class="data.manual_verification ? 'border-rose-300/30 bg-rose-300/[0.06] text-rose-200 hover:bg-rose-300/[0.12]' : 'border-emerald-300/30 bg-emerald-300/[0.06] text-emerald-200 hover:bg-emerald-300/[0.12]'">
                                        <span x-text="data.manual_verification ? '{{ __('Зняти Verified') }}' : '{{ __('Призначити Verified') }}'"></span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Reset password --}}
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <p class="text-sm font-semibold text-white">{{ __('Скинути пароль') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Залиште поле порожнім — система згенерує тимчасовий пароль і покаже його у статус-повідомленні.') }}</p>
                            <form
                                method="POST"
                                action=""
                                :action="`/admin/users/${data.id}/reset-password`"
                                class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                            >
                                @csrf
                                <x-admin.field name="password" type="password" :label="__('Новий пароль')" placeholder="{{ __('або залиште порожнім') }}" autocomplete="new-password" />
                                <x-admin.field name="password_confirmation" type="password" :label="__('Підтвердження')" placeholder="{{ __('повторіть пароль') }}" autocomplete="new-password" />
                                <div class="flex items-end">
                                    <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 text-sm font-bold text-amber-100 transition hover:bg-amber-300/15 sm:w-auto">{{ __('Скинути') }}</button>
                                </div>
                            </form>
                        </div>

                        {{-- Quick links --}}
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                            <p class="text-sm font-semibold text-white">{{ __('Активність') }}</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <a :href="`{{ route('admin.products') }}?author=${data.id}`" class="flex items-center justify-between gap-2 rounded-xl border border-white/10 bg-zinc-950/50 px-3 py-2 text-xs text-zinc-200 transition hover:bg-white/[0.06]">
                                    <span>{{ __('Моделі автора') }}</span>
                                    <span class="rounded-full bg-white/[0.06] px-2 py-0.5 font-bold" x-text="data.products_count">0</span>
                                </a>
                                <a :href="`{{ route('admin.orders') }}?user=${data.id}`" class="flex items-center justify-between gap-2 rounded-xl border border-white/10 bg-zinc-950/50 px-3 py-2 text-xs text-zinc-200 transition hover:bg-white/[0.06]">
                                    <span>{{ __('Замовлення користувача') }}</span>
                                    <span class="rounded-full bg-white/[0.06] px-2 py-0.5 font-bold" x-text="data.orders_count">0</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Danger zone tab --}}
                <div x-show="tab === 'danger'" x-cloak class="px-6 py-5">
                    <div class="rounded-2xl border border-rose-400/30 bg-rose-400/[0.06] p-5">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-rose-400/20 text-rose-200">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-white">{{ __('Видалити користувача') }}</p>
                                <p class="mt-1 text-xs leading-5 text-rose-200/80">{{ __('Незворотна дія: всі моделі автора буде видалено каскадно. Замовлення та платежі залишаться, але посилання на користувача буде втрачено.') }}</p>
                            </div>
                        </div>
                        <form
                            method="POST"
                            action=""
                            :action="`/admin/users/${data.id}`"
                            onsubmit="return confirm('{{ __('Точно видалити користувача? Цю дію неможливо скасувати.') }}')"
                            class="mt-4"
                        >
                            @csrf @method('DELETE')
                            <button
                                type="submit"
                                :disabled="data.is_self"
                                class="inline-flex h-10 items-center gap-2 rounded-xl border border-rose-400/40 bg-rose-500/10 px-4 text-sm font-bold text-rose-100 transition hover:bg-rose-500/20 disabled:opacity-40"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                {{ __('Видалити назавжди') }}
                            </button>
                            <p x-show="data.is_self" class="mt-2 text-[11px] text-amber-200">{{ __('Не можна видалити власний акаунт.') }}</p>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.admin>
