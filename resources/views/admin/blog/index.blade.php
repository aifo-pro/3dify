<x-layouts.admin :title="__('Блог')" :description="__('SEO-статті, категорії, теги та RSS для 3Dify.')" active="blog">
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.blog.create') }}" class="inline-flex h-11 items-center rounded-2xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 hover:bg-emerald-300">{{ __('Нова стаття') }}</a>
        <a href="{{ route('admin.blog.categories') }}" class="inline-flex h-11 items-center rounded-2xl border border-white/10 bg-white/[0.05] px-5 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('Категорії') }}</a>
        <a href="{{ route('admin.blog.tags') }}" class="inline-flex h-11 items-center rounded-2xl border border-white/10 bg-white/[0.05] px-5 text-sm font-bold text-white hover:bg-white/[0.10]">{{ __('Теги') }}</a>
        <form method="GET" class="ml-auto flex flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('Пошук') }}" class="h-11 rounded-2xl border border-white/10 bg-zinc-950/60 px-4 text-sm text-white">
            <select name="status" class="h-11 rounded-2xl border border-white/10 bg-zinc-950/60 px-4 text-sm text-white">
                <option value="">{{ __('Усі статуси') }}</option>
                @foreach(\App\Models\BlogPost::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="h-11 rounded-2xl bg-white/[0.08] px-5 text-sm font-bold text-white">{{ __('Фільтр') }}</button>
        </form>
    </div>

    <x-admin.section :title="__('Статті')">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-[0.16em] text-zinc-500">
                    <tr><th class="px-4 py-3">{{ __('Назва') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Дата') }}</th><th>{{ __('Перегляди') }}</th><th class="text-right">{{ __('Дії') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($posts as $post)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-bold text-white">{{ $post->title_uk }}</div>
                                <div class="text-xs text-zinc-500">/{{ $post->slug }}</div>
                            </td>
                            <td><span class="rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-bold text-zinc-200">{{ $post->status }}</span></td>
                            <td class="text-zinc-400">{{ optional($post->published_at)->format('d.m.Y H:i') ?: '—' }}</td>
                            <td class="text-zinc-400">{{ number_format($post->views) }}</td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('admin.blog.edit', $post) }}" class="text-emerald-300 hover:text-emerald-100">{{ __('Редагувати') }}</a>
                                <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" class="ml-3 inline" onsubmit="return confirm('{{ __('Видалити статтю?') }}')">@csrf @method('DELETE')<button class="text-rose-300 hover:text-rose-100">{{ __('Видалити') }}</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-zinc-500">{{ __('Статей ще немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $posts->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
