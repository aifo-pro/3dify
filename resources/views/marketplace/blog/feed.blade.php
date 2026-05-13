<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<rss version="2.0">
    <channel>
        <title>3Dify Blog</title>
        <link>{{ route('blog.index') }}</link>
        <description>{{ __('blog.meta.rss_description') }}</description>
        <language>{{ app()->getLocale() }}</language>
        @foreach($posts as $post)
            <item>
                <title>{{ $post->localized_title }}</title>
                <link>{{ $post->url }}</link>
                <description><![CDATA[{{ $post->localized_excerpt }}]]></description>
                <pubDate>{{ optional($post->published_at)->toRfc2822String() }}</pubDate>
                <guid isPermaLink="true">{{ $post->url }}</guid>
            </item>
        @endforeach
    </channel>
</rss>
