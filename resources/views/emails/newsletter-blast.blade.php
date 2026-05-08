<!doctype html>
<html lang="uk">
<body style="margin:0;padding:0;background:#0a0f0d;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#e4e4e7;">
    <div style="max-width:600px;margin:24px auto;padding:24px;background:#111418;border:1px solid rgba(255,255,255,0.08);border-radius:18px;">
        <h1 style="margin:0 0 16px;color:#fff;font-size:22px;">{{ $blast->subject }}</h1>
        <div style="font-size:14px;line-height:1.6;color:#d4d4d8;">{!! nl2br(e($blast->body)) !!}</div>
        <hr style="margin:24px 0;border:none;border-top:1px solid rgba(255,255,255,0.08);">
        <p style="font-size:11px;color:#71717a;line-height:1.6;text-align:center;">
            {{ __('Ви отримали цей лист, бо підписалися на оновлення.') }}<br>
            <a href="{{ $unsubscribeUrl }}" style="color:#34d399;">{{ __('Відписатися') }}</a>
        </p>
    </div>
</body>
</html>
