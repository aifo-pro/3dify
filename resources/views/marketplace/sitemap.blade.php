<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
        @isset($u['lastmod'])<lastmod>{{ $u['lastmod'] }}</lastmod>@endisset
        @isset($u['changefreq'])<changefreq>{{ $u['changefreq'] }}</changefreq>@endisset
        @isset($u['priority'])<priority>{{ $u['priority'] }}</priority>@endisset
        @foreach(($u['images'] ?? []) as $image)
            <image:image>
                <image:loc>{{ $image['loc'] }}</image:loc>
                @if(! empty($image['title']))
                    <image:title>{{ $image['title'] }}</image:title>
                @endif
            </image:image>
        @endforeach
    </url>
@endforeach
</urlset>
