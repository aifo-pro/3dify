@php
    $name = $term->localized('name');
    if ($type === 'category') {
        $seoTitle = ($term->localized('seo_title') ?: $name).' · '.__('blog.meta.index_title');
        $seoDescription = $term->localized('seo_description') ?: $term->localized('description') ?: $name;
        $canonical = route('blog.category', $term);
    } else {
        $seoTitle = $name.' · '.__('blog.meta.index_title');
        $seoDescription = __('blog.term_tag_description', ['tag' => $name]);
        $canonical = route('blog.tag', $term);
    }
@endphp
<x-layouts.marketplace
    :seo-title="$seoTitle"
    :seo-description="$seoDescription"
    :seo-canonical="$canonical"
>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <x-ui.badge>{{ $type === 'category' ? __('blog.label_category') : __('blog.label_tag') }}</x-ui.badge>
        <h1 class="mt-5 text-5xl font-black text-white">{{ $name }}</h1>
        @if($seoDescription)<p class="mt-4 max-w-3xl text-zinc-400">{{ $seoDescription }}</p>@endif

        @if($posts->isEmpty())
            <div class="mt-10 max-w-2xl">
                <x-ui.empty-state :title="__('blog.term_empty_title')" :description="__('blog.term_empty_hint')" />
            </div>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $post)
                    @include('marketplace.blog.partials.card', ['post' => $post])
                @endforeach
            </div>
        @endif
        @if($posts->hasPages())
            <div class="mt-8">{{ $posts->links() }}</div>
        @endif
    </section>
</x-layouts.marketplace>
