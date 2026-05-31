<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:sans-serif;background:#09090b;color:#e4e4e7;margin:0;padding:24px}.card{max-width:520px;margin:0 auto;background:#18181b;border-radius:16px;padding:32px;border:1px solid rgba(255,255,255,.1)}.btn{display:inline-block;background:#34d399;color:#09090b;font-weight:700;padding:12px 28px;border-radius:12px;text-decoration:none;margin-top:20px}h2{color:#fff;margin:0 0 12px}p{color:#a1a1aa;line-height:1.6}</style>
</head>
<body>
<div class="card">
    <h2>{{ __('mail.abandoned_cart_greeting', ['name' => $user->displayName()]) }}</h2>
    <p>{{ __('mail.abandoned_cart_line1') }}</p>
    <p><strong style="color:#fff">{{ $product->localized('title') }}</strong><br>
    {{ $product->is_free ? __('Безкоштовно') : number_format((float)$product->price,2).' '.$product->currency }}</p>
    <a href="{{ $checkoutUrl }}" class="btn">{{ __('mail.abandoned_cart_cta') }}</a>
    <p style="margin-top:20px;font-size:13px">{{ __('mail.abandoned_cart_line2') }}</p>
</div>
</body>
</html>
