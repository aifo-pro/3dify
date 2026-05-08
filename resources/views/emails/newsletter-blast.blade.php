<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>{{ $blast->subject }}</title>
    <style>
        @media (max-width: 600px) {
            .container { width: 100% !important; padding: 16px !important; }
            .body-pad { padding: 20px !important; }
            .product-row td { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#0a0f0d;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;color:#e4e4e7;-webkit-font-smoothing:antialiased;">
    @php
        // Auto-detect: if the admin pasted plain text (no HTML), wrap with nl2br + escape.
        // If it's our composer-generated HTML, render as-is.
        $isHtml = (bool) preg_match('/<[a-z][\s\S]*>/i', $blast->body);
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f0d;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background:#111418;border:1px solid rgba(255,255,255,0.08);border-radius:18px;overflow:hidden;">
                    {{-- Brand strip --}}
                    <tr>
                        <td style="padding:20px 28px;background:linear-gradient(135deg,rgba(52,211,153,0.18),rgba(125,211,252,0.12));border-bottom:1px solid rgba(255,255,255,0.06);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="middle">
                                        <a href="{{ config('app.url') }}" style="text-decoration:none;color:#ffffff;font-weight:800;font-size:18px;letter-spacing:-0.01em;">
                                            <span style="color:#34d399;">3</span>Dify
                                        </a>
                                    </td>
                                    <td align="right" valign="middle" style="color:#71717a;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;font-weight:600;">
                                        {{ $blast->sent_at?->translatedFormat('d M Y') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="body-pad" style="padding:32px 28px;">
                            {!! $isHtml ? $blast->body : nl2br(e($blast->body)) !!}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 28px;border-top:1px solid rgba(255,255,255,0.06);background:rgba(0,0,0,0.2);">
                            <p style="margin:0 0 10px;font-size:11px;color:#71717a;line-height:1.6;text-align:center;">
                                {{ __('Ви отримали цей лист, бо підписалися на оновлення :site.', ['site' => config('app.name', '3Dify')]) }}
                            </p>
                            <p style="margin:0;font-size:11px;color:#71717a;line-height:1.6;text-align:center;">
                                <a href="{{ $unsubscribeUrl }}" style="color:#34d399;text-decoration:underline;">{{ __('Відписатися від розсилки') }}</a>
                                <span style="margin:0 8px;color:#3f3f46;">·</span>
                                <a href="{{ config('app.url') }}" style="color:#a1a1aa;text-decoration:none;">{{ config('app.url') }}</a>
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0;font-size:10px;color:#52525b;text-align:center;">
                    © {{ date('Y') }} {{ config('app.name', '3Dify') }}. {{ __('Всі права захищені.') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
