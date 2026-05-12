@php
    $publicUrl = fn ($path) => $path ? Storage::disk('public')->url($path) : null;
    $coverImage = $publicUrl($product->cover_path)
        ?: ($product->gallery ? $publicUrl(collect($product->gallery)->first()) : null);
    $imagePreview = $product->previewFile && in_array($product->previewFile->extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)
        ? Storage::disk($product->previewFile->disk)->url($product->previewFile->path)
        : null;
    $previewImage = $coverImage ?: $imagePreview;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->localized('title') }} — 3Dify</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #05070a;
            color: #fff;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .preview {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: #05070a;
        }
        .preview img {
            max-width: 100%;
            max-height: calc(100vh - 80px);
            object-fit: contain;
            border-radius: 12px;
        }
        .preview .no-image {
            color: #71717a;
            font-size: 14px;
        }
        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            background: #0d1117;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .footer .title {
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .footer .link {
            font-size: 12px;
            font-weight: 600;
            color: #6ee7b7;
            text-decoration: none;
            white-space: nowrap;
            transition: color 0.15s;
        }
        .footer .link:hover { color: #a7f3d0; }
    </style>
</head>
<body>
    <div class="preview">
        @if($previewImage)
            <img src="{{ $previewImage }}" alt="{{ $product->localized('title') }}">
        @else
            <span class="no-image">{{ $product->localized('title') }}</span>
        @endif
    </div>
    <div class="footer">
        <span class="title">{{ $product->localized('title') }}</span>
        <a href="{{ route('products.show', $product) }}" target="_blank" rel="noopener" class="link">
            {{ __('Переглянути на 3Dify') }} &rarr;
        </a>
    </div>
</body>
</html>
