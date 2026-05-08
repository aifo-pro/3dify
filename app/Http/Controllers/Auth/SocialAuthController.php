<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function githubRedirect()
    {
        abort_unless(class_exists(\Laravel\Socialite\Facades\Socialite::class), 501, 'Install laravel/socialite to enable GitHub login.');

        return \Laravel\Socialite\Facades\Socialite::driver('github')->redirect();
    }

    public function githubCallback()
    {
        abort_unless(class_exists(\Laravel\Socialite\Facades\Socialite::class), 501, 'Install laravel/socialite to enable GitHub login.');
        $github = \Laravel\Socialite\Facades\Socialite::driver('github')->user();

        $user = User::updateOrCreate(
            ['github_id' => $github->getId()],
            [
                'name' => $github->getName() ?: $github->getNickname() ?: 'GitHub user',
                'username' => $github->getNickname(),
                'email' => $github->getEmail() ?: 'github-'.$github->getId().'@3dify.local',
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    public function telegram(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'url'],
            'auth_date' => ['nullable', 'integer'],
            'hash' => ['required', 'string'],
        ]);

        $this->verifyTelegramPayload($request->except('_token'));

        $user = User::firstOrCreate(
            ['telegram_id' => $data['id']],
            [
                'name' => $data['first_name'] ?? $data['username'] ?? 'Telegram user',
                'username' => $data['username'] ?? null,
                'telegram_username' => $data['username'] ?? null,
                'email' => 'telegram-'.$data['id'].'@3dify.local',
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    private function verifyTelegramPayload(array $payload): void
    {
        $botToken = app(SiteSettings::class)->string('auth.telegram_bot_token') ?: config('services.telegram.bot_token');

        abort_unless($botToken, 501, 'Telegram bot token is not configured.');

        $hash = $payload['hash'] ?? '';
        unset($payload['hash']);
        ksort($payload);

        $checkString = collect($payload)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secret = hash('sha256', $botToken, true);
        $expected = hash_hmac('sha256', $checkString, $secret);

        abort_unless(hash_equals($expected, $hash), 403);
    }
}
