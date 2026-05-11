<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function githubRedirect()
    {
        abort_unless(class_exists(Socialite::class), 501, 'Install laravel/socialite to enable GitHub login.');

        return Socialite::driver('github')->redirect();
    }

    public function githubCallback()
    {
        abort_unless(class_exists(Socialite::class), 501, 'Install laravel/socialite to enable GitHub login.');

        try {
            $github = Socialite::driver('github')->user();
        } catch (\Throwable $e) {
            Log::error('GitHub OAuth callback failed', ['message' => $e->getMessage()]);

            return redirect()->route('login')->with('error', __('Не вдалося авторизуватися через GitHub. Спробуйте ще раз.'));
        }

        $user = User::where('github_id', $github->getId())->first();

        if (! $user) {
            $email = $github->getEmail() ?: 'github-'.$github->getId().'@3dify.local';
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'github_id' => $github->getId(),
                ]);
            } else {
                $nickname = $github->getNickname();
                if ($nickname && User::where('username', $nickname)->exists()) {
                    $nickname = $nickname.'-'.Str::random(4);
                }

                $user = User::create([
                    'github_id' => $github->getId(),
                    'name' => $github->getName() ?: $github->getNickname() ?: 'GitHub user',
                    'username' => $nickname,
                    'email' => $email,
                    'password' => Str::password(32),
                    'email_verified_at' => now(),
                ]);
            }
        }

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

        $user = User::where('telegram_id', $data['id'])->first();

        if (! $user) {
            $email = 'telegram-'.$data['id'].'@3dify.local';
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'telegram_id' => $data['id'],
                    'telegram_username' => $data['username'] ?? $user->telegram_username,
                ]);
            } else {
                $username = $data['username'] ?? null;
                if ($username && User::where('username', $username)->exists()) {
                    $username = $username.'-'.Str::random(4);
                }

                $user = User::create([
                    'telegram_id' => $data['id'],
                    'name' => $data['first_name'] ?? $data['username'] ?? 'Telegram user',
                    'username' => $username,
                    'telegram_username' => $data['username'] ?? null,
                    'email' => $email,
                    'password' => Str::password(32),
                    'email_verified_at' => now(),
                ]);
            }
        }

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
