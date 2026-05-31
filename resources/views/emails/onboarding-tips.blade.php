<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:sans-serif;background:#09090b;color:#e4e4e7;margin:0;padding:24px}.card{max-width:520px;margin:0 auto;background:#18181b;border-radius:16px;padding:32px;border:1px solid rgba(255,255,255,.1)}.tip{background:rgba(52,211,153,.07);border:1px solid rgba(52,211,153,.2);border-radius:12px;padding:16px;margin:12px 0}.btn{display:inline-block;background:#34d399;color:#09090b;font-weight:700;padding:12px 28px;border-radius:12px;text-decoration:none;margin-top:20px}h2{color:#fff;margin:0 0 12px}p{color:#a1a1aa;line-height:1.6}h3{color:#34d399;margin:0 0 6px;font-size:15px}</style>
</head>
<body>
<div class="card">
    <h2>{{ __('mail.onboarding_greeting', ['name' => $user->displayName()]) }}</h2>
    <p>{{ __('mail.onboarding_line1') }}</p>

    <div class="tip">
        <h3>{{ __('mail.onboarding_tip1_title') }}</h3>
        <p style="margin:0;font-size:14px">{{ __('mail.onboarding_tip1_body') }}</p>
    </div>
    <div class="tip">
        <h3>{{ __('mail.onboarding_tip2_title') }}</h3>
        <p style="margin:0;font-size:14px">{{ __('mail.onboarding_tip2_body') }}</p>
    </div>
    <div class="tip">
        <h3>{{ __('mail.onboarding_tip3_title') }}</h3>
        <p style="margin:0;font-size:14px">{{ __('mail.onboarding_tip3_body') }}</p>
    </div>

    <a href="{{ route('products.index') }}" class="btn">{{ __('mail.onboarding_cta') }}</a>
</div>
</body>
</html>
