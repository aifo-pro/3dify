@php $isEdit = $challenge->exists; @endphp
<x-layouts.admin :title="$isEdit ? 'Редагувати челендж' : 'Новий челендж'" :breadcrumbs="[['label' => 'Челенджі', 'url' => route('admin.challenges.index')]]" breadcrumb-current="{{ $isEdit ? 'Редагувати' : 'Створити' }}">
    @if(session('status'))<div class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ $isEdit ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf @if($isEdit) @method('PUT') @endif

        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-bold text-zinc-400">Назва (UK) *</label><input name="title_uk" value="{{ old('title_uk', $challenge->localized('title','uk')) }}" required class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none"></div>
                    <div><label class="mb-1 block text-xs font-bold text-zinc-400">Назва (EN)</label><input name="title_en" value="{{ old('title_en', $challenge->localized('title','en')) }}" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none"></div>
                    <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-zinc-400">Опис</label><textarea name="description_uk" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none">{{ old('description_uk', $challenge->localized('description','uk')) }}</textarea></div>
                    <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-zinc-400">Приз</label><input name="prize_description" value="{{ old('prize_description', $challenge->prize_description) }}" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none"></div>
                    <div><label class="mb-1 block text-xs font-bold text-zinc-400">Початок</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $challenge->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none"></div>
                    <div><label class="mb-1 block text-xs font-bold text-zinc-400">Дедлайн</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $challenge->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2.5 text-sm text-white focus:border-emerald-400 focus:outline-none"></div>
                </div>
                <label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $challenge->is_active)) class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400"><span class="text-sm text-zinc-300">Активний</span></label>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <button type="submit" class="w-full rounded-xl bg-emerald-400 py-3 text-sm font-black text-zinc-950 hover:bg-emerald-300">{{ $isEdit ? 'Зберегти' : 'Створити' }}</button>
                <a href="{{ route('admin.challenges.index') }}" class="mt-2 block w-full rounded-xl border border-white/10 py-2.5 text-center text-sm text-zinc-400 hover:bg-white/[0.04]">Скасувати</a>
            </div>
        </div>
    </form>

    @if($isEdit && isset($entries) && $entries->isNotEmpty())
        <div class="mt-10">
            <h2 class="mb-4 text-lg font-black text-white">Роботи учасників</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($entries as $entry)
                    <div class="rounded-xl border border-white/[0.07] bg-zinc-900/50 p-3">
                        @if($entry->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($entry->image_path) }}" class="mb-2 h-32 w-full rounded-lg object-cover">
                        @endif
                        <p class="text-xs font-bold text-white">{{ $entry->user?->displayName() }}</p>
                        <form method="POST" action="{{ route('admin.challenges.entries.moderate', $entry) }}" class="mt-2 flex gap-2">
                            @csrf @method('PATCH')
                            <select name="status" class="flex-1 rounded-lg border border-white/10 bg-zinc-950/60 px-2 py-1.5 text-xs text-white">
                                @foreach(['pending','approved','winner','rejected'] as $s)
                                    <option value="{{ $s }}" @selected($entry->status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <button class="rounded-lg bg-emerald-400 px-3 py-1.5 text-xs font-bold text-zinc-950">OK</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.admin>
