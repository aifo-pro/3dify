<x-layouts.admin :title="__('Теги блогу')" :description="__('Ключові теми для SEO і related posts.')" active="blog">
    @if(session('status'))<div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>@endif
    <x-admin.section :title="__('Новий тег')">
        <form method="POST" action="{{ route('admin.blog.tags.store') }}" class="grid gap-4 md:grid-cols-5">
            @csrf
            <x-admin.field name="name_uk" :label="__('Name UK')" required />
            <x-admin.field name="name_en" :label="__('Name EN')" />
            <x-admin.field name="slug" :label="__('Slug')" />
            <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="is_active" value="1" checked class="rounded border-white/20 bg-zinc-950 text-emerald-400"> {{ __('Активний') }}</label>
            <button class="h-11 rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950">{{ __('Створити') }}</button>
        </form>
    </x-admin.section>
    <x-admin.section :title="__('Список')" class="mt-6">
        <div class="space-y-3">
            @foreach($tags as $tag)
                <form method="POST" action="{{ route('admin.blog.tags.update', $tag) }}" class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 md:grid-cols-5">
                    @csrf @method('PATCH')
                    <input name="name_uk" value="{{ $tag->name_uk }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">
                    <input name="name_en" value="{{ $tag->name_en }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">
                    <input name="slug" value="{{ $tag->slug }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">
                    <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="is_active" value="1" @checked($tag->is_active) class="rounded border-white/20 bg-zinc-950 text-emerald-400"> {{ __('Активний') }}</label>
                    <div class="flex gap-2">
                        <button class="rounded-xl bg-emerald-400 px-4 text-xs font-black text-zinc-950">{{ __('Зберегти') }}</button>
                        <button form="delete-tag-{{ $tag->id }}" class="rounded-xl border border-rose-300/30 px-4 text-xs font-bold text-rose-200" type="submit">{{ __('Видалити') }}</button>
                    </div>
                </form>
                <form id="delete-tag-{{ $tag->id }}" method="POST" action="{{ route('admin.blog.tags.destroy', $tag) }}">@csrf @method('DELETE')</form>
            @endforeach
        </div>
    </x-admin.section>
</x-layouts.admin>
